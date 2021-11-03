<?php

namespace UptimeDevelopment\SocialiteCriipto;

use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory;
use Illuminate\Contracts\Container\BindingResolutionException;

class SocialiteCriiptoServiceProvider extends ServiceProvider
{
    /**
     * @throws BindingResolutionException
     */
    public function boot()
    {        
        $socialite = $this->app->make(Factory::class);

        $socialite->extend('criipto', function () use ($socialite) {
            $config = config('services.criipto');

            return $socialite->buildProvider(SocialiteCriiptoProvider::class, $config);
        });
    }
}