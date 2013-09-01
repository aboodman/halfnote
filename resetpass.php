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

require("_functions.php");
require("_database.php");
require("_layout.php");

$email = trim($_GET['email']);
$temppass = trim($_GET['token']);
$message = '';
$success = false;

if (!$email || !$temppass) {
  die('Missing $email or $token parameter.');
}

if ($_POST['action'] == 'reset') {
  $password = trim($_POST['password']);
  $password2 = trim($_POST['password2']);
  if ($password != $password2) {
    $message = '<p class="error" style="margin:2em 0">The two passwords are not
        the same.</p>';
  } else if (!resetPassword($email, $temppass, $password)) {
    $message = '<p class="error" style="margin:2em 0">System error. Cannot 
        reset password now.</p>';
  } else {
    $message = "<p class='ok'>Ok, your password has been 
        reset. <a href='/halfnote/'>Go to your note</a>.</p>";
    $success = true;
  }
}
?>
<html>
<head>
<style type="text/css">
<? include("_style_base.php"); ?>
<? include("_style_form.php"); ?>
</style>
<title>Halfnote - Reset Account</title>

<? masthead('Reset Account'); ?>

<div style="width:50em; margin:1em;">
<p>Enter your new desired password below.</p>
<?= $message ?>
<? if (!$success) { ?>
<form method="post">
  <input type="hidden" name="action" value="reset">
  <table cellpadding="0" cellspacing="0" border="0">
    <tr>
      <td align="left" valign="middle">Password:&nbsp;</td>
      <td align="left" valign="middle"><input type="password" name="password"></td>
    </tr>
    <tr>
      <td align="left" valign="middle">Once more:&nbsp;</td>
      <td align="left" valign="middle"><input type="password" name="password2"></td>
    </tr>
  </table>

  <input type="submit" value="OK" style="width:auto">
</form>
<? } ?>
</div>
</body>
</html>
