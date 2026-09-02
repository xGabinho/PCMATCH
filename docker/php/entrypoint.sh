#!/bin/bash
set -e

cd /var/www/html

# Instalar dependencias si no existen (bind mount en dev)
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
    echo "📦 Instalando dependencias de Composer..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Generar APP_KEY solo si no existe o está vacía en .env
if [ -f ".env" ] && grep -qE "^APP_KEY=.+" .env; then
    echo "🔑 APP_KEY ya configurada."
else
    echo "🔑 Generando APP_KEY..."
    php artisan key:generate --force
fi

echo "🚀 Iniciando PHP-FPM..."
exec php-fpm
