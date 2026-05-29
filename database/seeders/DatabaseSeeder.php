<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $password = env('HIREME_ADMIN_PASSWORD');

        if (! $password && app()->environment(['local', 'testing'])) {
            $password = 'hireme-local-admin';
        }

        if (! $password) {
            throw new RuntimeException('Set HIREME_ADMIN_PASSWORD before seeding the admin user outside local/testing.');
        }

        User::updateOrCreate(
            ['email' => 'admin@hireme.local'],
            [
                'name' => 'HireMe Admin',
                'password' => $password,
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $this->call(DemoDataSeeder::class);
    }
}
