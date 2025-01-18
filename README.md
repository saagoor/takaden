# Takaden

[![Latest Version on Packagist](https://img.shields.io/packagist/v/saagoor/takaden.svg?style=flat-square)](https://packagist.org/packages/saagoor/takaden)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/saagoor/takaden/run-tests?label=tests)](https://github.com/saagoor/takaden/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/saagoor/takaden/Fix%20PHP%20code%20style%20issues?label=code%20style)](https://github.com/saagoor/takaden/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/saagoor/takaden.svg?style=flat-square)](https://packagist.org/packages/saagoor/takaden)

A Laravel package to implement Bangladeshi payment gateways, i.e Bkash, Nagad, Rocket, Upay & SSLCommerz.

## Installation

You can install the package via composer:

```bash
composer require saagoor/takaden
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="takaden-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="takaden-config"
```

## Usage

Head over to the [documentation](https://mhsagor.gitbook.io/takaden) for more information.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/saagoor/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [MH Sagor](https://github.com/saagoor)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
