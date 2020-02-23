#!/bin/bash

printf '#### GIT PULL ####\n'
ssh p5chi 'cd ~/p5chi_auf/pentabee-api && docker-compose exec php-fpm git pull'

printf '\n\n\n#### COMPOSER ####\n'
ssh p5chi 'cd ~/p5chi_auf/pentabee-api && docker-compose exec php-fpm composer install'

printf '\n\n#### MIGRATIONS ####\n'
ssh p5chi 'cd ~/p5chi_auf/pentabee-api && docker-compose exec php-fpm bin/console doctrine:migrations:migrate -n'

# printf '\n\n\n#### FIXTURES ####'
# ssh p5chi 'cd ~/p5chi_auf/pentabee-api && docker-compose exec php-fpm bin/console doctrine:fixtures:load -n'
