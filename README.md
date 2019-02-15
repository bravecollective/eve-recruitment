# EVE Online Recruitment App

## Deployment
1. Install requirements: `composer install`
1. Copy `.env.example` to `.env`
1. Assign values in `.env`
1. Point webserver to `/public` directory
1. Run migrations: `php artisan migrate`
1. Update `config/constants.php` to include any additional permissions
1. Run database seeder: `php artisan db:seed`
1. 

## Roles
The roles system is a bit strange.

Each recruitment ad (either corporation or group) has two roles associated with it: `{name} recruiter`, and `{name}
director`. The former allows seeing corp members (for corps), and applications. The latter allows managing the ad.

The role `admin` is incredibly powerful, and can assign any role to any character registered on the website.