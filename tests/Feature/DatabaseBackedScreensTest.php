<?php

namespace Tests\Feature;

use App\Models\CheckIn;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Plans and prices, revenue, the system status panel and the app's
 * notification switch all come from the database rather than the code.
 */
class DatabaseBackedScreensTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::factory()->owner()->create(['username' => 'boss']);
    }

    private function member(string $plan = 'Regular', array $overrides = []): Member
    {
        return Member::create($overrides + [
            'name' => 'Ana Reyes',
            'plan' => $plan,
            'membership_type' => 'Monthly Unlimited',
            'email' => 'ana@example.com',
            'password' => 'password123',
            'amount' => 1000,
            'joined_at' => today(),
            'expires_at' => today()->addMonth(),
        ]);
    }

    // -----------------------------------------------------------------
    // Plans and prices
    // -----------------------------------------------------------------

    public function test_the_app_reads_the_plans_and_prices_from_the_database(): void
    {
        $this->getJson('/api/plans')->assertOk()->assertExactJson(['plans' => [
            ['name' => 'Regular', 'price' => 1000],
            ['name' => 'Student', 'price' => 800],
            ['name' => 'Senior', 'price' => 700],
        ]]);
    }

    public function test_the_owner_can_add_a_plan_and_change_a_price(): void
    {
        $this->actingAs($this->owner());

        $this->post(route('plans.store'), ['plan_name' => 'Couple', 'price' => 1500, 'colour' => '#FFC107'])
            ->assertSessionHasNoErrors();
        $this->patch(route('plans.update', Plan::where('name', 'Student')->sole()), ['price' => 850, 'colour' => '#40C4FF'])
            ->assertSessionHasNoErrors();

        $this->assertSame(1500.0, Plan::priceOf('Couple'));
        $this->assertSame(850.0, Plan::priceOf('Student'));
        $this->get(route('members.index'))->assertOk()->assertSee('Couple');
    }

    public function test_a_new_price_is_what_new_sign_ups_are_charged(): void
    {
        Plan::where('name', 'Student')->update(['price' => 850]);

        $this->postJson('/api/register', [
            'name' => 'Ana Reyes', 'email' => 'ana@example.com', 'plan' => 'Student',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertCreated();

        $this->assertEquals(850, Member::pending()->sole()->amount);
    }

    public function test_a_plan_with_members_cannot_be_deleted(): void
    {
        $this->member('Senior');
        $this->actingAs($this->owner());

        $this->delete(route('plans.destroy', Plan::where('name', 'Senior')->sole()))->assertStatus(422);
        $this->delete(route('plans.destroy', Plan::where('name', 'Student')->sole()));

        $this->assertSame(['Regular', 'Senior'], Plan::names());
    }

    public function test_a_plan_name_already_taken_is_refused(): void
    {
        $this->actingAs($this->owner())
            ->post(route('plans.store'), ['plan_name' => 'Regular', 'price' => 1, 'colour' => '#FFFFFF'])
            ->assertSessionHasErrorsIn('plan', 'plan_name');
    }

    public function test_staff_cannot_change_prices(): void
    {
        $this->actingAs(User::factory()->staff()->create(['username' => 'desk']));

        $this->patch(route('plans.update', Plan::first()), ['price' => 1, 'colour' => '#FFFFFF'])
            ->assertRedirect(route('staff.dashboard'));

        $this->assertSame(1000.0, Plan::priceOf('Regular'));
    }

    // -----------------------------------------------------------------
    // Revenue comes from payments
    // -----------------------------------------------------------------

    public function test_revenue_counts_payments_taken_including_walk_ins(): void
    {
        $member = $this->member('Student');
        Payment::create(['member_id' => $member->id, 'name' => $member->name, 'paid_on' => today(), 'kind' => 'Monthly', 'amount' => 800, 'method' => 'Cash']);
        Payment::create(['name' => 'Walk-in Guest', 'paid_on' => today(), 'kind' => 'Walk-in', 'amount' => 200, 'method' => 'Cash']);
        // Last month's money is not this month's revenue.
        Payment::create(['name' => 'Old', 'paid_on' => now()->startOfMonth()->subDay(), 'kind' => 'Walk-in', 'amount' => 999, 'method' => 'Cash']);

        $this->actingAs($this->owner());

        $this->get(route('overview'))->assertSee('Revenue This Month')->assertSee('₱1,000');
        $this->get(route('analytics'))->assertOk()
            ->assertSee('₱1,000 this month')
            ->assertSee('Walk-in')
            ->assertViewHas('revenue', fn ($revenue) => $revenue['Student'] === 80 && $revenue['Walk-in'] === 20);
    }

    // -----------------------------------------------------------------
    // System status
    // -----------------------------------------------------------------

    public function test_the_system_status_reports_what_actually_happened(): void
    {
        config(['mail.default' => 'log']);
        CheckIn::create(['member_id' => $this->member()->id, 'time_in' => now()->subMinutes(5), 'method' => 'QR Code']);

        $this->actingAs($this->owner())->get(route('overview'))->assertOk()
            ->assertViewHas('systems', fn ($systems) => collect($systems)->keyBy(0)->pipe(fn ($rows) =>
                $rows['QR Scanner'][1] === '5 minutes ago'
                && $rows['Last payment'][1] === 'None yet'
                && $rows['Email'][1] === 'Not sending (log only)'))
            ->assertDontSee('Not connected');
    }

    // -----------------------------------------------------------------
    // The app's notification switch
    // -----------------------------------------------------------------

    public function test_the_notification_switch_is_saved_and_read_back(): void
    {
        $this->member();
        $token = $this->postJson('/api/login', ['email' => 'ana@example.com', 'password' => 'password123'])->json('token');

        $this->withToken($token)->getJson('/api/me')->assertJsonPath('member.notifications_enabled', true);

        $this->withToken($token)->putJson('/api/me/settings', ['notifications_enabled' => false])
            ->assertOk()->assertJsonPath('member.notifications_enabled', false);

        $this->withToken($token)->getJson('/api/me')->assertJsonPath('member.notifications_enabled', false);
        $this->withToken($token)->putJson('/api/me/settings', ['notifications_enabled' => 'maybe'])->assertUnprocessable();
    }
}
