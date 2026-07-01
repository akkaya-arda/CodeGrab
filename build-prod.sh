#!/bin/bash

rm -rf dist
mkdir -p dist/guard-helper-backend
mkdir -p dist/public_html
mkdir -p dist/SINGLE-FOLDER-DEPLOYMENT

echo "Cleaning up old dist directory..."
if [ -d "guard-helper-ui" ]; then
    cd guard-helper-ui
    npm install
    npm run build -- --configuration production --output-path=../dist/public_html --output-hashing=none
    cd ..
    
    if [ -d "dist/public_html/browser" ]; then
        echo "Optimizing Angular output structure..."
        cp -R dist/public_html/browser/. dist/public_html/
        rm -rf dist/public_html/browser
    fi
else
    echo "Error: guard-helper-ui directory not found!"
    exit 1
fi

echo "Copying Laravel backend files..."
if [ -d "guard-helper-laravel" ]; then
    cp -R guard-helper-laravel/app dist/guard-helper-backend/
    cp -R guard-helper-laravel/config dist/guard-helper-backend/
    cp -R guard-helper-laravel/database dist/guard-helper-backend/
    cp -R guard-helper-laravel/routes dist/guard-helper-backend/
    cp -R guard-helper-laravel/bootstrap dist/guard-helper-backend/
    cp -R guard-helper-laravel/vendor dist/guard-helper-backend/
    cp -R guard-helper-laravel/resources dist/guard-helper-backend/
    cp guard-helper-laravel/composer.json dist/guard-helper-backend/
    cp guard-helper-laravel/composer.lock dist/guard-helper-backend/
    cp guard-helper-laravel/.env.example dist/guard-helper-backend/..env.example
    cp guard-helper-laravel/artisan dist/guard-helper-backend/
    
    cp -R guard-helper-laravel/. dist/SINGLE-FOLDER-DEPLOYMENT/
    rm -rf dist/SINGLE-FOLDER-DEPLOYMENT/vendor
    rm -rf dist/SINGLE-FOLDER-DEPLOYMENT/.env
    rm -rf dist/SINGLE-FOLDER-DEPLOYMENT/.git
    cp -R dist/guard-helper-backend/vendor dist/SINGLE-FOLDER-DEPLOYMENT/
else
    echo "Error: guard-helper-laravel directory not found!"
    exit 1
fi

mkdir -p dist/guard-helper-backend/storage/logs
mkdir -p dist/guard-helper-backend/storage/framework/cache/data
mkdir -p dist/guard-helper-backend/storage/framework/sessions
mkdir -p dist/guard-helper-backend/storage/framework/testing
mkdir -p dist/guard-helper-backend/storage/framework/views
chmod -R 775 dist/guard-helper-backend/storage

echo "Cleaning up environment database and setup lock files..."
find dist/guard-helper-backend/database -name "*.sqlite" -type f -delete
find dist/SINGLE-FOLDER-DEPLOYMENT/database -name "*.sqlite" -type f -delete
rm -f dist/guard-helper-backend/storage/installed
rm -f dist/SINGLE-FOLDER-DEPLOYMENT/storage/installed

echo "Creating default .env files and injecting cryptographic key..."
for TARGET_DIR in dist/guard-helper-backend dist/SINGLE-FOLDER-DEPLOYMENT; do
    cp guard-helper-laravel/.env.example $TARGET_DIR/.env
    UNIQUE_KEY=$(php -r "echo 'base64:'.base64_encode(random_bytes(32));")
    
    if grep -q "APP_KEY=" $TARGET_DIR/.env; then
        sed -i'' -e "s|^APP_KEY=.*|APP_KEY=${UNIQUE_KEY}|" $TARGET_DIR/.env 2>/dev/null || sed -i "" -e "s|^APP_KEY=.*|APP_KEY=${UNIQUE_KEY}|" $TARGET_DIR/.env
    else
        echo -e "\nAPP_KEY=${UNIQUE_KEY}" >> $TARGET_DIR/.env
    fi
done

echo "Clearing and optimizing Laravel configuration..."
if [ -f "dist/guard-helper-backend/artisan" ]; then
    cd dist/guard-helper-backend
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan event:clear
    php artisan optimize:clear
    cd ../..
fi

echo "Generating dual-folder secure deployment files..."
cat << 'EOF' > dist/public_html/.htaccess
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>
    RewriteEngine On
    <FilesMatch "\.(sqlite|sqlite3|db|env|log)$">
        Order deny,allow
        Deny from all
    </FilesMatch>
    RewriteRule ^(.*)/$ /$1 [L,R=301]
    
    RewriteCond %{REQUEST_URI} ^/?(api|install) [NC]
    RewriteRule ^ index.php [L]
    
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.html [L]
</IfModule>
EOF

