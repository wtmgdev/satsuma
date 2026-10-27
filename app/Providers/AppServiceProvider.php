<?php

namespace App\Providers;

use App\Services\VulnerabilityFeed\PatchstackFeed;
use App\Services\VulnerabilityFeed\VulnerabilityFeed;
use App\Services\VulnerabilityFeed\WordfenceFeed;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(VulnerabilityFeed::class, function () {
            $provider = (string) config('services.vuln_feed.provider', 'wordfence');

            return match ($provider) {
                'patchstack' => new PatchstackFeed,
                default => new WordfenceFeed,
            };
        });

        RedirectResponse::macro('flashToast', function (string $type, string $message): RedirectResponse {
            Inertia::flash('toast', ['type' => $type, 'message' => $message]);

            return $this;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
