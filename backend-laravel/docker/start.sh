#!/bin/bash
set -e

cd /var/www/html

# Instalar dependencias si no existen
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
    echo "📦 Instalando dependencias de Composer..."
    composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
fi

# Generar APP_KEY si no existe
if [ -z "$APP_KEY" ] || grep -q "APP_KEY=$" .env 2>/dev/null; then
    echo "🔑 Generando APP_KEY..."
    php artisan key:generate --force
fi

# Cache de configuración para producción
echo "⚡ Caching configuración..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ejecutar migraciones pendientes
echo "🗄️ Ejecutando migraciones..."
php artisan migrate --force

# Crear symlink de storage si no existe
php artisan storage:link 2>/dev/null || true

# Permisos
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

echo "🚀 Iniciando PHP-FPM y Nginx..."

# Iniciar PHP-FPM en background
php-fpm -D

# Iniciar Nginx en foreground (para que Docker no se cierre)
nginx -g "daemon off;"
