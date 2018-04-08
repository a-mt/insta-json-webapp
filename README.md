
# Exemples

To display the result of a given method (JSON):

    ?=account_getCurrentUser

To display the list of medias:

    ?_=collection_getFeed&collectionId=COLLECTION_ID&_gallery

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

## Require mgp25/instagram-php

https://github.com/mgp25/Instagram-API

    composer require mgp25/instagram-php
