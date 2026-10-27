<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Organization;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'client_id' => Client::factory(),
            'name' => fake()->company(),
            'url' => 'https://'.Str::lower(Str::random(10)).'.test',
            'api_token' => Str::random(64),
            'connection_status' => 'offline',
        ];
    }
}
