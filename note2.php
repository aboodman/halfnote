<?
require("_database.php");
require("_functions.php");

function init() {
  $noteid = getNoteID();

  if (!$noteid) {
    return false;
  }

  $note = getNote($noteid);

  if (!$note) {
    return false;
  }

  return $note;
}

$id = '';
$version = '';
$email = '';
$content = 'type here...';
$default_content = $content;
$note = init();

if ($note) {
  $id = $note['id'];
  $userid = $note['userid'];
  $version = $note['version'];
  $content = $note['content'];
  $email = $note['email'];
}
?>
<html>
<title>halfnote</title>
<head>
<style type="text/css">
<? include("_style_base.php"); ?>

.green {
  color:#090;
}

.orange {
  color:#FF7735;
}

.red {
  color:#F00;
}

textarea {
  position:absolute; 
  overflow:auto; 
  left:2px; 
  top:1em; 
  right:200px; 
  bottom:1em; 
  margin-top:8px; 
  margin-bottom:2px; 
  border:1px solid #ccc;
}

iframe {
  height:200px;
}

#loginstatus {
  display:none;
}

body.loggedin textarea {
  right:2px;
}

body.loggedin #login {
  display:none;
}

body.loggedin #loginstatus {
  display:inline;
}

p {
  margin-top:0;
  margin-bottom:1em;
}

</style>

<!--[if IE]>

<style type="text/css">

textarea {
  position:static;
  margin-top:1px;
  margin-left:2px;
  width:expression(document.body.offsetWidth - 205);
  height:expression(document.body.offsetHeight - 
		      (document.getElementById("status") ? 
                       document.getElementById("status").offsetHeight : 0) -
		      this.offsetTop - 5);
}

body.loggedin textarea {
  width:expression(document.body.offsetWidth - 6);
}

iframe {
  height:expression(document.body.offsetHeight - this.offsetTop);
}
</style>

<![endif]-->
</head>
<body style="margin:0; overflow:hidden;" class="<?= $email == '' ? '' : 'loggedin' ?>">

<table id="masthead" width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>
  <td align="left">
    <b>aaronboodman.com</b> / <b>halfnote</b>
  </td>
  <td align="right">
    <span id="loginstatus">Logged in as <b id="email"><?= $email ?></b> 
    | <a href="logout.php" onclick="logout();">logout</a></span> 
  </td>
</tr>
</table>

<textarea 
  onfocus="if (this.value == defaultContent) this.value = ''" 
  onblur="if (this.value == '') this.value = defaultContent"></textarea>

<div id="status" style="position:absolute; bottom:0px; left:2px;" class="green">Ready.</div>
<div id="contact" style="position:absolute; bottom:0px; right:2px;">
<script type="text/javascript">
  document.writeln("<a href='" + "mail" + "to:halfnote" + "@" + "aaronboodman.com'>Contact</a>");
</script>
</div>
<div id="localStore" style="behavior:url(#default#userData);"></div>

<script type="text/javascript" src="schmrypto.js"></script>
<script type="text/javascript">
var id = '<?= $id ?>';
var userId = '<?= $userid ?>';
var version = '<?= $version ?>';
var defaultContent = '<?= $default_content ?>';
var t = document.getElementsByTagName("textarea")[0];
var lastVal = t.value;
var timerid;
var syncTimerId;
var req = null;
var syncReq = null;
var syncTimeout = 1000 * 15; // 15 seconds (!)
var localStore = document.getElementById("localStore");
var v = "<?= str_replace(array("\n", '"'), 
                         array("\\n", '\\"'), 
                         $content) ?>";

function init() {
  if (typeof window.globalStorage == "undefined" && 
      typeof localStore.XMLDocument == "undefined") {
    location.href = "browser.php";
    return;
  }

  if (userId) {
    if (!getKey()) {
      location.href = "logout.php";
      return;
    }

    if (isEncrypted(v)) {
      v = masterDecrypt(v);
    }
  }

  t.value = v;
}

init();

listen(t, "keyup", keypress);
listen(t, "keypress", keypress);
listen(t, "focus", sync);
listen(window, "focus", sync);

scheduleSync();

function listen(elm, ev, fn) {
  if (elm.addEventListener) {
    elm.addEventListener(ev, fn, false);
  } else {
    elm.attachEvent("on"+ev, fn);
  }
}

function keypress() {
  if (t.value != lastVal) {
    if (syncTimerId) {
      syncTimerId = window.clearTimeout(syncTimerId);
    }

    if (timerid) {
      timerid = window.clearTimeout(timerid);
    }

    lastVal = t.value;
    timerid = window.setTimeout(saveChanges, 500);
  }
}

