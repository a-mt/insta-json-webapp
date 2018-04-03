
# Install

## Install php-curl

Check if curl module is available

    ls -la /etc/php5/mods-available/

If it is, enable the curl module

    sudo php5enmod curl

If not, install it

    sudo apt-get update
    sudo apt-get install php5-curl

Restart Apache

    sudo service apache2 restart

## Install composer

Check if you have composer

    composer

If not, install it

    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar composer

## Require instagram-php-scraper

    composer require raiym/instagram-php-scraper

Make private properties protected

    sed -i 's/private /protected /' vendor/raiym/instagram-php-scraper/src/InstagramScraper/Instagram.php
    sed -i 's/new self(/new static(/' vendor/raiym/instagram-php-scraper/src/InstagramScraper/Instagram.php

## Create an Instagram app

- [Manage Client](https://www.instagram.com/developer/clients/manage/) > Register a New Client
- Fill in the form
    - Application name
    - Description
    - Website URL
    - Valid redirect URIs: website URL followed by /login (+ Enter)
- Retrieve your client ID + secret
- Create a `.env` file and put your credentials, the redirect url and a passphrase to secure transactions

      CLIENT_ID="your id"
      CLIENT_SECRET="your secret"
      REDIRECT_URL=".../login"
      PASSPHRASE="your passphrase"
