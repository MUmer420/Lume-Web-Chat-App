FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
        libcurl4-openssl-dev \
    && docker-php-ext-install mysqli curl \
    && docker-php-ext-enable mysqli \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Pass all variables down into Apache runtime instances (Including TRANSLATOR_URL)
RUN echo "PassEnv SETUP_KEY TRANSLATOR_URL MYSQL_URL DB_HOST DB_USER DB_PASS DB_NAME" >> /etc/apache2/apache2.conf

# Move your project files into the Apache document root
COPY . /var/www/html/

# ==========================================
# Fix for Railway Apache MPM conflict
# ==========================================

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

CMD ["/usr/local/bin/docker-entrypoint.sh"]