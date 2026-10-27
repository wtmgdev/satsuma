<?php

namespace Database\Factories;

use App\Models\Site;
use App\Models\SitePlugin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SitePlugin>
 */
class SitePluginFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'site_id' => Site::factory(),
            'name' => str($name)->title(),
            'slug' => str($name)->slug(),
            'version' => '1.0.0',
            'is_active' => true,
            'update_available' => false,
            'new_version' => null,
        ];
    }
}
