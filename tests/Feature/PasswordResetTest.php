<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Anyone who forgets their password can have a reset link emailed to them,
 * and the link lets them set a new one without signing in.
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function account(): User
    {
        return User::factory()->create([
            'username' => 'someone',
            'email' => 'someone@fitpass.test',
            'role' => 'Staff',
            'password' => 'secret',
        ]);
    }

    public function test_the_login_page_links_to_the_reset_form(): void
    {
        $this->get(route('login'))->assertSee(route('password.request'));
        $this->get(route('password.request'))->assertOk()->assertSee('FORGOT PASSWORD');
    }

    public function test_a_known_email_gets_a_reset_link(): void
    {
        Notification::fake();
        $user = $this->account();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_an_unknown_email_gets_the_same_answer(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'nobody@fitpass.test'])
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_the_link_sets_a_new_password(): void
    {
        $user = $this->account();
        $token = Password::createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()
            ->assertSee($user->email);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('brand-new-pass', $user->fresh()->password));
        $this->assertDatabaseHas('audit_logs', ['action' => 'reset their password']);

        $this->post(route('login'), ['username' => 'someone', 'password' => 'brand-new-pass'])
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

        $this->assertTrue(Hash::check('secret', $user->fresh()->password));
    }
}
