# syntax=docker/dockerfile:1
FROM php:8.3-cli-alpine

# Install system dependencies and Composer
RUN apk add --no-cache git curl unzip && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set work directory
WORKDIR /app

# Copy composer definition and install PHP dependencies
COPY composer.json ./
RUN composer install --no-dev --prefer-dist --no-progress --no-interaction

# Copy project files
COPY . .

# Ensure cache directory exists
RUN mkdir -p app/storage/cache

# Default configuration values (override at runtime with -e/--env)
ENV FS_BASE="https://intl.fusionsolar.huawei.com" \
    FS_USER="changeme" \
    FS_CODE="changeme" \
    MA_PROXY="http://154.70.204.15:3128" \
    CACHE_TTL_SECONDS="90" \
    CACHE_BACKEND="file" \
    FRONTEND_ORIGIN="" \
    APP_VERSION="dev" \
    RATE_LIMIT_PER_MINUTE="0"

EXPOSE 8096

CMD ["php", "-S", "0.0.0.0:8096", "-t", "app", "app/api/index.php"]
