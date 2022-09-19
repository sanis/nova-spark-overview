# A Laravel Nova resource tool to manage your Spark (Stripe) subscriptions

This [Nova](https://nova.laravel.com) tool lets you:

- view a database subscription (subscription name is a parameter)
- view Stripe subscription details
- view invoices for a given subscription with a downloadable link
- change a subscription plan
- cancel a subscription
- resume a subscription
- avoid unnecessary Stripe API call when you load a resource to quickly get a status information and dive deeper if you need it

### Default view of the subscription

![screenshot of the initial Cashier overview tool](https://raw.githubusercontent.com/RhysLees/nova-spark-overview/master/screenshots/initial.png)

### Expanded view of the subscription

![screenshot of the expanded Cashier overview tool](https://raw.githubusercontent.com/RhysLees/nova-spark-overview/master/screenshots/expanded.png)

## Disclaimer

This package has been heavily inspired by [themsaid/nova-spark-manager](https://github.com/themsaid/nova-spark-manager) and was created to be in sync with latest changes in Cashier as well as to optimize default loads by avoiding a Stripe API request unless it's needed. Structure of this repository was inspired by [spatie/skeleton-nova-tool](https://github.com/spatie/skeleton-nova-tool).

## Installation

You can install the nova tool in to a Laravel app that uses [Nova](https://nova.laravel.com) via composer:

```bash
composer require rhyslees/nova-spark-overview
```

Next up, you use the resource tool with Nova. This is typically done in the `fields` method of the desired Nova Resource.

```php
use RhysLees\NovaSparkOverview\Subscription;

// ...

public function fields(Request $request)
{
    return [
        ID::make()->sortable(),

        ...

        Subscription::make(),

        // if you want to display a specific subscription or multiple
        Subscription::make('a-fancy-subscription-name'),

        ...
    ];
}
```

### Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email rudolf@rhyslees.io instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
