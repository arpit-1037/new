<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupportKnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('support_knowledge')->truncate();

        DB::table('support_knowledge')->insert([
            [
                'title' => 'Leak Sensor',
                'category' => 'product',
                'content' => 'Leak Sensor detects water leaks and sends alerts to the dashboard/app. It typically reports temperature, humidity, and battery status depending on model.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Smart Valve',
                'category' => 'product',
                'content' => 'Smart Valve can automatically shut off water when a leak is detected (if configured). It also reports flow, pressure, and temperature depending on installation.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Pricing - Basic Plan',
                'category' => 'pricing',
                'content' => 'Basic plan: monitoring + alerts. Pricing: define your actual price here (example: $19/month per device).',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Troubleshooting - Device Offline',
                'category' => 'troubleshooting',
                'content' => "If a device is offline: (1) check device battery, (2) check gateway/internet connectivity, (3) verify device is within range, (4) wait 5–10 minutes and refresh dashboard.",
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Policy - Escalation',
                'category' => 'policy',
                'content' => 'If the assistant cannot find the answer in the knowledge base, it must respond: "I will escalate this to a human support agent."',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}