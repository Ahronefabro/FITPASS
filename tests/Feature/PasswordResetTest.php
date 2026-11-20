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

    public function test_account_details_change_username_and_email_together(): void
    {
        $user = $this->account();
        User::factory()->create(['username' => 'other', 'email' => 'taken@example.com', 'role' => 'Staff']);
        $this->actingAs($user);

        // One card holds both fields.
        $this->get(route('account.edit'))->assertOk()
            ->assertSee('Account details')
            ->assertSee('value="desk"', false)
            ->assertSee('value="desk@fitpass.test"', false)
            ->assertDontSee('Change email');

        $details = fn (array $over) => $this->patch(route('account.details'), $over + [
            'username' => 'desk', 'email' => 'desk@fitpass.test', 'current_password' => 'old-password',
        ]);

        // The current password is required; another account's username or email is refused.
        $details(['email' => 'me@example.com', 'current_password' => 'wrong'])->assertSessionHasErrorsIn('details', 'current_password');
        $details(['email' => 'taken@example.com'])->assertSessionHasErrorsIn('details', 'email');
        $details(['username' => 'other'])->assertSessionHasErrorsIn('details', 'username');
        $this->assertSame(['desk', 'desk@fitpass.test'], [$user->fresh()->username, $user->fresh()->email]);

        // Saving with nothing changed changes nothing.
        $details([])->assertSessionHas('status', 'Nothing to change.');

        // Both at once, each recorded.
        $details(['username' => 'frontdesk', 'email' => ' Me@Example.com '])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Username and email updated.');
        $this->assertSame(['frontdesk', 'me@example.com'], [$user->fresh()->username, $user->fresh()->email]);
        $this->assertTrue(AuditLog::where('action', 'changed their username from desk to frontdesk')->exists());
        $this->assertTrue(AuditLog::where('action', 'changed their email from desk@fitpass.test to me@example.com')->exists());

        // And "Forgot password?" now sends the link to the new address.
        auth()->logout();
        Notification::fake();
        $this->post(route('password.email'), ['email' => 'me@example.com']);
        Notification::assertSentTo($user, ResetPassword::class);
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
