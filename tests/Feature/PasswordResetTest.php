<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Forgotten passwords: ask for a link by email, follow it, choose a new one.
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function account(): User
    {
        return User::factory()->create([
            'name' => 'Front Desk',
            'username' => 'desk',
            'email' => 'desk@fitpass.test',
            'role' => 'Staff',
            'password' => 'old-password',
        ]);
    }

    public function test_the_sign_in_page_links_to_the_forgot_password_form(): void
    {
        $this->get(route('login'))->assertSee(route('password.request'));
        $this->get(route('password.request'))->assertOk()->assertSee('FORGOT PASSWORD');
    }

    public function test_a_reset_link_is_emailed_to_the_account(): void
    {
        Notification::fake();
        $user = $this->account();

        $this->post(route('password.email'), ['email' => 'desk@fitpass.test'])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_an_unknown_email_gets_the_same_answer_and_no_email(): void
    {
        Notification::fake();
        $this->account();

        $known = $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'desk@fitpass.test'])
            ->assertSessionHasNoErrors()->baseResponse->getSession()->get('status');

        $this->flushSession();

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'nobody@fitpass.test'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', $known);

        Notification::assertSentTimes(ResetPassword::class, 1);
    }

    public function test_the_link_lets_them_choose_a_new_password_and_sign_in(): void
    {
        $user = $this->account();
        $token = Password::createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()->assertSee('NEW PASSWORD');

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])->assertRedirect(route('login'))->assertSessionHas('status');

        $this->assertTrue(Hash::check('brand-new-pass', $user->fresh()->password));
        $this->assertTrue(AuditLog::where('action', 'reset their password')->where('user_name', 'Front Desk')->exists());

        $this->post(route('login'), ['username' => 'desk', 'password' => 'brand-new-pass'])
            ->assertRedirect(route('staff.dashboard'));
    }

    public function test_a_bad_token_changes_nothing(): void
    {
        $user = $this->account();

        $this->post(route('password.update'), [
            'token' => 'not-a-real-token',
            'email' => $user->email,
            'password' => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_the_new_password_must_be_confirmed_and_long_enough(): void
    {
        $user = $this->account();
        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }
}
