# phpstats

The website for PHPstats, a family of PHP 8.5 packages for statistical work:
phit, phibble, phplyr, phpolars, and cmdstanphp.

## Setup

Requires PHP 8.5, Composer, and Node. The vignettes also need the `phit`
extension.

```sh
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

The seeder loads the phit example datasets from `database/data`.
