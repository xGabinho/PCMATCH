#!/bin/bash
set -e

cd /var/www/html

# Instalar dependencias si no existen (bind mount en dev)
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
    echo "📦 Instalando dependencias de Composer..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Generar APP_KEY si no existe
if [ -z "$APP_KEY" ] || grep -q "APP_KEY=$" .env 2>/dev/null; then
    echo "🔑 Generando APP_KEY..."
    php artisan key:generate --force
fi

echo "🚀 Iniciando PHP-FPM..."
exec php-fpm
