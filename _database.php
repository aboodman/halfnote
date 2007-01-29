<?
$DBI_DATABASE="";
$DBI_USERNAME="";
$DBI_PASSWORD="";
$DBI_HOST="";
$DBI_ERROR = "";
$DBI_HANDLE=0;
$DBI_DEBUG = false;

function db_set_error($error) {
  global $DBI_ERROR;
  $DBI_ERROR = $error;
};

function db_get_error() {
  global $DBI_ERROR;
  return $DBI_ERROR;
};


function db_connect() {
  global $DBI_DATABASE, $DBI_USERNAME, $DBI_PASSWORD, $DBI_HOST, $DBI_ERROR, $DBI_HANDLE;
  if (!$DBI_HANDLE) {
    if (!($DBI_HANDLE = mysql_connect($DBI_HOST, $DBI_USERNAME, $DBI_PASSWORD))) {
      db_set_error(mysql_error());
      return 0;
    }

    if (!mysql_selectdb($DBI_DATABASE, $DBI_HANDLE)) {
      db_set_error(mysql_error());
      mysql_close($DBI_HANDLE);
      $DBI_HANDLE=undef;
      return 0;
    }
  }
  return 1;
};


function db_query($query) {
  global $DBI_HANDLE, $DBI_DEBUG;

  if ($DBI_DEBUG) {
    echo "<!-- $query -->\n";
  }

  db_connect();
  if (!($result = mysql_query($query,$DBI_HANDLE))) {
    db_set_error(mysql_error());
    return;
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

function db_query_get($query='') {
  global $DBI_HANDLE, $DBI_DEBUG;

  if ($DBI_DEBUG) {
    echo "<!-- $query -->\n";
  }

  $i = 0;
  db_connect();

  if (!($result = mysql_query($query,$DBI_HANDLE))) {
    db_set_error(mysql_error());
    //die(db_get_error());
    return false;
  }

  while ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
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

function db_query_set($qry='') {
  if(!db_query($qry)) {
    //die(db_get_error());
    return false;
  } else {
    return mysql_insert_id();
  }
}

function db_close() {
  global $DBI_HANDLE;
  mysql_close($DBI_HANDLE);
  $DBI_HANDLE=null;
};
?>
