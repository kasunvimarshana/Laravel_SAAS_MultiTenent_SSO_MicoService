<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

/**
 * Configures Laravel Passport for multi-guard SSO authentication.
 */
final class AuthServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    protected $policies = [];

    public function boot(): void
    {
        // Configure Passport token lifetimes
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));

        // Enable token revocation on logout
        Passport::revokeOtherTokens();
    }
}
