# aedificator
custom command to scaffold from table in laravel

## Installation

Add the package into composer.json while using it with Laravel 8+.

"psr-4": {
    "Veainge\\Aedificator\\": "packages/veainge/aedificator/src/"
}

### Composer Update
After adding the package, run the following command:
```bash
composer update
```

## Configuraton 

(optional) Edit config/app.php:
Add service providers
```bash
Veainge\Aedificator\AedificatorServiceProvider::class,
```
(optional) Run commands to clear cache :
```bash
php artisan config:clear
```
## Run Laravel Command

```bash
php artisan forge:table "your table name";
```
to start the command, only the name of the table is necessary.
you need to already have the migrated table in the database.

### Get command help
```bash
php artisan forge:table --help
```
## Available Options
```bash
php artisan forge:table table_name `OPTION`
```
- `table_name` : "Type the name of the table" *Required
- `--folder=Dashboard` : "placed in a certain folder helps keep you organized"
- `-M` `--model` : "only create the model based on the table"
- `-C` `--controller` : "only create controller based on table"
- `-R` `--request` : "only create request based on table"
- `-NV` `--noviews` : "create a model, controller, request and routes"
- `-VW` `--views` : "only create a views based on table"
- `-L` `--lang=en` : "create files to be translated"
- `-CM` `--components` : "copy necessary components"
- `-NAV` `--navigation` : "only adds one item to the navigation menu"

## License
Laravel Breeze is open-sourced software licensed under the [GNU v3 license](LICENSE.md).
