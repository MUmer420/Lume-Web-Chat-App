FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
        libcurl4-openssl-dev \
    && docker-php-ext-install mysqli curl \
    && docker-php-ext-enable mysqli \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN echo "PassEnv SETUP_KEY DB_HOST DB_USER DB_PASS DB_NAME" >> /etc/apache2/apache2.conf

# ==========================================
# Fix for Railway Apache MPM conflict
# ==========================================

# Copy the script into the container's path
COPY docker-entrypoint.sh /usr/local/bin/

# Make the script executable
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Tell Docker to execute our script on startup instead of default Apache
CMD ["/usr/local/bin/docker-entrypoint.sh"]