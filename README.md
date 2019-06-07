# EVE Online Recruitment App

## Deployment
1. Install requirements: `composer install`
1. Copy `.env.example` to `.env`
1. Assign values in `.env`
1. Point webserver to `/public` directory
1. If desired, replace `invTypes.sql` and `invGroups.sql` in `database/dumps`
1. Run migrations: `php artisan migrate`
1. Update `config/constants.php` to include any additional permissions
1. Run database seeder: `php artisan db:seed`
1. Ensure php's `max_execution_time` is set to at least 300
1. Add the following to your crontab:

```* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1```

## Roles
Each recruitment ad (either corporation or group) has two roles associated with it: `{name} recruiter`, and `{name}
director`. The former allows seeing corp members (for corps), and applications. The latter allows managing the ad.

The role `admin` is incredibly powerful, and can assign any role to any character registered on the website.
The role `group admin` allows creation of group ads.

## Code Modification Options
* To add or remove application states, modify `app/Models/Application.php`. **WARNING**: Do not change existing state
  IDs after deployment, since the keys are used as the ID in the database.
* To add custom application warnings, edit `app/Models/Application.php`. Any strings appended to the `$warnings` array
  in the `addWarnings()` function will be rendered on the application page as warnings.