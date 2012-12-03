<?
/**
 * Tiny migrate script for PHP and MySQL.
 *
 * Copyright 2012 Alex Kennberg (https://github.com/kennberg/php-mysql-migrate)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * Initialize your database parameters:
 *    cp config.php.sample config.php
 *    vim config.php
 *
 *  The rest is in the usage report.
 */
if (count($argv) <= 1) {
  echo "Usage:
     To add new migration:
         php php-mysql-migrate/migrate.php add [name-without-spaces]
     To migrate your database:
         php php-mysql-migrate/migrate.php migrate
     ";
  flush();
  exit;
}

define ('MIGRATE_VERSION_FILE', '.version');
define ('MIGRATE_FILE_PREFIX', 'migrate-');
define ('MIGRATE_FILE_POSTFIX', '.php');
require_once('config.php');

if (count($argv) <= 1) {
  echo "See readme file for usage.\n";
  exit;
}

$link = mysql_connect(DBADDRESS, DBUSERNAME, DBPASSWORD);
if (!$link)
  die('Failed to connect to the database');
mysql_select_db(DBNAME, $link);
mysql_query("SET NAMES 'utf8'", $link);

// Find the latest version or start at 0.
$version = 0;
$f = @fopen(MIGRATE_VERSION_FILE, 'r');
if ($f) {
  $version = intval(fgets($f));
  fclose($f);
}
echo "Current database version is: $version\n";

global $link;
function query($query) {
  global $link;
  $result = mysql_query($query, $link);
  if (!$result) {
    flush();
    echo "Migration failed: " . mysql_error($link) . "\n";
    echo "Aborting.\n";
    mysql_query('ROLLBACK', $link);
    mysql_close($link);
    flush();
    exit;
  }
  return $result;
}

if ($argv[1] == 'add') {
  // Create migration file path.
  $version++;
  $path = MIGRATIONS_DIR . MIGRATE_FILE_PREFIX . sprintf('%04d', $version);
  if (@strlen($argv[2]))
    $path .= '-' . $argv[2];
  $path .= MIGRATE_FILE_POSTFIX;

  echo "Adding a new migration script: $path\n";

  $f = @fopen($path, 'w');
  if ($f) {
    fputs($f, "<?php\n\nquery(\$query);\n\n");
    fclose($f);
    echo "Done.\n";
  }
  else {
    echo "Failed.\n";
  }
}
else if ($argv[1] == 'migrate') {
  // Find all the migration files in the directory.
  $files = array();
  $dir = opendir(MIGRATIONS_DIR);
  while ($file = readdir($dir)) {
    if (substr($file, 0, strlen(MIGRATE_FILE_PREFIX)) == MIGRATE_FILE_PREFIX)
      $files[] = $file;
  }
  asort($files);

  // Run all the new files.
  $found_new = false;
  foreach ($files as $file) {
    $file_version = intval(substr($file, strlen(MIGRATE_FILE_PREFIX)));
    if ($file_version <= $version)
      continue;

    echo "Running: $file\n";
    mysql_query('BEGIN', $link);
    flush();
    include(MIGRATIONS_DIR . $file);
    mysql_query('COMMIT', $link);
    flush();
    echo "Done.\n";

    $version = $file_version;
    $found_new = true;

    // Output the new version number.
    $f = @fopen(MIGRATE_VERSION_FILE, 'w');
    if ($f) {
      fputs($f, $version);
      fclose($f);
    }
    else {
      echo "Failed to output new version to " . MIGRATION_VERSION_FILE . "\n";
    }
  }

  if ($found_new)
    echo "Migration complete.\n";
  else
    echo "Your database is up-to-date.\n";
}

mysql_close($link);

