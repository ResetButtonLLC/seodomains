#!/usr/bin/env bash
cd /var/www/seodomains.promodo.dev
git stash
git pull origin master
php artisan migrate
composer install
php artisan cache:clear
php artisan config:clear