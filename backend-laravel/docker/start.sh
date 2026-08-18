#!/bin/bash
set -e

cd /var/www/html

# Ajustar puerto en Nginx según la variable de entorno $PORT (Render asigna $PORT automáticamente, default 10000)
PORT=${PORT:-10000}
echo "🌐 Configurando Nginx para escuchar en el puerto: $PORT"
sed -i "s/listen [0-9]\+;/listen $PORT;/g" /etc/nginx/sites-available/default

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

# Limpieza y cache de configuración para producción
echo "⚡ Caching configuración..."
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ejecutar migraciones pendientes
echo "🗄️ Ejecutando migraciones..."
php artisan migrate --force

# Crear symlink de storage si no existe
php artisan storage:link 2>/dev/null || true

# Permisos de almacenamiento y cache
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

echo "🚀 Iniciando PHP-FPM y Nginx en puerto $PORT..."

# Iniciar PHP-FPM en background
php-fpm -D

# Iniciar Nginx en foreground (para mantener vivo el contenedor)
nginx -g "daemon off;"
