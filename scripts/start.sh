#!/bin/bash
set -e

# The custom artisan serve command (App\Console\Commands\ServeCommand) starts
# the task scheduler automatically in the background, so no need to launch
# schedule:work separately here.
exec php artisan serve --host=0.0.0.0 --port=5000
