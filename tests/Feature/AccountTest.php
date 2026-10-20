<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Notifications\EmailChangeCode;
use App\Notifications\EmailChanged;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The My Account page, where anyone signed in changes their own email and
 * password, and the seeder that must leave those changes alone.
 */
class AccountTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role = 'Staff'): User
    {
        return User::factory()->create([
            'name' => 'Front Desk',
            'username' => 'desk',
            'email' => 'desk@fitpass.test',
            'role' => $role,
            'password' => 'secret-pass',
        ]);
    }

    public function test_a_guest_is_sent_to_sign_in(): void
    {
        $this->get(route('account'))->assertRedirect(route('login'));
    }

    public function test_each_role_sees_the_page_inside_its_own_portal(): void
    {
        $this->actingAs($this->account('Staff'))->get(route('account'))
            ->assertOk()->assertSee('My Account')->assertSee(route('staff.dashboard'));

        $owner = User::factory()->create(['username' => 'boss', 'role' => 'Owner']);
        $this->actingAs($owner)->get(route('account'))
            ->assertOk()->assertSee(route('overview'));
    }

    /** Ask for a new email and return the code that was mailed to it. */
    private function requestCode(User $user, string $email = 'frontdesk@realgym.com'): string
    {
        $this->actingAs($user)->put(route('account.email'), [
            'email' => $email,
            'current_password' => 'secret-pass',
        ])->assertSessionHasNoErrors()->assertSessionHas('status');

        $code = null;
        Notification::assertSentOnDemand(EmailChangeCode::class, function ($notification, $channels, $notifiable) use ($email, &$code) {
            $code = $notification->code;

            return $notifiable->routes['mail'] === $email;
        });

        return $code;
    }

    public function test_a_new_email_only_takes_effect_after_its_code_is_entered(): void
    {
        Notification::fake();
        $user = $this->account();

        $code = $this->requestCode($user);

        // Nothing has changed yet: the page waits for the code.
        $this->assertSame('desk@fitpass.test', $user->fresh()->email);
        $this->get(route('account'))->assertSee('VERIFY EMAIL')->assertSee('frontdesk@realgym.com');

        $this->post(route('account.email.verify'), ['code' => $code])
            ->assertSessionHasNoErrors()->assertSessionHas('status');

        $user->refresh();
        $this->assertSame('frontdesk@realgym.com', $user->email);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(AuditLog::where('action', 'changed their account email')->exists());

        // The old address hears about it.
        Notification::assertSentOnDemand(EmailChanged::class, fn ($n, $c, $notifiable) => $notifiable->routes['mail'] === 'desk@fitpass.test');
    }

    public function test_a_wrong_code_changes_nothing_and_too_many_end_the_attempt(): void
    {
        Notification::fake();
        $user = $this->account();
        $code = $this->requestCode($user);
        $wrong = $code === '111111' ? '222222' : '111111';

        foreach (range(1, 4) as $try) {
            $this->post(route('account.email.verify'), ['code' => $wrong])
                ->assertSessionHasErrorsIn('verify', 'code');
        }

        // The fifth wrong try throws the pending change away, so even the
        // right code no longer works.
        $this->post(route('account.email.verify'), ['code' => $wrong])->assertSessionHasErrorsIn('verify', 'code');
        $this->post(route('account.email.verify'), ['code' => $code])->assertSessionHasErrorsIn('verify', 'code');

        $this->assertSame('desk@fitpass.test', $user->fresh()->email);
    }

    public function test_an_expired_code_is_refused(): void
    {
        Notification::fake();
        $user = $this->account();
        $code = $this->requestCode($user);

        $this->travel(16)->minutes();

        $this->post(route('account.email.verify'), ['code' => $code])->assertSessionHasErrorsIn('verify', 'code');
        $this->assertSame('desk@fitpass.test', $user->fresh()->email);
    }

    public function test_a_pending_change_can_be_cancelled(): void
    {
        Notification::fake();
        $user = $this->account();
        $code = $this->requestCode($user);

        $this->delete(route('account.email.cancel'))->assertSessionHas('status');
        $this->get(route('account'))->assertDontSee('VERIFY EMAIL');

        $this->post(route('account.email.verify'), ['code' => $code])->assertSessionHasErrorsIn('verify', 'code');
        $this->assertSame('desk@fitpass.test', $user->fresh()->email);
    }

    public function test_a_new_code_replaces_the_old_one(): void
    {
        Notification::fake();
        $user = $this->account();
        $this->requestCode($user);

        $this->post(route('account.email.resend'))->assertSessionHas('status');
        Notification::assertSentOnDemandTimes(EmailChangeCode::class, 2);
    }

    public function test_the_email_is_not_changed_without_the_right_password(): void
    {
        $user = $this->account();

        $this->actingAs($user)->put(route('account.email'), [
            'email' => 'thief@example.com',
            'current_password' => 'wrong',
        ])->assertSessionHasErrorsIn('email', 'current_password');

        $this->assertSame('desk@fitpass.test', $user->fresh()->email);
    }

    public function test_an_email_already_on_another_account_is_refused(): void
    {
        User::factory()->create(['username' => 'other', 'email' => 'taken@realgym.com']);
        $user = $this->account();

        $this->actingAs($user)->put(route('account.email'), [
            'email' => 'taken@realgym.com',
            'current_password' => 'secret-pass',
        ])->assertSessionHasErrorsIn('email', 'email');
    }

    public function test_the_password_can_be_changed(): void
    {
        $user = $this->account();

        $this->actingAs($user)->put(route('account.password'), [
            'current_password' => 'secret-pass',
            'password' => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])->assertSessionHasNoErrors()->assertSessionHas('status');

        $this->assertTrue(Hash::check('brand-new-pass', $user->fresh()->password));
    }

    public function test_the_password_is_not_changed_without_the_current_one(): void
    {
        $user = $this->account();

        $this->actingAs($user)->put(route('account.password'), [
            'current_password' => 'wrong',
            'password' => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])->assertSessionHasErrorsIn('password', 'current_password');

        $this->assertTrue(Hash::check('secret-pass', $user->fresh()->password));
    }

    public function test_seeding_again_keeps_a_changed_email_and_password(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('username', 'admin')->sole();
        $admin->forceFill(['email' => 'owner@realgym.com', 'password' => 'my-own-pass'])->save();

        $this->seed(DatabaseSeeder::class);

        $admin = User::where('username', 'admin')->sole();
        $this->assertSame('owner@realgym.com', $admin->email);
        $this->assertTrue(Hash::check('my-own-pass', $admin->password));
        $this->assertSame(2, User::count());
    }
}
