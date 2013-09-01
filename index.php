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

require("_database.php");
require("_functions.php");
require("_layout.php");

$userId = validateUserCookie(true /* redirect if invalid */);

// Handle creating a new note for this user.
$newNoteName = $_GET['new'];
if ($newNoteName) {
  $newNoteName = urldecode($newNoteName);
  $noteData = createOrUpdateName($userId, $newNoteName);
  header('Location: /halfnote/' . $noteData['path']);
}

// The note path is the URL's path, minus the leading slash, minus any
// querystring.
$notePath = preg_replace('/\?.*/', '',
                         substr($_SERVER['REQUEST_URI'],
                                strlen('/halfnote/')));

if ($notePath == '') {
  // By default, we load the most recently updated note.
  $noteData = getMostRecentlyUsedNote($userId);
} else {
  $noteData = getNoteByPath($userId, $notePath);

  // No note with that path. Redirect to default note.
  if (!$noteData) {
    header('Location: /halfnote/');
  }
}

if (!$noteData) {
  createDefaultNote($userId);
  header('Location: /halfnote/');
}

$renameName = $_GET['rename'];
if ($renameName) {
  $renameName = urldecode($renameName);
  $newPath = createOrUpdateName($userId, $renameName, $noteData['id']);
  header('Location: /halfnote/' . $newPath);
}

$delete = $_GET['delete'];
if ($delete) {
  deleteNote($userId, $noteData['id']);
  header('Location: /halfnote/');
}
?>
<!DOCTYPE HTML>
<html>
<head>
<title>Halfnote - <?= $noteData['name'] ?></title>

<meta name="application-name" content="Halfnote">
<meta name="description" content="For when a full notepad is too much">
<meta name="application-url" content="http://aaronboodman.com/halfnote">
<link rel="icon" href="favicon.png" sizes="16x16">
<link rel="chrome-application-definition" href="application">

<style type="text/css">
<? include("_style_base.php"); ?>

body {
  margin:0;
  overflow:hidden;
}

#wrapper {
  position:absolute; 
  left:2px; 
  top:1em; 
  right:2px; 
  bottom:1em; 
  margin-top:8px; 
  margin-bottom:2px;
  border:1px solid #ccc;
}

textarea {
  overflow:auto; 
  width:100%;
  height:100%;
  margin:0;
  padding:0;
  border:0;
}

iframe {
  height:200px;
}

#install a {
  color:red!important;
  font-weight:bold!important;
}

#status {
  position:absolute;
  bottom:0px;
  left:2px;
}

#contact {
  position:absolute;
  bottom:0px;
  right:2px;
}

#title {
  cursor: pointer;
}

#file-menu {
  position:absolute;
  background:#f2f3f4;
  left:2px;
  top:1em;
  margin-top:8px;
  z-index:2;
  padding:0;
  border: 1px solid #ccc;
  visibility:hidden;
}

#file-menu a {
  display: block;
  margin:0;
  padding:0.45em;
  text-decoration:none;
  padding-left: 0;
  padding-right: 2.5em;
}

#file-menu a tt {
  padding-left:0.25em;
  padding-right:0.25em;
}

#file-menu a:hover {
  background:#f5f6f7!important;
}

#file-menu a:last-child {
  border-bottom:none;
}

#file-menu a:hover {
  background: transparent;
}

#file-menu hr {
  margin: 0.4em 0;
  border:none;
  background:#ccc;
  height:1px;
}
</style>

<!--[if IE]>
<style type="text/css">
/*
  IE specific styles: override the definition of the elements to make them
  stretch right on IE's broken layout engine
*/
#wrapper {
  position:static;
  margin-top:1px;
  margin-left:2px;
  width:expression(document.body.offsetWidth - 6);
  height:expression(document.body.offsetHeight - 
		      (document.getElementById("status") ? 
                       document.getElementById("status").offsetHeight : 0) -
		      this.offsetTop - 5);
}

iframe {
  height:expression(document.body.offsetHeight - this.offsetTop);
}
</style>
<![endif]-->

</head>
<body onload="init()">

<? masthead($noteData['name'] . ' &#9662;'); ?>

<div id="file-menu">
<a href="#" id="new-note-item"><tt>&nbsp;</tt>New...</a>
<a href="#" id="rename-note-item"><tt>&nbsp;</tt>Rename...</a>
<a href="#" id="delete-note-item"><tt>&nbsp;</tt>Delete</a>
<?
$notesForUser = getNotesForUser($userId);
if (count($notesForUser) > 1) {
  print "<hr>";
  for ($i = 0; $i < count($notesForUser); $i++) {
    print('<a href="' . $notesForUser[$i]['path'] . '">');
    if ($notesForUser[$i]['id'] == $noteData['id'])
      print('<tt>&#8226;</tt>');
    else
      print('<tt>&nbsp;</tt>');
    print($notesForUser[$i]['name'] . '</a>');
  }
}
?>
</div>

<div id="wrapper"><textarea></textarea></div>

<div id="status" class="green">Ready.</div>
<div id="contact">
  <a href="mailto:halfnote@aaronboodman.com">Contact</a>
</div>

<script>
var noteId = <?= $noteData['id'] ?>;
var noteName = '<?= str_replace(array('\\', '\''), array('\\\\', '\\\''),
                                $noteData['name']) ?>';
</script>

<script src="base.js"></script>
<script src="cookies.js"></script>
<script src="xhr.js"></script>
<script src="utils.js"></script>
<script src="datastore.js"></script>
<script src="note.js"></script>
<script src="menu.js"></script>

</body>
</html>
