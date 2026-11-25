<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportLegacyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.legacy' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
        Artisan::call('migrate', ['--database' => 'legacy', '--force' => true]);

        $legacy = DB::connection('legacy');
        $legacy->table('users')->insert(['id' => 7, 'name' => 'Snevver', 'email' => 'a@b.c', 'password' => 'hash', 'created_at' => now(), 'updated_at' => now()]);
        $legacy->table('exercises')->insert(['id' => 3, 'user_id' => 7, 'name' => 'Squat', 'created_at' => now(), 'updated_at' => now()]);
        $legacy->table('training_schemes')->insert(['id' => 2, 'user_id' => 7, 'name' => 'Legs', 'created_at' => now(), 'updated_at' => now()]);
        $legacy->table('exercise_training_scheme')->insert(['exercise_id' => 3, 'training_scheme_id' => 2, 'order' => 1]);
        $legacy->table('gym_logs')->insert(['user_id' => 7, 'date' => '2026-11-20', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_it_copies_and_verifies_without_touching_legacy(): void
    {
        $before = DB::connection('legacy')->table('exercises')->count();

        $this->artisan('app:import-legacy')->assertSuccessful();

        $this->assertSame(1, DB::table('users')->where('id', 7)->count());
        $this->assertSame('Squat', DB::table('exercises')->value('name'));
        $this->assertSame(1, DB::table('exercise_training_scheme')->count());
        $this->assertSame(1, DB::table('gym_logs')->count());
        $this->assertSame($before, DB::connection('legacy')->table('exercises')->count());
    }

    public function test_it_skips_a_table_that_no_longer_exists_in_legacy(): void
    {
        DB::connection('legacy')->getSchemaBuilder()->drop('gym_logs');

        $this->artisan('app:import-legacy --dry-run')
            ->expectsOutputToContain('`gym_logs` does not exist; skipping')
            ->assertSuccessful();

        $this->artisan('app:import-legacy')
            ->expectsOutputToContain('`gym_logs` does not exist; skipping')
            ->assertSuccessful();

        $this->assertSame('Squat', DB::table('exercises')->value('name'));
        $this->assertSame(0, DB::table('gym_logs')->count());
    }

    public function test_it_refuses_to_import_into_a_non_empty_database(): void
    {
        DB::table('users')->insert(['name' => 'x', 'email' => 'x@y.z', 'password' => 'x']);

        $this->artisan('app:import-legacy')->assertFailed();
        $this->assertSame(0, DB::table('exercises')->count());
    }
}
