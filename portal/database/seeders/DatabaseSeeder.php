<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PlanSeeder::class);

        User::updateOrCreate(['email' => 'admin@mikro.local'], [
            'name' => 'Main Admin',
            'phone' => '0590000000',
            'role' => 'admin',
            'is_active' => true,
            'password' => Hash::make('admin12345'),
        ]);

        User::updateOrCreate(['email' => 'agent@mikro.local'], [
            'name' => 'Default Agent',
            'phone' => '0591111111',
            'role' => 'agent',
            'is_active' => true,
            'commission_percent' => 10,
            'monthly_collection_target' => 5000,
            'commission_paid_total' => 0,
            'password' => Hash::make('agent12345'),
        ]);
    }
}
