FROM alpine:3.19


# Éviter les prompts interactifs
ENV DEBIAN_FRONTEND=noninteractive

# Variables d'environnement
ENV STEP_VERSION=0.25.2
ENV STEP_CA_VERSION=0.25.2

# Installation des dépendances de base
RUN apk add --no-cache \
    apache2 \
    apache2-ssl \
    php82 \
    php82-apache2 \
    php82-cli \
    php82-common \
    php82-curl \
    php82-mbstring \
    php82-xml \
    php82-openssl \
    php82-zip \
    php82-ldap \
    php82-bcmath \
    php82-session \
    php82-json \
    php82-ctype \
    curl \
    wget \
    ca-certificates \
    sudo \
    bash \
    vim \
    tzdata \
    && rm -rf /var/cache/apk/*

# Télécharger et installer step-cli (binaire)
RUN wget -O step.tar.gz "https://github.com/smallstep/cli/releases/download/v${STEP_VERSION}/step_linux_${STEP_VERSION}_amd64.tar.gz" && \
    tar -xzf step.tar.gz && \
    mv step_${STEP_VERSION}/bin/step /usr/local/bin/ && \
    chmod +x /usr/local/bin/step && \
    rm -rf step.tar.gz step_${STEP_VERSION}

# Télécharger et installer step-ca (binaire)
RUN wget -O step-ca_linux_amd64.tar.gz "https://dl.smallstep.com/certificates/docs-ca-install/latest/step-ca_linux_amd64.tar.gz" && \
    tar -xzf step-ca_linux_amd64.tar.gz && \
    mv step-ca_linux_amd64/step-ca /usr/local/bin/ && \
    chmod +x /usr/local/bin/step-ca && \
    rm -rf step-ca_linux_amd64

# Vérifier l'installation
RUN step version && step-ca version

# Créer l'utilisateur apache si nécessaire
RUN if ! id -u www-data >/dev/null 2>&1; then \
        #addgroup -g 82 -S www-data && \
        adduser -u 82 -D -S -G www-data www-data; \
    fi

# Créer les répertoires nécessaires
RUN mkdir -p /var/www/localhost/htdocs/ssl-manager \
    /var/step-ca \
    /var/step-ca/certs \
    /var/www/localhost/htdocs/ssl-manager/data \
    /var/www/localhost/htdocs/ssl-manager/data/certificates \
    /var/www/localhost/htdocs/ssl-manager/logs \
    /run/apache2 \
    && chown -R www-data:www-data /var/www/localhost/htdocs/ssl-manager \
    && chown -R www-data:www-data /var/step-ca \
    && chmod -R 755 /var/www/localhost/htdocs/ssl-manager \
    && chmod -R 750 /var/step-ca

# Configuration PHP
RUN sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 10M/' /etc/php82/php.ini && \
    sed -i 's/post_max_size = 8M/post_max_size = 10M/' /etc/php82/php.ini && \
    sed -i 's/max_execution_time = 30/max_execution_time = 300/' /etc/php82/php.ini && \
    sed -i 's/memory_limit = 128M/memory_limit = 256M/' /etc/php82/php.ini

# Configuration Apache
RUN echo "ServerName localhost" >> /etc/apache2/httpd.conf && \
    sed -i 's/#LoadModule rewrite_module/LoadModule rewrite_module/' /etc/apache2/httpd.conf && \
    sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/httpd.conf && \
    sed -i 's/DocumentRoot "\/var\/www\/localhost\/htdocs"/DocumentRoot "\/var\/www\/localhost\/htdocs\/ssl-manager"/' /etc/apache2/httpd.conf && \
    sed -i 's/<Directory "\/var\/www\/localhost\/htdocs">/<Directory "\/var\/www\/localhost\/htdocs\/ssl-manager">/' /etc/apache2/httpd.conf

# Copier la configuration Apache personnalisée
COPY apache-config/ssl-certs-alpine.conf /etc/apache2/conf.d/ssl-certs.conf

# Copier les scripts
COPY scripts/entrypoint-alpine.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Exposer les ports
EXPOSE 80 443

# Variables d'environnement
ENV APACHE_RUN_USER=www-data
ENV APACHE_RUN_GROUP=www-data
ENV APACHE_LOG_DIR=/var/log/apache2
ENV STEP_CA_PATH=/var/step-ca

WORKDIR /var/www/localhost/htdocs/ssl-manager

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/sbin/httpd", "-D", "FOREGROUND"]
