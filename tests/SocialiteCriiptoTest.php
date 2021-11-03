<?php

namespace UptimeDevelopment\SocialiteCriipto\Tests;

use Laravel\Socialite\Contracts\Factory;
use Laravel\Socialite\SocialiteServiceProvider;
use UptimeDevelopment\SocialiteCriipto\SocialiteCriiptoProvider;
use UptimeDevelopment\SocialiteCriipto\SocialiteCriiptoServiceProvider;


class SocialiteCriiptoTest extends \Orchestra\Testbench\TestCase
{

    /** @test */
    public function test_it_can_instantiate_the_criipto_driver(): void
    {
        $factory = $this->app->make(Factory::class);

        $provider = $factory->driver('criipto');

        $this->assertInstanceOf(SocialiteCriiptoProvider::class, $provider);
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('services.criipto', [
            'client_id' => 'cognito-client-id',
            'client_secret' => 'cognito-secret',
            'redirect' => 'https://your-callback-url',
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [
            SocialiteServiceProvider::class,
            SocialiteCriiptoServiceProvider::class,
        ];
    }
}