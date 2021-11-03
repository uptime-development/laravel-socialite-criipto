# Criipto driver for Laravel Socialite

This package is a custom Criipto driver for Laravel Socialite. This packages may be used for authenticating users with MitID

## Installation

You can install the package via composer:

```bash
composer require uptime-development/laravel-socialite-criipto
```

## Usage
Once you install the package, you will ned to create a account on https://criipto.id

Add the config values in you `config/services.php` configuration file from the newly created account:

```php
'criipto' => [
    'base_uri' => env('CRIIPTO_URI'),
    'client_id' => env('CRIIPTO_CLIENT_ID'),
    'client_secret' => env('CRIIPTO_CLIENT_SECRET'),
    'redirect' => env('CRIIPTO_REDIRECT_URI'),
],
```

You can nowuse the driver as you would use it in the Laravel Socialite's official [documentation](https://laravel.com/docs/8.x/socialite). Use `criipto` keyword when you want to use the driver followed with the extra parameter to define the [acr_values](https://docs.criipto.com/how-to/acr-values)

```php
Route::get('/auth/redirect', function () {
    return Socialite::driver('criipto')
            ->with(['acr_values' => 'urn:grn:authn:dk:mitid:low'])
            ->redirect();
});
```

```php

Route::get('/auth/callback', function () {
    $user = Socialite::driver('criipto')->user();
    dd($user);
});
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

If you discover any security related issues, please email anders.andersen@uptimedevelopment.dk

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Todo

[ ] Github Action - Should run tests and php-cs-fixer