cat << 'EOF' > dist/public_html/index.php
<?php
use Illuminate\Http\Request;
define('LARAVEL_START', microtime(true));
if (file_exists($maintenance = __DIR__.'/../guard-helper-backend/storage/framework/maintenance.php')) {
    require $maintenance;
}
require __DIR__.'/../guard-helper-backend/vendor/autoload.php';
$app = require_once __DIR__.'/../guard-helper-backend/bootstrap/app.php';
$handle = $app->make(Illuminate\Contracts\Http\Kernel::class)->handle($request = Request::capture())->send();
EOF

echo "Structuring single-folder deployment using stealth public routing..."

cp -R dist/public_html/. dist/SINGLE-FOLDER-DEPLOYMENT/public/

cat << 'EOF' > dist/SINGLE-FOLDER-DEPLOYMENT/public/index.php
<?php
use Illuminate\Http\Request;
define('LARAVEL_START', microtime(true));
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$handle = $app->make(Illuminate\Contracts\Http\Kernel::class)->handle($request = Request::capture())->send();
EOF

cat << 'EOF' > dist/SINGLE-FOLDER-DEPLOYMENT/public/.htaccess
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>
    RewriteEngine On
</IfModule>
EOF

cat << 'EOF' > dist/SINGLE-FOLDER-DEPLOYMENT/.htaccess
<IfModule mod_rewrite.c>
    RewriteEngine On

    RewriteRule ^\.(env|git) - [F,L]
    RewriteRule ^(database|storage|config|app|routes|bootstrap|vendor)($|/) - [F,L]
    RewriteRule \.(sqlite|sqlite3|db|env|log|json|lock|yaml)$ - [F,L]

    RewriteCond %{REQUEST_URI} ^/?api [NC,OR]
    RewriteCond %{REQUEST_URI} ^/?install [NC]
    RewriteRule ^(.*)$ public/index.php [QSA,L]

    RewriteRule ^$ public/index.php [L]

    RewriteCond %{DOCUMENT_ROOT}/public/$1 -f
    RewriteRule ^(.*)$ public/$1 [L]

    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ public/index.html [L]
</IfModule>
EOF

cat << 'EOF' > dist/SINGLE-FOLDER-DEPLOYMENT/database/.htaccess
Order deny,allow
Deny from all
EOF

cat << 'EOF' > dist/SINGLE-FOLDER-DEPLOYMENT/storage/.htaccess
Order deny,allow
Deny from all
EOF

cat << 'EOF' > dist/SINGLE-FOLDER-DEPLOYMENT/web.config
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <security>
            <requestFiltering>
                <hiddenSegments>
                    <add segment="app" />
                    <add segment="config" />
                    <add segment="database" />
                    <add segment="routes" />
                    <add segment="bootstrap" />
                    <add segment="storage" />
                    <add segment="vendor" />
                </hiddenSegments>
                <fileExtensions>
                    <add fileExtension=".sqlite" allowed="false" />
                    <add fileExtension=".sqlite3" allowed="false" />
                    <add fileExtension=".db" allowed="false" />
                    <add fileExtension=".env" allowed="false" />
                    <add fileExtension=".log" allowed="false" />
                </fileExtensions>
            </requestFiltering>
        </security>
        <rewrite>
            <rules>
                <rule name="Protect Hidden Files" stopProcessing="true">
                    <match url="^.*$" />
                    <conditions logicalGrouping="MatchAny">
                        <add input="{REQUEST_FILENAME}" pattern="\.env$" />
                        <add input="{REQUEST_FILENAME}" pattern="\.sqlite$" />
                    </conditions>
                    <action type="CustomResponse" statusCode="403" statusReason="Forbidden" statusDescription="Access is forbidden." />
                </rule>
                <rule name="Laravel API and Install Routes" stopProcessing="true">
                    <match url="^(api|install)(.*)$" ignoreCase="true" />
                    <action type="Rewrite" url="public/index.php" appendQueryString="true" />
                </rule>
                <rule name="Root Dynamic Tunnel" stopProcessing="true">
                    <match url="^$" />
                    <action type="Rewrite" url="public/index.php" />
                </rule>
                <rule name="Static Assets Pass" stopProcessing="true">
                    <match url="^(.*)$" />
                    <conditions logicalGrouping="MatchAll">
                        <add input="{DOCUMENT_ROOT}/public/{R:1}" matchType="IsFile" />
                    </conditions>
                    <action type="Rewrite" url="public/{R:1}" />
                </rule>
                <rule name="Angular SPA Deep Routes" stopProcessing="true">
                    <match url="^(.*)$" />
                    <conditions logicalGrouping="MatchAll">
                        <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
                        <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
                    </conditions>
                    <action type="Rewrite" url="public/index.html" />
                </rule>
            </rules>
        </rewrite>
    </system.webServer>
</configuration>
EOF

cat << 'EOF' > dist/SINGLE-FOLDER-DEPLOYMENT/index.php
<?php
require_once __DIR__.'/public/index.php';
EOF

touch dist/SINGLE-FOLDER-DEPLOYMENT/database/database.sqlite
mkdir -p dist/SINGLE-FOLDER-DEPLOYMENT/storage/framework/views
chmod -R 775 dist/SINGLE-FOLDER-DEPLOYMENT/storage

echo "Build process completed successfully."