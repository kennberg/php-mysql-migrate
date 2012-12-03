php-mysql-migrate
======================

Tiny one file migration script for MySQL using PHP, built for the minimalist.

Handles migrating your MySQL database schema upwards. Easy to use in different environments: multiple developers, staging, production. Just run migrate and it will move the database schema to the latest version.

How to use
======================

If you already have a GIT repo for your server, then:

    git submodule add https://github.com/kennberg/php-mysql-migrate php-mysql-migrate
    git submodule init

Or, for installation inside your server directory:

    git clone https://github.com/kennberg/php-mysql-migrate

To add a new migration:

    php php-mysql-migrate/migrate.php add [name-without-spaces]

To migrate to the latest version:

    php php-mysql-migrate/migrate.php migrate

More info
======================

To setup your database information, make sure to run:

    cp config.php.sample config.php
    vim config.php

The database version is tracked locally using file ".version".

The MySQL database link is available using "$link" variable.

Make sure that you use the function "query('SELECT 1')" instead of "mysql\_query" since it handles error reporting.

License
======================
Apache v2. See the LICENSE file.
