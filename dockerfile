# Используем официальный образ PHP с Apache
FROM php:8.1-apache

# Устанавливаем необходимые расширения PHP
RUN docker-php-ext-install mysqli

# Включаем модуль mod_rewrite
RUN a2enmod rewrite

# Настраиваем поддержку .htaccess
RUN echo '<Directory /var/www/html/>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n' >> /etc/apache2/sites-available/000-default.conf

# Копируем файлы проекта в контейнер
COPY . /var/www/html/

# Устанавливаем права для .htaccess (если он есть)
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Открываем порт 80 для веб-сервера
EXPOSE 80
