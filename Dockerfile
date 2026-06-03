FROM php:8.3-apache

# Dependências
RUN apt-get update && apt-get install -y \
        libpq-dev \
        libzip-dev \
        unzip \
        curl \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

# Apache: habilita mod_rewrite e headers
RUN a2enmod rewrite headers

# Apache config
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Arquivos da aplicação
WORKDIR /var/www/html
COPY . .

# Permissões
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && find /var/www/html -type d -exec chmod 755 {} \;

EXPOSE 80
