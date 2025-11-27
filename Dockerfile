FROM ubuntu:22.04

# Éviter les prompts interactifs
ENV DEBIAN_FRONTEND=noninteractive

# Installation des dépendances
RUN apt-get update && apt-get install -y \
    apache2 \
    php8.1 \
    php8.1-cli \
    php8.1-common \
    php8.1-curl \
    php8.1-mbstring \
    php8.1-xml \
    php8.1-zip \
    php8.1-ldap \
    php8.1-bcmath \
    libapache2-mod-php8.1 \
    curl \
    wget \
    gnupg \
    ca-certificates \
    sudo \
    vim \
    && rm -rf /var/lib/apt/lists/*

# Installer step-ca (Smallstep CA)
RUN wget -O step-cli.deb https://dl.smallstep.com/gh-release/cli/gh-release-header/v0.25.0/step-cli_0.25.0_amd64.deb && \
    wget -O step-ca.deb https://dl.smallstep.com/gh-release/certificates/gh-release-header/v0.25.0/step-ca_0.25.0_amd64.deb && \
    dpkg -i step-cli.deb step-ca.deb && \
    rm step-cli.deb step-ca.deb

# Activer les modules Apache
RUN a2enmod rewrite php8.1 ssl headers

# Créer les répertoires nécessaires
RUN mkdir -p /var/www/html/ssl-manager \
    /var/step-ca \
    /var/step-ca/certs \
    /var/www/html/ssl-manager/data \
    /var/www/html/ssl-manager/data/certificates \
    /var/www/html/ssl-manager/logs

# Définir les permissions
RUN chown -R www-data:www-data /var/www/html/ssl-manager \
    && chown -R www-data:www-data /var/step-ca \
    && chmod -R 755 /var/www/html/ssl-manager \
    && chmod -R 750 /var/step-ca

# Copier la configuration Apache
COPY apache-config/ssl-certs.conf /etc/apache2/sites-available/ssl-certs.conf

# Activer le site
RUN a2dissite 000-default.conf && \
    a2ensite ssl-certs.conf

# Copier les scripts
COPY scripts/entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

# Exposer le port
EXPOSE 80 443

# Variables d'environnement
ENV APACHE_RUN_USER=www-data
ENV APACHE_RUN_GROUP=www-data
ENV APACHE_LOG_DIR=/var/log/apache2
ENV STEP_CA_PATH=/var/step-ca

WORKDIR /var/www/html/ssl-manager

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2ctl", "-D", "FOREGROUND"]
