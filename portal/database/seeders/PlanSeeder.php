<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // يجب أن يطابق mikrotik_profile حقل profile في /ppp secret على الراوتر (اسم ملف PPP profile).
        // عدّل الأسماء لتطابق ما عندك في Winbox إن اختلفت.
        Plan::updateOrCreate(['name' => '20M'], [
            'speed_mbps' => 20,
            'price' => 70,
            'duration_days' => 30,
            'mikrotik_profile' => '20M',
            'is_active' => true,
        ]);

        Plan::updateOrCreate(['name' => '30M'], [
            'speed_mbps' => 30,
            'price' => 90,
            'duration_days' => 30,
            'mikrotik_profile' => '30M',
            'is_active' => true,
        ]);

        Plan::updateOrCreate(['name' => '50M'], [
            'speed_mbps' => 50,
            'price' => 110,
            'duration_days' => 30,
            'mikrotik_profile' => '50M',
            'is_active' => true,
        ]);
    }
}
