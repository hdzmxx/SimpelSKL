<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DatabaseSeederSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_requires_explicit_admin_credentials(): void
    {
        config([
            'sekolah.admin.email' => null,
            'sekolah.admin.password' => null,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Set ADMIN_EMAIL dan ADMIN_PASSWORD');

        $this->app->make(DatabaseSeeder::class)->run();
    }

    public function test_seeder_does_not_load_student_fixtures_by_default(): void
    {
        config([
            'sekolah.admin.email' => 'admin@example.test',
            'sekolah.admin.password' => 'a-strong-test-password',
            'sekolah.seed_sample_data' => false,
        ]);

        $this->app->make(DatabaseSeeder::class)->run();

        $this->assertDatabaseCount('siswa', 0);
        $this->assertDatabaseHas('pengguna', ['email' => 'admin@example.test']);
    }

    public function test_production_seeder_rejects_student_fixtures_before_writing_data(): void
    {
        $this->app['env'] = 'production';
        config(['sekolah.seed_sample_data' => true]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SEED_SAMPLE_DATA tidak boleh diaktifkan di production.');

        $this->app->make(DatabaseSeeder::class)->run();
    }

    public function test_production_seeder_rejects_short_admin_passwords(): void
    {
        $this->app['env'] = 'production';
        config([
            'sekolah.admin.email' => 'admin@example.test',
            'sekolah.admin.password' => 'short',
            'sekolah.seed_sample_data' => false,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ADMIN_PASSWORD harus memiliki minimal 12 karakter');

        $this->app->make(DatabaseSeeder::class)->run();
    }
}
