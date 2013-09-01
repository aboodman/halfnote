<?
// Copyright 2007, Google Inc.
//
// Redistribution and use in source and binary forms, with or without 
// modification, are permitted provided that the following conditions are met:
//
//  1. Redistributions of source code must retain the above copyright notice, 
//     this list of conditions and the following disclaimer.
//  2. Redistributions in binary form must reproduce the above copyright notice,
//     this list of conditions and the following disclaimer in the documentation
//     and/or other materials provided with the distribution.
//  3. Neither the name of Google Inc. nor the names of its contributors may be
//     used to endorse or promote products derived from this software without
//     specific prior written permission.
//
// THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR IMPLIED
// WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF 
// MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO
// EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, 
// SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
// PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS;
// OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
// WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR 
// OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF 
// ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

// General utils for interacting with mysql from php.

require('_dbconfig.php');

if ($DBI_USERNAME == '') {
  die('Database configuration not set up. Please modify ' .
      '_dbconfig.php to specify a database to use.');
}

$DBI_HANDLE=0;

function db_connect() {
  global $DBI_DATABASE, $DBI_USERNAME, $DBI_PASSWORD, $DBI_HOST, $DBI_HANDLE;
  if (!$DBI_HANDLE) {
    if (!($DBI_HANDLE =
          mysql_connect($DBI_HOST, $DBI_USERNAME, $DBI_PASSWORD))) {
      throw new Exception(mysql_error());
    }

    if (!mysql_selectdb($DBI_DATABASE, $DBI_HANDLE)) {
      throw new Exception(mysql_error());
      mysql_close($DBI_HANDLE);
      $DBI_HANDLE=undef;
    }
  }
  return 1;
};


function db_query($query, $allowErrors) {
  global $DBI_HANDLE, $DBI_DEBUG;

  db_connect();
  if (!($result = mysql_query($query,$DBI_HANDLE))) {
    if (!$allowErrors) {
      throw new Exception(mysql_error());
    } else {
      return 0;
    }
  }
  return $result;
};

function db_escape($string) {
  global $DBI_HANDLE;
  db_connect();
  return mysql_real_escape_string($string, $DBI_HANDLE);
}

function firstRow($res) {
  if(count($res) == 0) {
    return false;
  } else {
    return $res[0];
  }
}

function db_query_get($query) {
  global $DBI_HANDLE, $DBI_DEBUG;

  if ($DBI_DEBUG) {
    echo "<!-- $query -->\n";
  }

  $i = 0;
  db_connect();

  $result = db_query($query, false);  // never allow errors for get queries.
  if (!$result)
    return 0;

  while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
    while (list($key, $value) = each($row)) {
      //echo "$query - Set $key = $value<br />\n";
      $rows[$i][$key] = $value;
    }
    $i++;
  }
  if(isset($rows)) {
    return $rows;
  }
};

function db_query_set($qry, $allowErrors = false) {
  if (!db_query($qry, $allowErrors))
    return 0;
  return mysql_insert_id();
}

function db_close() {
  global $DBI_HANDLE;
  mysql_close($DBI_HANDLE);
  $DBI_HANDLE=null;
};
?>
