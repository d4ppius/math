<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnverifiedAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_migration_counts_existing_parents_as_verified_so_nobody_is_locked_out(): void
    {
        $older = User::factory()->unverified()->create();
        $verified = User::factory()->create(['email_verified_at' => '2026-01-01 10:00:00']);

        (require database_path('migrations/2026_09_20_130000_verify_existing_parent_accounts.php'))->up();

        $this->assertTrue($older->fresh()->hasVerifiedEmail());
        // Already verified accounts keep their original timestamp.
        $this->assertSame('2026-01-01 10:00:00', $verified->fresh()->email_verified_at->format('Y-m-d H:i:s'));
    }

    public function test_stale_unverified_accounts_and_the_empty_families_they_leave_are_deleted(): void
    {
        config(['auth.unverified_retention_days' => 7]);

        $stale = User::factory()->unverified()->create(['created_at' => now()->subDays(8)]);
        $staleFamily = $stale->family;

        $this->artisan('accounts:prune-unverified')->assertSuccessful();

        $this->assertModelMissing($stale);
        $this->assertModelMissing($staleFamily);
    }

    public function test_a_family_with_a_remaining_parent_or_children_is_kept(): void
    {
        $family = Family::factory()->create();
        $verifiedParent = User::factory()->for($family)->create();
        $staleInvitee = User::factory()->unverified()->for($family)->create(['created_at' => now()->subDays(30)]);
        $child = Child::factory()->for($family)->create();

        $this->artisan('accounts:prune-unverified')->assertSuccessful();

        $this->assertModelMissing($staleInvitee);
        $this->assertModelExists($verifiedParent);
        $this->assertModelExists($family);
        $this->assertModelExists($child);
    }

    public function test_recent_unverified_verified_and_admin_accounts_are_never_touched(): void
    {
        $recent = User::factory()->unverified()->create(['created_at' => now()->subDays(2)]);
        $verifiedOld = User::factory()->create(['created_at' => now()->subYear()]);
        $admin = User::factory()->unverified()->create(['created_at' => now()->subYear()]);
        $admin->is_admin = true;
        $admin->save();

        $this->artisan('accounts:prune-unverified')->assertSuccessful();

        $this->assertModelExists($recent);
        $this->assertModelExists($verifiedOld);
        $this->assertModelExists($admin);
    }

    public function test_the_privacy_statement_names_the_cleanup_period(): void
    {
        config(['auth.unverified_retention_days' => 7]);

        $this->get(route('legal.privacy'))->assertSee('innerhalb von 7 Tagen bestätigt wird');
    }
}
