<?
$SITE_SECRET = "kr1kety/&*j0n3s!%4$$4akdf@!";

function getNoteID() {
  global $SITE_SECRET;

  $raw = $_COOKIE['c'];

  if (!$raw) {
    return false;
  }

  $vals = explode('-', $raw);

  if (count($vals) != 2) {
    return false;
  }

  $noteid = $vals[0];
  $hash = $vals[1];

  if ($hash != md5($noteid.'-'.$SITE_SECRET)) {
    return false;
  }

  return $noteid;
}

function setNoteID($noteid) {
  global $SITE_SECRET;

  $hash = md5($noteid.'-'.$SITE_SECRET);
  $val = $noteid.'-'.$hash;

  // store the cookie for 30 days
  setcookie('c', $val, time() + 60*60*24*30, '/');
}

function getNote($id) {
  $id = db_escape($id);

  return firstRow(db_query_get("select 
                                  n.id, 
                                  n.userid,
                                  u.email, 
                                  n.content, 
                                  n.version
                                from 
                                  note n
                                left join
                                  user u on u.id = n.userid
                                where 
                                  n.id = '$id'"));
}

function insertNote($content) {
  $content = db_escape($content);

  $id = db_query_set("insert into note (content, version) values ('$content', 1)");
  $version = firstRow(db_query_get("select version as version from note where id = '$id'"));
  $version = $version['version'];

  return array('id' => $id,
	       'version' => $version);
}

function updateNote($id, $version, $content) {
  $id = db_escape($id);
  $version = db_escape($version);
  $content = db_escape($content);

  $rslt = db_query_set("update note set content='$content', version=version+1 where id='$id' and version='$version'");

  $num_rows = mysql_affected_rows();
  
  if ($num_rows == 0) {
    $version = firstRow(db_query_get("select version from note where id = '$id'"));
    return $version['version'];
  }

  return false;
}

function createAccount($email, $password, $noteid) {
  $email = db_escape($email);
  $password = db_escape($password);
  $noteid = db_escape($noteid);

  $userid = db_query_set("insert into user (email, password) values ('$email', '$password')");

  if (!$userid) {
    return false;
  }

  db_query_set("update note set userid = $userid where id = '$noteid'");
  setNoteID($noteid);

  return true;
}

function login($email, $password) {
  $email = db_escape($email);
  $password = db_escape($password);

  $rslt = firstRow(db_query_get("select 
                                   u.id as id, 
                                   u.password as password,
                                   n.id as noteid
                                 from 
                                   user u,
                                   note n
                                 where 
                                   u.id = n.userid and
                                   u.email = '$email'"));

  if (!$rslt) {
    return 'email';
  }

  if ($rslt['password'] != $password) {
    return 'password';
  }

  setNoteID($rslt['noteid']);
}

function mailPassword($email) {
  global $SITE_SECRET;

  $token = crypt($email . $SITE_SECRET);
  $token = str_replace(array('.','/'), array('-','_'), $token);

  mail($email, 
       'Account reset for Halfnote',
       "Click below to reset your account:\n\n"
       . 'http://' . $_SERVER['SERVER_NAME'] . '/halfnote/resetpass.php?email=' 
       . urlencode($email) . '&token=' . $token . "\n",
       'From: Halfnote <no-reply@aaronboodman.com>\r\n');

  return true;
}

function resetPassword($email, $token, $newpass) {
  global $SITE_SECRET;

  $token = str_replace(array('-','_'), array('.','/'), $token);
  $check = crypt($email . $SITE_SECRET, $token);

  if ($check != $token) {
    return false;
  }

  $newpass = db_escape($newpass);
  $newpass = crypt($newpass);

  db_query_set("update user set password = '$newpass' where email = '$email'");

  $rslt = firstRow(db_query_get("select n.id from note n, user u where u.id = n.userid and u.email = '$email'"));
  setNoteID($rslt['id']);

  return true;
}
?>
