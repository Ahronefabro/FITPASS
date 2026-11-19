<?php

namespace Tests\Feature;

use App\Models\CheckIn;
use App\Models\Member;
use App\Models\Payment;
use App\Models\StaffNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffPortalTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->staff()->create([
            'name' => 'Front Desk Staff',
            'username' => 'frontdesk',
            'password' => 'secret',
        ]);
    }

    private function owner(): User
    {
        return User::factory()->owner()->create([
            'name' => 'Super Admin',
            'username' => 'admin',
            'password' => 'secret',
        ]);
    }

    private function member(array $attributes = []): Member
    {
        return Member::create($attributes + [
            'name' => 'Jaspher Sibayan',
            'plan' => 'Regular',
            'membership_type' => 'Monthly Unlimited',
            'contact' => '09171234567',
            'amount' => 1000,
            'joined_at' => today()->subMonth(),
            'expires_at' => today()->addMonth(),
        ]);
    }

    public function test_the_old_staff_login_url_leads_to_the_one_sign_in(): void
    {
        $this->get('/staff/login')->assertRedirect('/login');

        $this->get(route('login'))->assertOk()->assertSee('LOG IN');
    }

    public function test_staff_pages_send_guests_to_the_sign_in(): void
    {
        $this->get(route('staff.dashboard'))->assertRedirect(route('login'));
    }

    public function test_a_staff_account_signs_in_and_lands_on_the_staff_dashboard(): void
    {
        $this->staff();

        $this->post(route('login'), ['username' => 'frontdesk', 'password' => 'secret'])
            ->assertRedirect(route('staff.dashboard'));

        $this->assertAuthenticated();
    }

    public function test_an_account_without_portal_access_is_turned_away(): void
    {
        User::factory()->create(['username' => 'member1', 'password' => 'secret']);

        $this->post(route('login'), ['username' => 'member1', 'password' => 'secret'])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_the_owner_can_look_over_the_staff_pages(): void
    {
        $this->actingAs($this->owner())->get(route('staff.dashboard'))->assertOk();
    }

    public function test_every_staff_page_loads(): void
    {
        $this->member();
        StaffNotification::create(['title' => 'Test', 'body' => 'Body', 'kind' => 'Manual', 'level' => 'info']);
        Payment::create(['name' => 'Walk-in Guest', 'paid_on' => today(), 'kind' => 'Walk-in', 'amount' => 80, 'method' => 'Cash']);

        $this->actingAs($this->staff());

        foreach (['dashboard', 'checkins', 'payments', 'members', 'manual-time', 'notifications', 'scanner'] as $page) {
            $this->get(route("staff.{$page}"))->assertOk();
        }
    }

    public function test_the_dashboard_accepts_each_chart_range(): void
    {
        $this->actingAs($this->staff());

        foreach (['this_week', 'last_week', 'this_month', 'last_month', 'nonsense'] as $range) {
            $this->get(route('staff.dashboard', ['range' => $range]))->assertOk();
        }
    }

    public function test_a_manual_time_in_then_out_opens_and_closes_one_visit(): void
    {
        $member = $this->member();
        $this->actingAs($this->staff());

        $this->post(route('staff.manual-time.store'), ['member_id' => $member->id, 'action' => 'Check In'])
            ->assertSessionHas('status');

        $visit = CheckIn::sole();
        $this->assertSame('Manual', $visit->method);
        $this->assertNull($visit->time_out);

        $this->post(route('staff.manual-time.store'), ['member_id' => $member->id, 'action' => 'Check Out'])
            ->assertSessionHas('status');

        $this->assertNotNull($visit->fresh()->time_out);
        $this->assertSame(1, CheckIn::count());
    }

    public function test_a_second_check_in_is_refused_while_one_is_open(): void
    {
        $member = $this->member();
        $this->actingAs($this->staff());

        $this->post(route('staff.checkins.store'), ['member_id' => $member->id, 'action' => 'Check In']);
        $this->post(route('staff.checkins.store'), ['member_id' => $member->id, 'action' => 'Check In'])
            ->assertSessionHasErrors('member_id');

        $this->assertSame(1, CheckIn::count());
    }

    public function test_checking_out_someone_who_is_not_in_is_refused(): void
    {
        $member = $this->member();

        $this->actingAs($this->staff())
            ->post(route('staff.checkins.store'), ['member_id' => $member->id, 'action' => 'Check Out'])
            ->assertSessionHasErrors('member_id');
    }

    public function test_the_check_in_log_can_be_filtered(): void
    {
        $member = $this->member();
        // Now, not an hour ago: just after midnight an hour ago is yesterday's log.
        CheckIn::create(['member_id' => $member->id, 'time_in' => now(), 'method' => 'QR Code']);
        $this->actingAs($this->staff());

        $this->get(route('staff.checkins', ['filter' => 'QR Code']))->assertOk()->assertSee('QR Code');
        $this->get(route('staff.checkins', ['filter' => 'Manual']))->assertOk()->assertSee('No logs found.');
        $this->get(route('staff.checkins', ['filter' => 'Currently In']))->assertOk()->assertSee($member->name);
    }

    public function test_the_scanner_reads_a_member_code_and_records_the_visit(): void
    {
        $member = $this->member();
        $this->actingAs($this->staff());

        // The first scan checks the member in straight away.
        $this->postJson(route('staff.scanner.record'), ['code' => $member->qr_code])
            ->assertOk()
            ->assertJson(['action' => 'Check In', 'method' => 'QR CODE', 'membership' => 'ACTIVE']);

        $visit = CheckIn::sole();
        $this->assertSame('QR Code', $visit->method);
        $this->assertNull($visit->time_out);

        // The second scan checks them out of that same visit.
        $this->postJson(route('staff.scanner.record'), ['code' => $member->qr_code])
            ->assertOk()
            ->assertJson(['action' => 'Check Out']);

        $this->assertNotNull($visit->fresh()->time_out);
        $this->assertSame(1, CheckIn::count());

        // And a third scan starts a new visit.
        $this->postJson(route('staff.scanner.record'), ['code' => $member->qr_code])
            ->assertJson(['action' => 'Check In']);

        $this->assertSame(2, CheckIn::count());
    }

    public function test_the_scanner_is_a_standalone_page_that_still_records_scans(): void
    {
        $member = $this->member();
        $this->actingAs($this->staff());

        // Its own page: none of the staff shell, but a way back to it.
        $this->get(route('staff.scanner'))
            ->assertOk()
            ->assertDontSee('class="sidebar"', false)
            ->assertSee(route('staff.dashboard'), false)
            // The page posts each scan to the record endpoint with the session's token.
            ->assertSee(json_encode(route('staff.scanner.record')), false)
            ->assertSee(csrf_token(), false);

        // A scan sent the way the page sends it lands in the database.
        $this->postJson(route('staff.scanner.record'), ['code' => $member->qr_code], ['X-CSRF-TOKEN' => csrf_token()])
            ->assertOk()
            ->assertJson(['action' => 'Check In']);

        $this->assertDatabaseHas('check_ins', ['member_id' => $member->id, 'method' => 'QR Code', 'time_out' => null]);
    }

    public function test_the_front_page_scanner_records_without_a_sign_in(): void
    {
        $member = $this->member();

        // Anyone can open it; the way out is the sign-in.
        $this->get('/')
            ->assertOk()
            ->assertSee('Member QR Scanner')
            ->assertSee(json_encode(route('scan.record')), false)
            ->assertSee(route('login'), false)
            ->assertSee('Staff / Owner login');

        // The same check in and check out as the staff scanner, on the same tables.
        $this->postJson(route('scan.record'), ['code' => $member->qr_code])
            ->assertOk()
            ->assertJson(['action' => 'Check In']);
        $this->assertDatabaseHas('check_ins', ['member_id' => $member->id, 'method' => 'QR Code', 'time_out' => null]);

        $this->postJson(route('scan.record'), ['code' => $member->qr_code])
            ->assertJson(['action' => 'Check Out']);
        $this->assertSame(1, CheckIn::whereNotNull('time_out')->count());

        // So it shows in both portals' attendance screens.
        $this->actingAs($this->staff())->get(route('staff.checkins'))->assertOk()->assertSee($member->name);
        $this->actingAs($this->owner())->get(route('checkins.index'))->assertOk()->assertSee($member->name);
    }

    public function test_the_front_page_scanner_still_checks_codes_and_memberships(): void
    {
        $this->postJson(route('scan.record'), ['code' => 'FP-NOPE'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');

        $expired = $this->member(['expires_at' => now()->subDay()]);
        $this->postJson(route('scan.record'), ['code' => $expired->qr_code])
            ->assertStatus(422);

        $this->assertSame(0, CheckIn::count());
    }

    public function test_the_front_page_scanner_is_rate_limited(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->postJson(route('scan.record'), ['code' => 'FP-GUESS'.$i]);
        }

        $this->postJson(route('scan.record'), ['code' => 'FP-GUESS'])->assertStatus(429);
    }

    public function test_signed_in_users_see_the_front_page_scanner_with_a_way_back_to_their_portal(): void
    {
        $this->actingAs($this->staff())->get('/')->assertOk()->assertSee(route('staff.dashboard'), false);
        $this->actingAs($this->owner())->get('/')->assertOk()->assertSee(route('overview'), false)->assertSee('Owner portal');
    }

    public function test_an_unknown_code_is_rejected(): void
    {
        $this->actingAs($this->staff())
            ->postJson(route('staff.scanner.record'), ['code' => 'FP-NOPE'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');

        $this->assertSame(0, CheckIn::count());
    }

    public function test_the_scanner_turns_away_an_expired_member(): void
    {
        $member = $this->member(['expires_at' => today()->subDay()]);

        $this->actingAs($this->staff())
            ->postJson(route('staff.scanner.record'), ['code' => $member->qr_code])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code' => 'Membership expired']);

        $this->assertSame(0, CheckIn::count());
    }

    public function test_an_expired_member_already_inside_can_still_be_checked_out(): void
    {
        $member = $this->member(['expires_at' => today()->subDay()]);
        CheckIn::create(['member_id' => $member->id, 'time_in' => now()->subHour(), 'method' => 'QR Code']);

        $this->actingAs($this->staff())
            ->postJson(route('staff.scanner.record'), ['code' => $member->qr_code])
            ->assertOk()
            ->assertJson(['action' => 'Check Out']);

        $this->assertNotNull(CheckIn::sole()->time_out);
    }

    public function test_a_member_whose_last_day_is_today_can_still_check_in(): void
    {
        $member = $this->member(['expires_at' => today()]);

        $this->actingAs($this->staff())
            ->postJson(route('staff.scanner.record'), ['code' => $member->qr_code])
            ->assertOk()
            ->assertJson(['action' => 'Check In']);
    }

    public function test_a_hand_typed_code_matches_whatever_its_case_or_spacing(): void
    {
        $member = $this->member();

        $this->actingAs($this->staff())
            ->postJson(route('staff.scanner.record'), ['code' => '  '.mb_strtolower($member->qr_code).' '])
            ->assertOk()
            ->assertJson(['action' => 'Check In']);

        $this->assertSame($member->id, CheckIn::sole()->member_id);
    }

    public function test_staff_can_add_edit_and_remove_a_member(): void
    {
        $this->actingAs($this->staff());

        $this->post(route('staff.members.store'), [
            'name' => 'Liza Mendoza',
            'plan' => 'Student',
            'contact' => '09181234567',
        ])->assertSessionHas('status');

        $member = Member::where('name', 'Liza Mendoza')->sole();
        $this->assertEquals(800, $member->amount);
        $this->assertNotNull($member->qr_code);

        $this->patch(route('staff.members.update', $member), [
            'name' => 'Liza Mendoza-Cruz',
            'plan' => 'Regular',
            'contact' => '09181234568',
        ])->assertSessionHas('status');

        $this->assertSame('Regular', $member->fresh()->plan);

        $this->delete(route('staff.members.destroy', $member))->assertSessionHas('status');
        $this->assertSame(0, Member::count());
    }

    public function test_staff_can_record_a_members_payment_and_a_walk_in(): void
    {
        $member = $this->member();
        $this->actingAs($this->staff());

        $this->post(route('staff.payments.store'), [
            'member_id' => $member->id,
            'paid_on' => today()->toDateString(),
            'kind' => 'Monthly',
            'amount' => 700,
            'method' => 'Gcash',
        ])->assertSessionHas('status');

        // A member on file names the payment, so the typed name is not needed.
        $this->assertSame($member->name, Payment::sole()->name);

        $this->post(route('staff.payments.store'), [
            'name' => 'Walk-in Guest',
            'paid_on' => today()->toDateString(),
            'kind' => 'Walk-in',
            'amount' => 80,
            'method' => 'Cash',
        ])->assertSessionHas('status');

        $this->assertSame(2, Payment::count());
    }

    public function test_a_payment_needs_a_member_or_a_name(): void
    {
        $this->actingAs($this->staff())
            ->post(route('staff.payments.store'), [
                'paid_on' => today()->toDateString(),
                'kind' => 'Walk-in',
                'amount' => 80,
                'method' => 'Cash',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_notifications_can_all_be_marked_read(): void
    {
        StaffNotification::create(['title' => 'A', 'body' => 'B', 'kind' => 'Manual', 'level' => 'warning']);

        $this->actingAs($this->staff())
            ->post(route('staff.notifications.read'))
            ->assertSessionHas('status');

        $this->assertSame(0, StaffNotification::unread()->count());
    }

    public function test_signing_out_returns_to_the_sign_in(): void
    {
        $this->actingAs($this->staff())
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
