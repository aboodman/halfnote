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

// Utility functions used by rest of the php pages.


//==============================================================================
// Custom error handling.
//==============================================================================
// Since we're using AJAX, the status code matter to us.
// Usually PHP just returns 200 for everything since it isn't buffering, it 
// too late to change the status by the time it figures out there's a problem.
//
// So, we need to buffer the output and errors we encounter and set the right
// status code if we get an error.
//==============================================================================
function handleError($code, $desc, $file, $line) {
  if ($code == E_NOTICE) {
    return;
  }

  ob_clean();
  header("Content-type: text/plain");
  header("HTTP/1.1 500 Internal Server Error");
  print("ERROR: $code $desc. $file:$line");
  exit();
}

function handleException($exception) {
  ob_clean();
  header("Content-type: text/plain");
  header("HTTP/1.1 500 Internal Server Error");
  $message = $exception->getMessage();
  $file = $exception->getFile();
  $line = $exception->getLine();
  $trace = $exception->getTraceAsString();
  print("ERROR: $message. $file:$line\n$trace");
  exit();
}

ob_start();
set_error_handler("handleError");
set_exception_handler("handleException");


//==============================================================================
// Request param handling.
//==============================================================================

function getRequiredGETParam($name) {
  $value = $_GET[$name];
  if (!$value) {
    throw new Exception("Required GET param '$name' not found");
  }
  return $value;
}

//==============================================================================
// Cookie handling.
//==============================================================================

// TODO(aa): This should be pulled out into configuration.
$SITE_SECRET = "halfnote-returns";

function setUserCookie($userid, $email) {
  global $SITE_SECRET;

  $hash = md5($userid.'-'.$SITE_SECRET);
  $val = $userid.'-'.$hash.'-'.$email;

  // store the cookie for 30 days
  setcookie('c', $val, time() + 60*60*24*30, '/');
}

function validateUserCookie($redirectIfInvalid = false) {
  global $SITE_SECRET;

  $raw = $_COOKIE['c'];

  if (!$raw) {
    handleInvalidUserCookie($redirectIfInvalid);
  }

  $vals = explode('-', $raw);

  if (count($vals) != 3) {
    return false;
  }

  $userid = $vals[0];
  $hash = $vals[1];

  if ($hash != md5($userid.'-'.$SITE_SECRET)) {
    handleInvalidUserCookie($redirectIfInvalid);
  }

  return $userid;
}

function handleInvalidUserCookie($redirectIfInvalid) {
  if ($redirectIfInvalid) {
    header('Location: /halfnote/login.php');
  } else {
    header("HTTP/1.1 403 Forbidden");
  }
  exit();
}


//==============================================================================
// Account management
//==============================================================================

