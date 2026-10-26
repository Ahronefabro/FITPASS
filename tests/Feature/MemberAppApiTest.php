<?php

namespace Tests\Feature;

use App\Models\CheckIn;
use App\Models\Member;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\MemberResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MemberAppApiTest extends TestCase
{
    use RefreshDatabase;

    private function signUp(array $overrides = [])
    {
        return $this->postJson('/api/register', $overrides + [
            'name' => 'Ana Reyes',
            'email' => 'ana@example.com',
            'contact' => '09170000000',
            'plan' => 'Student',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
    }

    private function token(string $email = 'ana@example.com'): string
    {
        return $this->postJson('/api/login', ['email' => $email, 'password' => 'password123'])
            ->assertOk()->json('token');
    }

    private function activeMember(): Member
    {
        return Member::create([
            'name' => 'Jaspher Sibayan',
            'plan' => 'Regular',
            'membership_type' => 'Monthly Unlimited',
            'email' => 'jaspher@example.com',
            'password' => 'password123',
            'amount' => 1000,
            'joined_at' => today()->subMonth(),
            'expires_at' => today()->addMonth(),
        ]);
    }

    public function test_a_sign_up_waits_for_payment_and_stays_out_of_the_member_counts(): void
    {
        $this->signUp()->assertCreated()
            ->assertJsonPath('member.status', 'Pending')
            ->assertJsonPath('member.qr_code', null);

        $this->assertSame(0, Member::count());
        $this->assertSame(1, Member::pending()->count());
    }

    public function test_a_sign_up_needs_a_unique_email_a_known_plan_and_a_confirmed_password(): void
    {
        $this->signUp()->assertCreated();

        $this->signUp(['plan' => 'Gold', 'password_confirmation' => 'nope'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'plan', 'password']);
    }

    public function test_a_pending_member_can_log_in_and_see_they_are_pending(): void
    {
        $this->signUp();

        $this->withToken($this->token())->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('member.name', 'Ana Reyes')
            ->assertJsonPath('member.status', 'Pending')
            ->assertJsonMissingPath('member.password');
    }

    public function test_a_wrong_password_is_refused(): void
    {
        $this->signUp();

        $this->postJson('/api/login', ['email' => 'ana@example.com', 'password' => 'wrong-one'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_the_api_needs_a_token_and_ignores_a_staff_session(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();

        $this->actingAs(User::factory()->staff()->create())
            ->getJson('/api/me')->assertUnauthorized();
    }

    public function test_staff_confirm_the_payment_and_the_membership_starts(): void
    {
        $this->signUp();
        $pending = Member::pending()->sole();

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('staff.members'))->assertOk()->assertSee('Ana Reyes')->assertSee('CONFIRM PAYMENT');

        $this->post(route('staff.members.approve', $pending->id), ['method' => 'Gcash'])->assertRedirect();

        $member = Member::sole();
        $this->assertSame('Active', $member->status);
        $this->assertTrue($member->expires_at->isSameDay(today()->addMonth()));
        $this->assertDatabaseHas('payments', [
            'member_id' => $member->id, 'kind' => 'Monthly', 'method' => 'Gcash', 'amount' => 800,
        ]);

        $this->withToken($this->token())->getJson('/api/me')
            ->assertJsonPath('member.status', 'Active')
            ->assertJsonPath('member.qr_code', $member->qr_code);
    }

    public function test_staff_can_reject_a_sign_up_but_not_approve_a_member_twice(): void
    {
        $this->signUp();
        $pending = Member::pending()->sole();
        $this->actingAs(User::factory()->staff()->create());

        $this->delete(route('staff.members.reject', $pending->id))->assertRedirect();
        $this->assertSame(0, Member::withPending()->count());

        $active = $this->activeMember();
        $this->post(route('staff.members.approve', $active->id), ['method' => 'Cash'])->assertNotFound();
        $this->assertSame(0, Payment::count());
    }

    public function test_the_scanner_does_not_know_a_pending_code(): void
    {
        $this->signUp();

        $this->actingAs(User::factory()->staff()->create())
            ->postJson(route('staff.scanner.record'), ['code' => Member::pending()->sole()->qr_code])
            ->assertUnprocessable();
    }

    public function test_a_member_sees_their_own_visits(): void
    {
        $member = $this->activeMember();
        CheckIn::create(['member_id' => $member->id, 'time_in' => now()->subDay(), 'time_out' => now()->subDay()->addHour(), 'method' => 'QR Code']);
        CheckIn::create(['member_id' => $member->id, 'time_in' => now()->subHour(), 'method' => 'Manual']);
        CheckIn::create(['guest_name' => 'Walk-in', 'time_in' => now(), 'method' => 'Manual']);
        $token = $this->token('jaspher@example.com');

        $this->withToken($token)->getJson('/api/check-ins?'.http_build_query([
            'from' => now()->subWeek()->toIso8601String(),
            'to' => now()->toIso8601String(),
        ]))->assertOk()->assertJsonCount(2, 'check_ins')->assertJsonPath('check_ins.0.time_out', null);

        $this->withToken($token)->getJson('/api/check-ins/latest')
            ->assertOk()->assertJsonPath('check_in.method', 'Manual');
    }

    public function test_a_member_changes_their_password(): void
    {
        $this->activeMember();
        $token = $this->token('jaspher@example.com');

        $this->withToken($token)->putJson('/api/me/password', [
            'current_password' => 'not-it', 'password' => 'newpass123', 'password_confirmation' => 'newpass123',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

        $this->withToken($token)->putJson('/api/me/password', [
            'current_password' => 'password123', 'password' => 'newpass123', 'password_confirmation' => 'newpass123',
        ])->assertOk();

        $this->postJson('/api/login', ['email' => 'jaspher@example.com', 'password' => 'newpass123'])->assertOk();
    }

    public function test_logging_out_ends_the_token(): void
    {
        $this->signUp();
        $token = $this->token();

        $this->withToken($token)->postJson('/api/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_a_pending_member_resets_a_forgotten_password_from_the_email_link(): void
    {
        Notification::fake();
        $this->signUp();
        $member = Member::pending()->sole();

        $this->postJson('/api/forgot-password', ['email' => 'ana@example.com'])->assertOk();
        $this->postJson('/api/forgot-password', ['email' => 'nobody@example.com'])->assertOk();

        $token = null;
        Notification::assertSentTo($member, MemberResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->get(route('member.password.reset', ['token' => $token, 'email' => 'ana@example.com']))
            ->assertOk()->assertSee(route('member.password.update'))->assertDontSee('Back to log in');

        $this->from(route('member.password.reset', ['token' => $token]))
            ->post(route('member.password.update'), [
                'token' => $token,
                'email' => 'ana@example.com',
                'password' => 'brandnew123',
                'password_confirmation' => 'brandnew123',
            ])->assertSessionHas('status');

        $this->postJson('/api/login', ['email' => 'ana@example.com', 'password' => 'brandnew123'])->assertOk();
    }
}
