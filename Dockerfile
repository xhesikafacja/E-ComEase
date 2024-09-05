# Use the official Ubuntu base image
FROM ubuntu:latest

# Set environment variables to non-interactive
ENV DEBIAN_FRONTEND=noninteractive

# Update the system
RUN apt-get update -y

# Install software-properties-common and add ondrej/php PPA
RUN apt-get install -y software-properties-common
RUN add-apt-repository ppa:ondrej/php

# Update the system
RUN apt-get update -y

# Install Apache, PHP, SQLite and necessary extensions
RUN apt-get install -y apache2 php8.2 libapache2-mod-php8.2 sqlite3 php8.2-sqlite3 php8.2-dom php8.2-curl

# Install Composer
RUN apt-get install -y curl zip unzip
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy the current directory contents into the container at /var/www/html/
COPY html /var/www/html/

# Run Composer Install
WORKDIR /var/www/html/
RUN composer install

# Expose port 80
EXPOSE 80

# Start Apache service
CMD ["/usr/sbin/apache2ctl", "-D", "FOREGROUND"]