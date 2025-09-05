FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    nodejs \
    npm

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u 1000 -d /home/farmuser farmuser
RUN mkdir -p /home/farmuser/.composer && \
    chown -R farmuser:farmuser /home/farmuser

# Copy existing application directory contents
COPY . /var/www

# Copy existing application directory permissions
COPY --chown=farmuser:farmuser . /var/www

# Change current user to farmuser
USER farmuser

# Install dependencies
RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
