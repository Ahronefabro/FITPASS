<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Each row in the owner's staff directory belongs to exactly one login. Adding
 * a row makes the login, the row's role and status govern only that login, and
 * deleting the row deletes the login.
 */
class StaffDirectoryTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->owner()->create(['username' => 'boss']);
    }

    private function add(array $overrides = []): TestResponse
    {
        return $this->actingAs($this->owner)->post(route('staff.store'), $overrides + [
            'name' => 'Juan Cruz',
            'username' => 'juan',
            'email' => 'juan@fitpass.test',
            'password' => 'secret123',
            'shift_start' => '06:00',
            'status' => 'Active',
        ]);
    }

    private function signIn(string $username): TestResponse
    {
        auth()->logout();

        return $this->post(route('login'), ['username' => $username, 'password' => 'secret123']);
    }

    public function test_adding_staff_creates_a_login_that_can_sign_in(): void
    {
        $this->add()->assertSessionHasNoErrors();

        $row = Staff::sole();
        $this->assertSame('juan', $row->user->username);
        $this->assertSame('Staff', $row->user->role);

        $this->signIn('juan')->assertRedirect(route('staff.scanner'));
    }

    public function test_a_username_or_email_already_taken_is_refused(): void
    {
        $this->add();

        $this->add(['email' => 'other@fitpass.test'])->assertSessionHasErrors('username');
        $this->add(['username' => 'other'])->assertSessionHasErrors('email');

        $this->assertSame(1, Staff::count());
    }

    public function test_two_people_with_the_same_name_do_not_share_a_status(): void
    {
        $this->add();
        $this->add(['username' => 'juan2', 'email' => 'juan2@fitpass.test']);

        $first = Staff::whereRelation('user', 'username', 'juan')->sole();
        $this->patch(route('staff.update', $first), ['status' => 'Inactive']);

        $this->signIn('juan')->assertSessionHasErrors('username');
        $this->signIn('juan2')->assertRedirect(route('staff.scanner'));
    }

    public function test_staff_is_the_only_role(): void
    {
        $this->add(['role' => 'Front Desk'])->assertSessionHasErrors('role');
        $this->add()->assertSessionHasNoErrors();

        $this->patch(route('staff.update', Staff::sole()), ['role' => 'Admin'])->assertSessionHasErrors('role');

        $this->assertSame('Staff', User::where('username', 'juan')->value('role'));
        $this->get(route('staff.index'))->assertDontSee('Front Desk')->assertDontSee('Trainers');
    }

    public function test_front_desk_trainers_and_admins_become_staff(): void
    {
        foreach (['Front Desk' => 'desk', 'Trainers' => 'coach', 'Admin' => 'admin'] as $role => $username) {
            $user = User::factory()->create(['username' => $username, 'role' => $role]);
            $user->staff()->create(['name' => $username, 'role' => $role, 'shift_start' => '06:00', 'status' => 'Active']);
        }

        (require database_path('migrations/2026_11_19_000001_make_every_staff_role_staff.php'))->up();

        $this->assertSame(['Staff'], Staff::distinct()->pluck('role')->all());
        $this->assertSame(['Owner', 'Staff'], User::distinct()->orderBy('role')->pluck('role')->all());
        $this->assertSame('Owner', $this->owner->fresh()->role);
    }

    public function test_deleting_a_row_deletes_its_login(): void
    {
        $this->add();

        $this->delete(route('staff.destroy', Staff::sole()));

        $this->assertSame(0, Staff::count());
        $this->assertFalse(User::where('username', 'juan')->exists());
    }

    public function test_the_owners_own_login_cannot_be_changed_from_the_directory(): void
    {
        $row = $this->owner->staff()->create([
            'name' => $this->owner->name, 'role' => 'Staff', 'shift_start' => '06:00', 'status' => 'Active',
        ]);

        $this->actingAs($this->owner)->patch(route('staff.update', $row), ['status' => 'Inactive'])->assertForbidden();
        $this->delete(route('staff.destroy', $row))->assertForbidden();

        $this->assertSame('Owner', $this->owner->fresh()->role);
    }

    public function test_a_row_without_a_login_shows_as_such_and_can_be_deleted(): void
    {
        $row = Staff::create(['name' => 'Old Entry', 'role' => 'Staff', 'shift_start' => '06:00', 'status' => 'Active']);

        $this->actingAs($this->owner)->get(route('staff.index'))->assertOk()->assertSee('NO LOGIN');

        $this->delete(route('staff.destroy', $row));
        $this->assertSame(0, Staff::count());
    }
}
