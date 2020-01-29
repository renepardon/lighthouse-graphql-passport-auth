<?php

namespace Renepardon\LighthouseGraphQLPassport\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use Laravel\Passport\Bridge\UserRepository;
use Laravel\Passport\Passport;
use League\OAuth2\Server\AuthorizationServer;
use Nuwave\Lighthouse\Events\BuildSchemaString;
use Renepardon\LighthouseGraphQLPassport\OAuthGrants\LoggedInGrant;
use Renepardon\LighthouseGraphQLPassport\OAuthGrants\SocialGrant;

class LighthouseGraphQLPassportServiceProvider extends ServiceProvider
{
    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function boot()
    {
        if (config('lighthouse-graphql-passport.migrations')) {
            $this->loadMigrationsFrom(__DIR__ . '/../../migrations');
        }

        app(AuthorizationServer::class)->enableGrantType($this->makeCustomRequestGrant(), Passport::tokensExpireIn());
        app(AuthorizationServer::class)->enableGrantType($this->makeLoggedInRequestGrant(), Passport::tokensExpireIn());
    }

    /**
     * @return \Renepardon\LighthouseGraphQLPassport\OAuthGrants\SocialGrant
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function makeCustomRequestGrant(): SocialGrant
    {
        $grant = new SocialGrant(
            $this->app->make(UserRepository::class),
            $this->app->make(RefreshTokenRepository::class)
        );

        $grant->setRefreshTokenTTL(Passport::refreshTokensExpireIn());

        return $grant;
    }

    /**
     * @return \Renepardon\LighthouseGraphQLPassport\OAuthGrants\LoggedInGrant
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function makeLoggedInRequestGrant(): LoggedInGrant
    {
        $grant = new LoggedInGrant(
            $this->app->make(UserRepository::class),
            $this->app->make(RefreshTokenRepository::class)
        );

        $grant->setRefreshTokenTTL(Passport::refreshTokensExpireIn());

        return $grant;
    }

    public function register()
    {
        $this->registerConfig();

        app('events')->listen(
            BuildSchemaString::class,
            function (): string {
                if (config('lighthouse-graphql-passport.schema')) {
                    return file_get_contents(config('lighthouse-graphql-passport.schema'));
                }

                return file_get_contents(__DIR__ . '/../../graphql/auth.graphql');
            }
        );
    }

    protected function registerConfig()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/config.php',
            'lighthouse-graphql-passport'
        );

        $this->publishes([
            __DIR__ . '/../../config/config.php' => config_path('lighthouse-graphql-passport.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/../../graphql/auth.graphql' => base_path('graphql/auth.graphql'),
        ], 'schema');

        $this->publishes([
            __DIR__ . '/../../migrations/2019_11_19_000000_update_social_provider_users_table.php' => base_path('database/migrations/2019_11_19_000000_update_social_provider_users_table.php'),
        ], 'migrations');
    }
}
