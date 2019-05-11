FROM composer

WORKDIR /app

COPY . /app

RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

EXPOSE 5000

CMD [ "php", "-S","0.0.0.0:5000/usuarios","./index.php" ]