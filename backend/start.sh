#!/bin/bash
# Script de inicio para el contenedor Laravel
# Espera a que MySQL esté disponible antes de arrancar

set -e

echo "⏳ Esperando a que MySQL esté listo..."

# Esperar hasta 60 segundos para que MySQL responda
MAX_TRIES=30
TRIES=0

until php -r "new PDO('mysql:host=${DB_HOST:-erp_mysql};port=${DB_PORT:-3306};dbname=${DB_DATABASE:-erp_db}', '${DB_USERNAME:-root}', '${DB_PASSWORD:-root}');" 2>/dev/null; do
    TRIES=$((TRIES + 1))
    if [ $TRIES -ge $MAX_TRIES ]; then
        echo "❌ MySQL no respondió después de ${MAX_TRIES} intentos. Abortando."
        exit 1
    fi
    echo "   MySQL no disponible todavía (intento $TRIES/$MAX_TRIES)... esperando 2s"
    sleep 2
done

echo "✅ MySQL listo."

# Limpiar cachés para desarrollo
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

echo "🚀 Iniciando Laravel en 0.0.0.0:8000..."
exec php artisan serve --host=0.0.0.0 --port=8000
