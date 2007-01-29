<?
require("_functions.php");
require("_database.php");

$email = trim($_GET['email']);
$temppass = trim($_GET['token']);
$message = '';

if (!$email || !$temppass) {
  die('Missing $email or $token parameter.');
}

if ($_POST['action'] == 'reset') {
  if (!resetPassword($email, $temppass, $password)) {
    $message = '<p class="error" style="margin:2em 0">System error. Cannot reset password now.</p>';
  }
  else {
    $message = "<p class='ok' style='margin:2em 0'>Ok, your account's been deleted. <a href='/halfnote'>Go create a new one!</a>.</p>";
  }
}
?>
<html>
<head>
<style type="text/css">
<? include("_style_base.php"); ?>
<? include("_style_form.php"); ?>
</style>
<title>halfnote - reset account</title>
<table id="masthead" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>
  <td align="left">
    <b>aaronboodman.com</b> / <b>halfnote</b> / <b>password reset</b>
  </td>
</tr>
</table>
<div style="width:50em; margin:1em;">
<p>Because of the neat-o client-side encryption Halfnote uses, it isn't possible to reset your password without deleting your data. This is the tradeoff for the security of nobody besides you being able to read your notes.

<p>If you really don't remember your password, your only option is to delete your account and create a new one. Alas, this will also delete all your notes.

<p>If you're sure that's what you want to do, click the button below.</p>
<?= $message ?>
<form method="post">
<input type="submit" value="Yes, I really want to delete my account" style="width:auto">
<input type="hidden" name="action" value="reset">
</form>
</div>
</body>
</html>
