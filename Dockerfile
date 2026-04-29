FROM php:8.2-cli

# Install system dependencies and the mysqli extension (used by config.php)
RUN apt-get update && apt-get install -y --no-install-recommends \
        unzip \
    && docker-php-ext-install mysqli \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy project files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Railway injects PORT at runtime; expose it as a hint
ENV PORT=8080
EXPOSE $PORT

CMD ["sh", "start.sh"]
