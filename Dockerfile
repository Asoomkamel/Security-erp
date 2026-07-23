FROM php:8.2-fpm

# حزم النظام المطلوبة لامتدادات PHP الشائعة بمشاريع Laravel
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libicu-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo_mysql mbstring bcmath gd zip intl \
    && rm -rf /var/lib/apt/lists/*

# نسخ Composer من الصورة الرسمية بدل تثبيته يدويًا
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
