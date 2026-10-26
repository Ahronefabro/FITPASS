<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * There is one sign-in page, and the role on the user record decides which
 * portal it opens. These tests pin down where each role lands, which accounts
 * are refused outright, and what a role may reach once it is signed in.
 */
class PortalAccessTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role, string $username = 'someone'): User
    {
        return User::factory()->create([
            'name' => 'Account '.$username,
            'username' => $username,
            'role' => $role,
            'password' => 'secret',
        ]);
    }

    private function signIn(string $username = 'someone'): TestResponse
    {
        return $this->post(route('login'), ['username' => $username, 'password' => 'secret']);
    }

    // -----------------------------------------------------------------
    // One sign-in page
    // -----------------------------------------------------------------

    public function test_the_sign_in_page_is_public(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('LOG IN');
    }

    public function test_the_old_per_portal_login_url_leads_here(): void
    {
        $this->get('/staff/login')->assertRedirect('/login');
    }

    public function test_an_owner_page_sends_a_guest_to_the_sign_in(): void
    {
        $this->get(route('overview'))->assertRedirect(route('login'));
    }

    // -----------------------------------------------------------------
    // The role decides where a sign-in lands
    // -----------------------------------------------------------------

    public static function landingRoles(): array
    {
        return [
            'an owner goes to the owner portal' => ['Owner', 'overview'],
            'an admin goes to the owner portal' => ['Admin', 'overview'],
            'staff go to the staff portal' => ['Staff', 'staff.dashboard'],
            'front desk go to the staff portal' => ['Front Desk', 'staff.dashboard'],
            'trainers go to the staff portal' => ['Trainers', 'staff.dashboard'],
        ];
    }

    /**
     * @dataProvider landingRoles
     */
    public function test_a_role_lands_on_its_own_portal(string $role, string $home): void
    {
        $this->account($role);

        $this->signIn()->assertRedirect(route($home));

        $this->assertAuthenticated();
    }

    public function test_a_role_that_belongs_to_no_portal_is_refused(): void
    {
        $this->account('Member');

        $this->signIn()->assertSessionHasErrors('username');

        // Refused before anything is signed in, so no session is ever created.
        $this->assertGuest();
    }

    public function test_an_account_with_an_empty_role_is_refused(): void
    {
        $this->account('');

        $this->signIn()->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    /**
     * A role that belongs to no portal, a wrong password and a name that does
     * not exist all read the same, so the form never confirms which accounts
     * are out there or what role one carries.
     */
    public function test_every_refused_sign_in_reads_the_same(): void
    {
        $this->account('Member', 'member1');

        $messages = collect([
            ['username' => 'member1', 'password' => 'secret'],  // a refused role
            ['username' => 'member1', 'password' => 'nope'],    // a wrong password
            ['username' => 'nobody', 'password' => 'secret'],   // an unknown name
        ])->map(fn (array $credentials) => $this->post(route('login'), $credentials)->exception?->errors());

        $this->assertCount(1, $messages->unique());
        $this->assertSame(['username' => ['Wrong name or password.']], $messages->first());
    }

    // -----------------------------------------------------------------
    // What each role reaches once signed in
    // -----------------------------------------------------------------

    public function test_staff_are_turned_back_from_every_owner_page(): void
    {
        $this->actingAs($this->account('Staff'));

        foreach (['overview', 'analytics', 'audit-logs', 'members.index', 'staff.index'] as $page) {
            $this->get(route($page))->assertRedirect(route('staff.dashboard'));
        }
    }

    public function test_an_owner_reaches_both_portals(): void
    {
        $this->actingAs($this->account('Owner'));

        $this->get(route('overview'))->assertOk();
        $this->get(route('staff.dashboard'))->assertOk();
    }

    public function test_staff_cannot_post_to_an_owner_page_either(): void
    {
        $this->actingAs($this->account('Staff'));

        $this->post(route('staff.store'), [
            'name' => 'Sneaky Hire',
            'role' => 'Admins',
            'shift_start' => '06:00',
            'status' => 'Active',
        ])->assertRedirect(route('staff.dashboard'));

        $this->assertSame(0, Staff::where('name', 'Sneaky Hire')->count());
    }

    // -----------------------------------------------------------------
    // The role is re-read on every request, not just at sign-in
    // -----------------------------------------------------------------

    public function test_a_role_moved_to_the_other_portal_takes_effect_at_once(): void
    {
        $user = $this->account('Owner');
        $this->actingAs($user);

        $this->get(route('overview'))->assertOk();

        $user->forceFill(['role' => 'Staff'])->save();

        $this->get(route('overview'))->assertRedirect(route('staff.dashboard'));
    }

    public function test_a_role_taken_away_mid_session_ends_the_session(): void
    {
        $user = $this->account('Staff');
        $this->actingAs($user);

        $this->get(route('staff.dashboard'))->assertOk();

        $user->forceFill(['role' => 'Member'])->save();

        $this->get(route('staff.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_a_revoked_role_gets_a_status_code_on_the_json_endpoints(): void
    {
        $user = $this->account('Staff');
        $this->actingAs($user);

        $user->forceFill(['role' => 'Member'])->save();

        // Not a redirect, which the scanner would read as a successful scan.
        $this->postJson(route('staff.scanner.lookup'), ['code' => 'FP-0001'])
            ->assertStatus(403);
    }

    // -----------------------------------------------------------------
    // Where a sign-in lands when a page was asked for first
    // -----------------------------------------------------------------

    public function test_a_page_the_role_may_see_is_honoured(): void
    {
        $this->account('Staff');

        $this->get(route('staff.payments'))->assertRedirect(route('login'));

        $this->signIn()->assertRedirect(route('staff.payments'));
    }

    public function test_a_page_the_role_may_not_see_is_dropped_for_its_own_home(): void
    {
        $this->account('Staff');

        // A front-desk bookmark of an owner page.
        $this->get(route('analytics'))->assertRedirect(route('login'));

        $this->signIn()->assertRedirect(route('staff.dashboard'));
    }

    public function test_an_open_session_is_sent_to_its_own_portal_home(): void
    {
        $this->actingAs($this->account('Staff'))
            ->get(route('login'))
            ->assertRedirect(route('staff.dashboard'));

        $this->actingAs($this->account('Owner', 'boss'))
            ->get(route('login'))
            ->assertRedirect(route('overview'));
    }

    // -----------------------------------------------------------------
    // Signing out, the audit trail, and the staff directory
    // -----------------------------------------------------------------

    public function test_signing_out_returns_to_the_sign_in(): void
    {
        $this->actingAs($this->account('Owner'))
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_each_sign_in_is_recorded_against_the_account(): void
    {
        $this->account('Owner', 'admin');

        $this->signIn('admin');

        $entry = AuditLog::sole();
        $this->assertSame('Account admin', $entry->user_name);
        $this->assertSame('signed in to the owner portal', $entry->action);
    }

    public function test_a_refused_sign_in_is_not_recorded_as_a_sign_in(): void
    {
        $this->account('Member');

        $this->signIn();

        $this->assertSame(0, AuditLog::count());
    }

    public function test_a_sign_in_stamps_the_matching_staff_directory_row(): void
    {
        $user = $this->account('Staff', 'frontdesk');
        $row = Staff::create([
            'name' => $user->name,
            'role' => 'Staff',
            'shift_start' => '06:00',
            'status' => 'Active',
        ]);

        $this->assertNull($row->last_login_at);

        $this->signIn('frontdesk');

        $this->assertNotNull($row->fresh()->last_login_at);
    }
}
