<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupportKnowledgeExtraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('support_knowledge')->insert([
            [
                'title' => 'Leak Sensor - Installation',
                'category' => 'product',
                'content' => 'Install the Leak Sensor near potential water leak areas such as under sinks, near washing machines, or water heaters. Ensure the sensor is placed flat on the surface.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Smart Valve - Installation',
                'category' => 'product',
                'content' => 'Smart Valve should be installed on the main water supply line by a certified plumber. Ensure proper alignment with water flow direction markings.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Battery Replacement - Leak Sensor',
                'category' => 'troubleshooting',
                'content' => 'To replace battery: open back cover, remove old batteries, insert new ones as per polarity marking, and close cover securely.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Device Not Reporting Data',
                'category' => 'troubleshooting',
                'content' => 'If device is not reporting data, verify internet connection, gateway power status, and confirm device is assigned to correct account.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Pricing - Pro Plan',
                'category' => 'pricing',
                'content' => 'Pro plan includes monitoring, alerts, analytics dashboard, and valve automation. Example pricing: $39/month per device.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Pricing - Enterprise Plan',
                'category' => 'pricing',
                'content' => 'Enterprise plan includes full automation, API access, priority support, and custom integrations. Contact sales for pricing.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Refund Policy',
                'category' => 'policy',
                'content' => 'Refund requests must be submitted within 14 days of purchase. Hardware refunds require product return in original condition.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Data Privacy Policy',
                'category' => 'policy',
                'content' => 'User data is encrypted in transit and at rest. We do not sell customer data to third parties.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Alert Notifications',
                'category' => 'product',
                'content' => 'Users receive alerts via mobile app notifications, email, or SMS depending on notification settings configured in the dashboard.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Gateway Setup',
                'category' => 'product',
                'content' => 'Connect gateway to power source and internet router. Wait until status light turns stable green before pairing devices.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Device Pairing Process',
                'category' => 'product',
                'content' => 'To pair device: open mobile app, navigate to Add Device, scan QR code on device, and follow on-screen instructions.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Low Battery Warning',
                'category' => 'troubleshooting',
                'content' => 'Low battery alerts appear in dashboard when battery level drops below 20%. Replace batteries promptly to avoid downtime.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Water Pressure Monitoring',
                'category' => 'product',
                'content' => 'Smart Valve monitors water pressure and alerts if pressure exceeds safe threshold limits.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Flow Rate Monitoring',
                'category' => 'product',
                'content' => 'Flow rate data helps detect abnormal continuous water usage which may indicate hidden leaks.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Manual Valve Override',
                'category' => 'product',
                'content' => 'Smart Valve includes manual override switch allowing user to open or close valve physically during maintenance.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Connectivity Issues',
                'category' => 'troubleshooting',
                'content' => 'If connectivity issues occur, restart gateway, check router settings, and ensure firewall is not blocking device communication.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Dashboard Analytics',
                'category' => 'product',
                'content' => 'Dashboard provides historical usage trends, leak event logs, and downloadable reports.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'User Role Permissions',
                'category' => 'policy',
                'content' => 'Admin users can manage devices and users. Standard users can only view assigned devices and alerts.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'SMS Notification Setup',
                'category' => 'product',
                'content' => 'To enable SMS alerts, go to Notification Settings and verify your mobile number with OTP verification.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Warranty Information',
                'category' => 'policy',
                'content' => 'All hardware devices come with 1-year limited manufacturer warranty covering defects.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Firmware Update Process',
                'category' => 'product',
                'content' => 'Firmware updates are pushed automatically over-the-air. Ensure device remains powered during update process.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'False Leak Alerts',
                'category' => 'troubleshooting',
                'content' => 'If false leak alerts occur, check for condensation or moisture near sensor area and reposition device if needed.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Account Password Reset',
                'category' => 'troubleshooting',
                'content' => 'To reset password, click Forgot Password on login page and follow email instructions.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Support Contact Hours',
                'category' => 'policy',
                'content' => 'Human support agents are available Monday to Friday, 9 AM to 6 PM local time.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}