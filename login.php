<?
require("_functions.php");
require("_database.php");

$mode = $_POST['mode'];
$email = trim($_POST['email']);
$password = trim($_POST['password']);
$password2 = trim($_POST['password2']);
$noteid = trim($_POST['noteid']);
$message = '';
$reload = false;

function validate() {
  global $email, $password, $password2, $mode, $noteid;

  if ($email == 'email') {
    $email = '';
  }

  if ($password == 'password') {
    $password = '';
  }

  if ($password2 == 'confirm password') {
    $password2 = '';
  }

  if (!$email ||
      $mode != 'forgot' && !$password ||
      $mode == 'create' && !$password2) {
    return '<span class="error">All fields are required.</span>';
  }

  if (strpos($email, "@") === false) {
    return '<span class="error">That\'s not a valid email address.</span>';
  }

  if ($mode == 'create' && $password != $password2) {
    return '<span class="error">Those passwords don\'t match.</span>';
  }
}

if ($mode) {
  $message = validate();

  if (!$message) {
    if ($mode == 'create') {
      if (!$noteid) {
	$rslt = insertNote('');
	$noteid = $rslt['id'];
      }

      if (!createAccount($email, $password, $noteid)) {
	$message = '<span class="error">That email is already taken.</span>';
      } else {
	$reload = true;
      }
    } else if ($mode == 'login') {
      $err = login($email, $password);

      if ($err == 'email') {
	$message = '<span class="error">No user with that email address.</span>';
      } else if ($err == 'password') {
	$message = '<span class="error">Wrong password.</span>';
      } else {
	$reload = true;
      }
    } else if ($mode == 'forgot') {
      if (!mailPassword($email)) {
	$message = '<span class="error">No user with that email address.</span>';
      } else {
	$message = '<span class="ok">OK! Go check your email now.</span>';
      }
    }
  }
}
?>

<? if ($reload) { ?>
<script type="text/javascript">
  parent.location.replace("/halfnote");
</script>
<? 
  exit();
} else if (($mode == 'create' || $mode == 'login') && !$message) { ?>
<script type="text/javascript">
  window.parent.setLoggedInUser("<?= $email ?>");
</script>
<? 
  exit();
} ?>

<html>
<head>
<style type="text/css">
<? include("_style_base.php"); ?>
<? include("_style_form.php"); ?>

a {
  margin-right:0.5em;
}
</style>
</head>
<body style="margin:0;">
<div id="localStore" style="behavior:url(#default#userData);"></div>
<script type="text/javascript" src="schmrypto.js"></script>
<script type="text/javascript" src="accelimation.js"></script>

<div id="head"></div><br>
<?= $message ?>
<div id="clip" style="position:relative; overflow:hidden;" 
  onkeypress="if (event.keyCode == 13 && presubmit()) document.forms[0].submit()">
<input id="email" type="text" label="email" value="<?= $email ?>"><br>
<input id="password" type="password" label="password"><br>
<input id="password2" type="password" label="confirm password">
</div>
<form method="post" style="margin:0" action="login.php" onsubmit="return presubmit()">
<input type="submit" value="OK!" style="width:auto;"><br><br>
<a href="#" onclick="createMode(); return false;">Create account</a
><a href="#" onclick="forgotMode(); return false;">Forgot password</a
><a href="#" onclick="loginMode(); return false;">Log in</a>
<input name="email" type="hidden">
<input name="password" type="hidden">
<input name="password2" type="hidden">
<input name="mode" type="hidden">
<input name="noteid" type="hidden">
</form>

<script type="text/javascript" src="labels.js"></script>
<script type="text/javascript">
  var clip = document.getElementById("clip");
  var fields = clip.getElementsByTagName("input");
  var head = document.getElementById("head");
  var navLinks = document.forms[0].getElementsByTagName("a");
  var form = document.forms[0];
  var mode = "<?= $mode ?>";

  setupLabels(fields);

  if (mode == "" || mode == "create") {
    createMode(true);
  } else if (mode == "login") {
    loginMode(true);
  } else if (mode == "forgot") {
    forgotMode(true);
  }

  function presubmit() {
    try {
      form.noteid.value = window.parent.id;
      form.email.value = document.getElementById("email").value;

      if (form.mode.value == "login") {
	if (parent.t.value != parent.defaultContent && 
	    !confirm("OK to abandon current note?")) {
	  return false;
	}

	form.password.value = hashPass(document.getElementById("password").value);
	form.password2.value = "";
      }

      if (form.mode.value == "create") {
	form.password.value = hashPass(document.getElementById("password").value);
	form.password2.value = hashPass(document.getElementById("password2").value);
      }

      return true;
    } catch (e) {
      alert(e.lineNumber + ": " + e.message);
      return false;
    }
  }

  function hashPass(val) {
    var bytes = Utf8.encode(val);
    var hash = sha1(bytes);
    var encoded = Base64.encode(hash);

    if (typeof globalStorage != "undefined") {
      globalStorage[location.host].setItem("key", hash.join(" "));
    } else {
      localStore.setAttribute("key", hash.join(" "));
      localStore.save("foo");
    }

    return encoded.substring(0, 4);
  }

  function loginMode(fast) {
    head.innerHTML = "<b>Log in</b> to access your notes if you already have an account:";
    form.mode.value = "login";

    navLinks[0].style.display = "";
    navLinks[1].style.display = "";
    navLinks[2].style.display = "none";

    changeMode(1, fast);
  }

  function createMode(fast) {
    head.innerHTML = "<b>Create an account</b> to access this note from other computers:";
    form.mode.value = "create";

    navLinks[0].style.display = "none";
    navLinks[1].style.display = "none";
    navLinks[2].style.display = "";

    changeMode(2, fast);
  }

  function forgotMode(fast) {
    head.innerHTML = "<b>Forgot your password?</b> Click here to reset your account.";
    form.mode.value = "forgot";

    navLinks[0].style.display = "none";
    navLinks[1].style.display = "none";
    navLinks[2].style.display = "";

    changeMode(0, fast);
  }

  function changeMode(elmIdx, fast) {
    for (var i = 0; i < fields.length; i++) {
      if (i <= elmIdx) {
	fields[i].style.display = "";
      } else {
	fields[i].style.display = "none";
      }
    }

    var elm = fields[elmIdx];
    var h = elm.offsetTop + elm.offsetHeight;

    if (fast) {
      clip.style.height = h + "px";
    } else {
      var a = new Accelimation(clip.style, "height", h, 150, 2, "px");
      a.start();
    }
  }
</script>
</body>
</html>