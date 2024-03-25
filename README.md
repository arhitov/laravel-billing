Laravel Billing
==============

![PHP][ico-php-support]
[![Laravel][ico-laravel-support]][link-laravel-support]
[![Software License][ico-license]][link-license]

Billing module for Laravel projects with support for transactions, invoicing, subscriptions, working with omnipay gateways and acquiring.

## Possibilities

- Multi-balance
- Operations history

### in developing

- Subscriptions
- Using omnipay gateway
- Using acquiring

## Sponsor my work!

If you think this package helped you in any way, you can sponsor me! I am a free developer, so your help will be very helpful to me. :blush:

## Deployment

```shell
composer require arhitov/laravel-billing
```

### Preparation

Append ServiceProvider Arhitov\LaravelBilling\Providers\PackageServiceProvider in the config/app.php file to the “providers” block.
Add the BillableInterface interface and the BillableTrait trait to the payment model.

### Configuration setup.

Publish the configuration and make changes as needed. Will create a file "_config/billing.php_".
```shell
php artisan vendor:publish --tag=billing-config
```

### Migration.

Publish the migration and make any necessary changes if necessary. For example, specify the database connection to be used. By default, the default connection is used.
> **_Attention!_** Migration should only be performed after the configuration has been configured.
```shell
php artisan vendor:publish --tag=billing-migrations
```

## Masterminds

The following repositories inspired me:
- laravel/cashier-stripe
I express my gratitude to the authors of the above repositories.

## License

The script is open-sourced software licensed under the [MIT license][link-license].

## Authors

Alexander Arhitov [clgsru@gmail.com](mailto:clgsru@gmail.com)

Welcome here! :metal:

[ico-php-support]: https://img.shields.io/badge/PHP-8.2+-blue.svg
[ico-laravel-support]: https://img.shields.io/badge/Laravel-10.x-blue.svg
[link-laravel-support]: https://laravel.com/docs/10.x/
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg
[link-license]: LICENSE