function saveChanges() {
  if (req) {
    // There is a still a request outstanding. Don't start another.
    return;
  }

  var v = t.value;

  if (userId) {
    v = masterEncrypt(v);
  }

  req = getreq();
  req.onreadystatechange = readyStateChange;
  req.open("POST", "update.php?id=" + id + "&version=" + version, true);
  req.send(v);

  setStatus("Saving...", "orange");
}

function readyStateChange() {
  if (req.readyState != 4) {
    return;
  }

  try {
    if (req.status != "200") {
      alert("Error from server\n" + req.status + " " + req.statusText);
      return;
    }
  } catch (e) {
    // if we're closing the window, cannot retrieve any properties.
    return;
  }

  var resp = req.responseText.split("\n");

  req = null;

  if (resp[0] == "OK") {
    id = resp[1];
    version = resp[2];
    setStatus(pickStatus(), "green");
    scheduleSync();
  } else if (resp[0] == "CONFLICT") {
    if (confirm("The note was changed on another machine. Press OK to reload or Cancel ignore.")) {
      location.reload(true);
    } else {
      id = resp[1];
      version = resp[2];
      saveChanges();
    }
  } else {
    alert("Unexpected response from server\n" + resp.join("\n"));
  }
}

function scheduleSync() {
  if (!id) {
    return;
  }

  if (syncTimerId) {
    syncTimerId = window.clearTimeout(syncTimerId);
  }

  syncTimerId = window.setTimeout(sync, syncTimeout);
}

function sync() {
  if (!id || !version) {
    return;
  }

  if (syncReq != null) {
    return;
  }

  setStatus("Syncing...", "orange");
  syncReq = getreq();

  syncReq.onreadystatechange = syncReadyStateChange;
  syncReq.open("GET", "sync.php?id=" + id + "&version=" + version, true);
  syncReq.send(null);
}

function syncReadyStateChange() {
  if (syncReq.readyState != 4) {
    return;
  }

  setStatus(pickStatus(), "green");

  try {
    if (syncReq.status != "200") {
      alert("Error from server\n" + syncReq.status + " " + syncReq.statusText);
      return;
    }
  } catch (e) {
    // if we're closing the window, cannot retrieve any properties.
    return;
  }

  var resp = syncReq.responseText.split("\n");
  syncReq = null;

  if (resp[0] == "OK") {
    scheduleSync();
  } else if (resp[0] == "OUT_OF_DATE") {
    version = resp[1];
    resp.splice(0, 2);
    var v = resp.join("\n");

    if (id) {
      v = masterDecrypt(v);
    }

    t.value = v;
    scheduleSync();
  } else {
    alert("Unexpected response from server\n" + resp.join("\n"));
  }
}

function getreq() {
  try {
    return new XMLHttpRequest();
  } catch (e) {
    try {
      return new ActiveXObject("Msxml2.XMLHTTP");
    } catch (e2) {
      try {
	return new ActiveXObject("Microsoft.XMLHTTP");
      } catch (e3) {
	alert("Your browser is wierd.");
      }
    }
  }

  return null;
}

function setStatus(msg, color) {
  var elm = document.getElementById("status")
  elm.className = color;
  elm.innerHTML = msg;
}


function setLoggedInUser(email) {
  var field = document.getElementById("email");

  while (field.firstChild) {
    field.removeChild(field.firstChild);
  }

  field.appendChild(document.createTextNode(email));

  document.body.className = "loggedin";
}

function pickStatus() {
  var msgs = ["Happy.", "Groovy.", "Okie-dokie.", "Content.", "Sexy.", "Minimal.", "Lovable.", "Efficient.", "Clevah.", "Rad.", "Hellah."];
  return msgs[Math.floor(Math.random() * msgs.length)];
}

function logout() {
  if (typeof globalStorage != "undefined") {
    globalStorage[location.host].setItem("key", "");
  } else {
    localStore.load("foo");
    localStore.setAttribute("key", "");
    localStore.save("foo");
  }
}
</script>

<div id="login" style="position:absolute; right:6px; top:1em; bottom:1em; width:190px; margin-top:10px; margin-bottom:8px; font-size:12px;">
  <p><b style="color:#090">Halfnote</b> is a (very) simple notepad you can access from anywhere. That's it!

  <p>Once you create an account below, your stuff will be strongly encrypted using your password. So feel free to put anything here. There's no way anyone who does not have the password will be able to read it.

<hr style="height:1px; background:#ccc; border:none; margin:2em 0;">

<iframe src="login.php" style="width:100%; border:0;" frameborder="0"></iframe>
</div>

</body>
</html>
