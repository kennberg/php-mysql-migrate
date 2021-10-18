php-sql-migrate
======================

Tiny one file migration script for MySQL and PostgreSQL using PHP, built for the minimalist.

Handles migrating your database schema upwards. Easy to use in different environments: multiple developers, staging, production. Just run migrate and it will move the database schema to the latest version. Will detect conficts and print out errors.

How to use
======================

If you already have a GIT repo for your server, then:

    git submodule add https://github.com/kennberg/php-mysql-migrate php-sql-migrate
    git submodule init

Or, for installation inside your server directory:

    git clone https://github.com/kennberg/php-sql-migrate

To add a new migration:

    php php-sql-migrate/migrate.php add [name-without-spaces]

To migrate to the latest version:

    php php-sql-migrate/migrate.php migrate

The migrate script will create a ".version" file in the directory from which it is run. For this reason, I recommend running the migration script from one level up. Do not checkin the version file since it needs to be local!

When you add a new migration, a new script file will be created under the "migrations/" folder and this folder should be checked into the code repository. This way other enviornments can migrate the database using them.

More info
======================

To setup your database information, make sure to run:

    cp config.php.sample config.php
    vim config.php

The database version is tracked locally using file ".version".

The MySQL database link is available using "$link" variable.

Make sure that you use the function "query" instead of "mysql\_query" in the migration scripts, since it handles error reporting.

License
======================
Apache v2. See the LICENSE file.
