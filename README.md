# EVE Online Recruitment App

## Deployment
1. Install requirements: `composer install`
1. Copy `.env.example` to `.env`
1. Assign values in `.env`
1. Point webserver to `/public` directory
1. Run migrations: `php artisan migrate`
1. Update `config/constants.php` to include any additional permissions
1. Run database seeder: `php artisan db:seed`