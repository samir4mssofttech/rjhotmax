#!/bin/bash

git checkout main

echo "🔄 Pulling latest changes from remote repository..."

git pull origin main

echo "🟢 Pull complete. Running post-pull deployment tasks..."

# 1. Install/update Composer packages
echo "🔄 Installing Composer dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader

# 2. Install/update NPM dependencies and build assets (if applicable)
if [ -f package.json ]; then
    echo "🔨 Installing NPM dependencies and building assets..."
    npm install --silent
    npm run build
fi

# 3. Publish and optimize Laravel and Filament assets
php artisan vendor:publish --force --tag=livewire:assets
php artisan filament:optimize
php artisan filament:assets
php artisan optimize

# 4. Run database migrations
echo "🗃️ Running database migrations..."
php artisan migrate --force

echo "✅ All post-pull tasks completed!"