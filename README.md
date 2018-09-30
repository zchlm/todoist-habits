# Todoist Habits
An automation to enable habit tracking in Todoist Built with [Lumen](https://lumen.laravel.com). Heavily inspired by the Python web service [amitness/habitist](https://github.com/amitness/habitist) with two improvements:

1. Habits are reset when re-scheduled
1. Support for `workday` habits

It integrates Seinfield's "[Don't Break the Chain](https://lifehacker.com/281626/jerry-seinfelds-productivity-secret)" method into [Todoist](https://todoist.com/). Once it's setup, you can forget about it and it works seamlessly.

## Usage

1. Add habits you want to form as tasks on Todoist. Habits can either be scheduled for:
    - `every day`
    - `every workday`

1. Add `[day 0]` to the task

1. When you complete the task, `[day 0]` will become `[day 1]`

1. If you fail to complete the task and it becomes overdue, the script will schedule it to `today` and reset `[day X]` to `[day 0]`

1. **Habits will also reset if they are re-scheduled**

## How it works

1. **Scheduled command:** This command runs every day and increments `[day X]` if a habit is complete, otherwise resets

1. **Resetting re-scheduled habits:** The application uses Todoist webhooks to listen for updated tasks. If a habit's due date has changed, it will reset

## Configuring with Heroku
1. Fork and clone the repo
    ```
    git clone https://github.com/yourgithubusername/todoist-habits
    ```

1. Create a [Todoist app](https://todoist.com/app_console/create_app) and obtain an access token. You can either follow their oAuth authorisation flow using `curl` OR use the provided *Test token*

1. Create a Heroku app + install the Heroku Scheduler & Postgres add-ons
    ```
    heroku create appname
    heroku addons:create scheduler:standard
    heroku addons:create heroku-postgresql
    ```

1. Add the access token & client secret
    ```
     heroku config:set TODOIST_TOKEN='XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
     heroku config:set TODOIST_CLIENT_SECRET='XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
    ```

1. Push the app to Heroku
    ```
    git push heroku master
    ```

1. Finally run `heroku addons:open scheduler` and schedule the command `php artisan todoist-habits` to run daily at midnight

## License
This project is licensed under the MIT License - see the LICENSE file for details
