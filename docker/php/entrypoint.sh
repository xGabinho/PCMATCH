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

# Esperar a que MySQL esté disponible
echo "⏳ Esperando a que MySQL esté disponible..."
MAX_RETRIES=30
RETRY=0
until php artisan db:monitor --databases=mysql > /dev/null 2>&1 || [ $RETRY -eq $MAX_RETRIES ]; do
    RETRY=$((RETRY + 1))
    echo "   Intento $RETRY/$MAX_RETRIES..."
    sleep 2
done

if [ $RETRY -eq $MAX_RETRIES ]; then
    echo "⚠️  No se pudo conectar a MySQL después de $MAX_RETRIES intentos. Continuando de todas formas..."
else
    echo "✅ MySQL disponible"

    # Ejecutar migraciones
    echo "🗃️  Ejecutando migraciones..."
    php artisan migrate --force

    echo "✅ Migraciones completadas"
fi

# Limpiar y cachear configuración
echo "⚙️  Optimizando configuración..."
php artisan config:cache
php artisan route:cache

echo "🚀 Iniciando PHP-FPM..."
exec php-fpm
