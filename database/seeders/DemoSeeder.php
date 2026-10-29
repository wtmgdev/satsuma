<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\CreditTransaction;
use App\Models\Organization;
use App\Models\Site;
use App\Models\SiteAnalysis;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * A customer-side demo account: a non-superadmin user with an agency
     * organization, clients, sites, inventory, analyses and a credit ledger.
     */
    public function run(): void
    {
        $demo = User::firstOrCreate(
            ['email' => 'demo@satsuma.test'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $organization = $demo->organizations()->where('name', 'Demo Agency')->first()
            ?? Organization::createWithOwner($demo, 'Demo Agency');

        if ($organization->sites()->exists()) {
            return;
        }

        $bakery = Client::factory()->for($organization)->create(['name' => 'Konditoriet']);
        $architects = Client::factory()->for($organization)->create(['name' => 'Norr Architects']);

        $online = Site::factory()->for($organization)->for($bakery)->create([
            'name' => 'Konditoriet webshop',
            'url' => 'https://konditoriet.demo.test',
            'connection_status' => 'online',
            'wp_version' => '6.9.4',
            'php_version' => '8.3.14',
            'last_ping_at' => now()->subMinutes(3),
            'last_scan_at' => now()->subHours(2),
            'whois' => [
                'domain' => 'konditoriet.demo.test',
                'registrar' => 'Demo Registrar AB',
                'registered_at' => '2019-03-14T00:00:00Z',
                'expires_at' => '2027-03-14T00:00:00Z',
                'nameservers' => ['ns1.demo-dns.test', 'ns2.demo-dns.test'],
            ],
            'whois_checked_at' => now()->subHours(6),
        ]);

        $online->plugins()->createMany([
            ['name' => 'Advanced Custom Fields PRO', 'slug' => 'advanced-custom-fields-pro', 'version' => '6.7.0.2', 'is_active' => true, 'update_available' => true, 'new_version' => '6.8.10'],
            ['name' => 'WooCommerce', 'slug' => 'woocommerce', 'version' => '9.8.2', 'is_active' => true, 'update_available' => false, 'new_version' => null],
            ['name' => 'Yoast Duplicate Post', 'slug' => 'duplicate-post', 'version' => '4.6', 'is_active' => true, 'update_available' => false, 'new_version' => null],
            ['name' => 'Satsuma Agent', 'slug' => 'satsuma-agent', 'version' => '0.7.0', 'is_active' => true, 'update_available' => false, 'new_version' => null],
        ]);

        $online->themes()->createMany([
            ['name' => 'Storefront', 'slug' => 'storefront', 'version' => '4.6.1', 'is_active' => true, 'update_available' => false, 'new_version' => null],
            ['name' => 'Twenty Twenty-Five', 'slug' => 'twentytwentyfive', 'version' => '1.2', 'is_active' => false, 'update_available' => true, 'new_version' => '1.4'],
        ]);

        Site::factory()->for($organization)->for($architects)->create([
            'name' => 'Norr Architects portfolio',
            'url' => 'https://norr.demo.test',
            'connection_status' => 'offline',
            'wp_version' => '6.8.3',
            'php_version' => '8.1.34',
            'last_ping_at' => now()->subMinutes(4),
            'last_scan_at' => now()->subDay(),
        ]);

        SiteAnalysis::create([
            'site_id' => $online->id,
            'type' => 'pagespeed_mobile',
            'score' => 71,
            'data' => [
                'strategy' => 'mobile',
                'scores' => ['performance' => 71, 'accessibility' => 94, 'best-practices' => 100, 'seo' => 92],
                'metrics' => [
                    ['id' => 'first-contentful-paint', 'label' => 'First Contentful Paint', 'value' => '1.7 s', 'score' => 0.9],
                    ['id' => 'largest-contentful-paint', 'label' => 'Largest Contentful Paint', 'value' => '12.7 s', 'score' => 0.02],
                    ['id' => 'total-blocking-time', 'label' => 'Total Blocking Time', 'value' => '0 ms', 'score' => 1.0],
                    ['id' => 'cumulative-layout-shift', 'label' => 'Cumulative Layout Shift', 'value' => '0.449', 'score' => 0.18],
                    ['id' => 'speed-index', 'label' => 'Speed Index', 'value' => '2.0 s', 'score' => 0.93],
                    ['id' => 'interactive', 'label' => 'Time to Interactive', 'value' => '12.7 s', 'score' => 0.03],
                ],
                'opportunities' => [
                    ['title' => 'Properly size images', 'savings' => 'Est savings of 4.2 s'],
                    ['title' => 'Reduce unused JavaScript', 'savings' => 'Est savings of 1.2 s'],
                ],
            ],
            'summary' => [
                'headline' => 'The mobile score of 71 is dragged down by a single problem: the main content does not finish loading until 12.7 s.',
                'points' => [
                    'Optimize the largest image on the page — compress, resize to display dimensions, and serve WebP/AVIF.',
                    'Delay scripts and embeds that are not needed for the initial render; Time to Interactive matches LCP at 12.7 s.',
                    'Layout shift (0.449) likely comes from images without dimensions — set explicit width/height attributes.',
                ],
            ],
            'created_at' => now()->subHours(1),
        ]);

        CreditTransaction::insert([
            ['organization_id' => $organization->id, 'user_id' => $demo->id, 'site_id' => $online->id, 'action' => 'pagespeed_analysis', 'credits' => -2, 'quantity' => 2, 'cost_estimate' => 0.0040, 'description' => null, 'metered' => true, 'created_at' => now()->subHours(1)],
            ['organization_id' => $organization->id, 'user_id' => $demo->id, 'site_id' => $online->id, 'action' => 'ai_summary', 'credits' => -1, 'quantity' => 1, 'cost_estimate' => 0.0040, 'description' => null, 'metered' => true, 'created_at' => now()->subHours(1)],
            ['organization_id' => $organization->id, 'user_id' => null, 'site_id' => null, 'action' => 'topup', 'credits' => 100, 'quantity' => 1, 'cost_estimate' => null, 'description' => 'Purchased credit pack', 'metered' => true, 'created_at' => now()->subDays(3)],
        ]);

        $organization->forceFill([
            'credits_balance' => $organization->credits_balance - 3,
            'credits_purchased' => 100,
        ])->save();

        DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\SiteStatusChanged',
            'notifiable_type' => User::class,
            'notifiable_id' => $demo->id,
            'data' => ['title' => 'Site back online', 'message' => 'Norr Architects portfolio recovered and is responding again.', 'site_id' => $online->id],
            'created_at' => now()->subHours(3),
        ]);
    }
}
