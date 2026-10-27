<?php

namespace Database\Factories;

use App\Models\Site;
use App\Models\SiteTheme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteTheme>
 */
class SiteThemeFactory extends Factory
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