function updateNote($userId, $noteId, $client, $version, $content) {
  $userId = db_escape($userId);
  $noteId = db_escape($noteId);
  $client = db_escape($client);
  $version = db_escape($version);
  $content = db_escape($content);

  // We allow the update if the version the client is sending is the current
  // version OR the client is the last client we spoke with. The last bit is
  // important in the case of flaky connections. Updates might get lost or
  // consolidated, and we want to allow that.
  //
  // We also validate that $userId owns $noteId here, which means that if this
  // fails, we'll report the error as a sync error, not a permission error.
  // But this can only happen if people are trying to screw with us, so fuck em.
  $rslt = db_query_set(
      "update note set
       content='$content', version=version+1, last_client_id='$client' 
       where id = '$noteId' and user_id = '$userId' and
         (last_client_id = '$client' or version = '$version') and
         @last_version := version");
  $num_rows = mysql_affected_rows();
  
  if ($num_rows == 1) {
    $rslt = firstRow(db_query_get("select @last_version + 1 as version"));
    $rslt['conflict'] = false;
  } else {
    $rslt = firstRow(db_query_get(
        "select version, content from note where id = '$noteId'"));
    $rslt['conflict'] = true;
  }

  return $rslt;
}

function createDefaultNote($userId) {
  return db_query_set(
    "insert into note (user_id, name, path, version, content)
     values ('$userId', 'My Notes', 'my-notes', 1, 'Hello, world!')");
}

function createAccount($email, $password) {
  $email = db_escape($email);
  $password = crypt(db_escape($password));
  $userId = db_query_set(
      "insert into user (email, password) 
       values ('$email', '$password')",
      true);  // allow errors (for unique index)

  // $email was already in use
  if (!$userId) {
    return false;
  }

  createDefaultNote($userId);

  setUserCookie($userId, $email);
  return $userId;
}

function login($email, $password) {
  $email = db_escape($email);

  $rslt = firstRow(db_query_get("select id, password from user
                                 where email = '$email'"));

  if (!$rslt) {
    return 'email';
  }

  if ($rslt['password'] != crypt($password, $rslt['password'])) {
    return 'password';
  }

  setUserCookie($rslt['id'], $email);
  return $rslt['id'];
}

function mailPassword($email) {
  global $SITE_SECRET;

  $token = crypt($email . $SITE_SECRET);
  $token = str_replace(array('.','/'), array('-','_'), $token);

  $path = explode('/', $_SERVER['SCRIPT_NAME']);
  array_pop($path);
  array_push($path, 'resetpass.php');

  mail($email, 
       'Account reset for Halfnote',
       "Click below to reset your account:\n\n"
       . 'http://' . $_SERVER['SERVER_NAME'] . implode('/', $path) .
       '?email=' . urlencode($email) . '&token=' . $token . "\n",
       'From: Halfnote <noreply@aaronboodman.com/halfnote>');

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
  $rslt = firstRow(db_query_get("select id from user where email = '$email'"));
  setUserCookie($rslt['id'], $email);

  return true;
}

//==============================================================================
// URL handling
//==============================================================================
function createNoteBaseName($name) {
  $name = preg_replace('/ +/', ' ', trim($name));
  $name = db_escape($name);
  for ($i = 0; $i < strlen($name); $i++) {
    $char = strtolower(substr($name, $i, 1));
    if ($char == ' ')
      $pathBase .= '-';
    else if (preg_match('/[a-z0-9]/', $char))
      $pathBase .= $char;
  }

  return $pathBase;
}

function createOrUpdateName($userId, $name, $noteId = 0) {
  $userId = db_escape($userId);
  $name = db_escape($name);
  $pathBase = createNoteBaseName($name);

  $attempt = 1;
  while (true) {
    $path = $pathBase;
    if ($attempt > 1)
      $path = "$path-$attempt";

    if (!$noteId) {
      $result = db_query_set(
          "insert into note (user_id, name, path, version, content) 
           values ('$userId', '$name', '$path', 1, 'Hello, world!')",
          true);  // allow errors (for path unique index)

      if ($result) {
        return array('id' => $result, 'path' => $path);
      }
    } else {
      db_query_set(
        "update note set name = '$name', path = '$path' 
         where id = '$noteId' and user_id = '$userId'",
         true);  // allow errors (for path unique index)
      if (mysql_affected_rows() == 1)
        return $path;
    }

    // There was already a note with that name.
    if ($attempt > 100) {
      throw new Exception("Too many duplicate names");
    }

    $attempt++;
  }
}

function getNoteByPath($userId, $path) {
  $userId = db_escape($userId);
  $path = db_escape($path);
  return firstRow(db_query_get(
      "select id, name from note where user_id = '$userId' and 
       path = '$path'"));
}

function getMostRecentlyUsedNote($userId) {
  $userId = db_escape($userId);
  return firstRow(db_query_get(
    "select id, name from note where user_id = '$userId' 
     order by updated desc limit 1"));
}

function getNotesForUser($userId) {
  $userId = db_escape($userId);
  $exclude = db_escape($exclude);
  return db_query_get(
      "select id, name, path from note where user_id = '$userId' 
       order by lower(name)");
}

function deleteNote($userId, $noteId) {
  $userId = db_escape($userId);
  $noteId = db_escape($noteId);
  db_query_set("delete from note where id = '$noteId' and user_id = '$userId'");
}
?>
