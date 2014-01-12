<?php
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
         php php-mysql-migrate/migrate.php add <name>
     To migrate your database:
         php php-mysql-migrate/migrate.php migrate [--skip-errors]
     ";
  exit;
}

require_once('config.php');
@define('MIGRATE_VERSION_FILE', '.version');
@define('MIGRATE_FILE_PREFIX', 'migrate-');
@define('MIGRATE_FILE_POSTFIX', '.php');
@define('DEBUG', false);

if (count($argv) <= 1) {
  echo "See readme file for usage.\n";
  exit;
}

// Connect to the database.
if (!@DEBUG) {
  $link = mysql_connect(DBADDRESS, DBUSERNAME, DBPASSWORD);
  if (!$link) {
    echo "Failed to connect to the database.\n";
    exit;
  }
  mysql_select_db(DBNAME, $link);
  mysql_query("SET NAMES 'utf8'", $link);
}

// Find the latest version or start at 0.
$version = 0;
$f = @fopen(MIGRATE_VERSION_FILE, 'r');
if ($f) {
  $version = intval(fgets($f));
  fclose($f);
}
echo "Current database version is: $version\n";

global $link;
global $skip_errors;
$skip_errors = false;

function query($query) {
  global $link;
  global $skip_errors;

  if (@DEBUG) {
    return true;
  }

  echo "Query: $query\n";

  $result = mysql_query($query, $link);
  if (!$result) {
    if ($skip_errors) {
      echo "Query failed: " . mysql_error($link) . "\n";
    }
    else {
      echo "Migration failed: " . mysql_error($link) . "\n";
      echo "Aborting.\n";
      mysql_query('ROLLBACK', $link);
      mysql_close($link);
      exit;
    }
  }
  return $result;
}

function get_migrations() {
  // Find all the migration files in the directory and return the sorted.
  $files = array();
  $dir = opendir(MIGRATIONS_DIR);
  while ($file = readdir($dir)) {
    if (substr($file, 0, strlen(MIGRATE_FILE_PREFIX)) == MIGRATE_FILE_PREFIX) {
      $files[] = $file;
    }
  }
  asort($files);
  return $files;
}

function get_version_from_file($file) {
  return intval(substr($file, strlen(MIGRATE_FILE_PREFIX)));
}

if ($argv[1] == 'add') {
  $new_version = $version;

  // Check the new version against existing migrations.
  $files = get_migrations();
  $last_file = end($files);
  if ($last_file !== false) {
    $file_version = get_version_from_file($last_file);
    if ($file_version > $new_version)
      $new_version = $file_version;
  }

  // Create migration file path.
  $new_version++;
  $path = MIGRATIONS_DIR . MIGRATE_FILE_PREFIX . sprintf('%04d', $new_version);
  if (@strlen($argv[2])) {
    $path .= '-' . str_replace(' ', '-', $argv[2]);
  }
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
  $files = get_migrations();

  $skip_errors = @$argv[2] == '--skip-errors';

  // Check to make sure there are no conflicts such as 2 files under the same version.
  $errors = array();
  $last_file = false;
  $last_version = false;
  foreach ($files as $file) {
    $file_version = get_version_from_file($file);
    if ($last_version !== false && $last_version === $file_version) {
      $errors[] = "$last_file --- $file";
    }
    $last_version = $file_version;
    $last_file = $file;
  }
  if (count($errors) > 0) {
    echo "Error: You have multiple files using the same version. " .
      "To resolve, move some of the files up so each one gets a unique version.\n";
    foreach ($errors as $error) {
      echo "  $error\n";
    }
    exit;
  }

  // Run all the new files.
  $found_new = false;
  foreach ($files as $file) {
    $file_version = get_version_from_file($file);
    if ($file_version <= $version) {
      continue;
    }

    echo "Running: $file\n";
    query('BEGIN');
    include(MIGRATIONS_DIR . $file);
    query('COMMIT');
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

  if ($found_new) {
    echo "Migration complete.\n";
  }
  else {
    echo "Your database is up-to-date.\n";
  }
}

if (!@DEBUG) {
  mysql_close($link);
}

