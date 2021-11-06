<?php
/**
 * Helper to print out migration file to stdout.
 *
 * Copyright 2021 Alex Kennberg (https://github.com/kennberg/php-mysql-migrate)
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


if (count($argv) <= 1) {
  echo "Usage:
     To print migration:
         php php-sql-migrate/print.php <file>
     ";
  exit;
}


function query($query) {
  echo "$query\n";
}


query('BEGIN');
include($argv[1]);
query('COMMIT');

