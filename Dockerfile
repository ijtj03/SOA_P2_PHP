FROM composer

WORKDIR /app

COPY . /app

RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

EXPOSE 8008

CMD [ "php", "-S","0.0.0.0:8008","./index.php" ]