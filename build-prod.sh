#!/bin/bash

rm -rf dist
mkdir -p dist/public/ui

if [ -d "guard-helper-ui" ]; then
    cd guard-helper-ui
    npm install
    npm run build -- --configuration production --output-path=../dist/public/ui --output-hashing=none
    cd ..
else
    exit 1
fi

if [ -d "guard-helper-laravel" ]; then
    cp -R guard-helper-laravel/app dist/
    cp -R guard-helper-laravel/config dist/
    cp -R guard-helper-laravel/database dist/
    cp -R guard-helper-laravel/routes dist/
    cp -R guard-helper-laravel/bootstrap dist/
    cp -R guard-helper-laravel/vendor dist/
    cp -R guard-helper-laravel/resources dist/
    
    cp guard-helper-laravel/composer.json dist/composer.json
    cp guard-helper-laravel/composer.lock dist/composer.lock
    cp guard-helper-laravel/.env.example dist/.env.example
    cp guard-helper-laravel/artisan dist/artisan
else
    exit 1
fi

mkdir -p dist/storage/logs
mkdir -p dist/storage/framework/cache/data
mkdir -p dist/storage/framework/sessions
mkdir -p dist/storage/framework/testing
mkdir -p dist/storage/framework/views
chmod -R 775 dist/storage

cp guard-helper-laravel/public/index.php dist/public/index.php
cp guard-helper-laravel/public/.htaccess dist/public/.htaccess

if [ -f "main-index.php" ] && [ -f "main-htaccess" ]; then
    cp main-index.php dist/index.php
    cp main-htaccess dist/.htaccess
fi