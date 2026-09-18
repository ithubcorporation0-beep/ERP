#!/bin/sh
set -e

# Config/route/view caching happens here, at container *start*, rather than
# baked into the image at build time. The image is meant to be deployable
# against any environment's .env (dev, staging, prod each with their own
# DB_HOST/APP_KEY/etc.), and `config:cache` freezes whatever env values are
# present the moment it runs - baking it into the image would freeze
# whatever values happened to be set during `docker build`, not the real
# ones the container is actually launched with.
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

exec "$@"
