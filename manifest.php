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

require('_functions.php');

header('Content-type: text/cache-manifest');

$version = 0;
$dir = dirname($_SERVER['SCRIPT_FILENAME']);
$handle = opendir($dir);

while (false !== ($file = readdir($handle))) {
  if (file_exists("$dir/$file")) {
    $v = filemtime("$dir/$file");
    if ($v > $version) {
      $version = $v;
    }
  }
}

$files = array(
  '.',
  'accelimation.js',
  'base.js',
  'cookies.js',
  'datastore.js',
  'favicon.png',
  'labels.js',
  'login.php',
  'menu.js',
  'note.js',
  'utils.js',
  'xhr.js'
  );

print("CACHE MANIFEST\n");
print("#version  $version\n");

foreach ($files as $file) {
  print("$file\n");
}
?>

NETWORK:
init.php
sync.php
update.php
x.gif
