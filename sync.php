<?
require("_functions.php");
require("_database.php");

$id = $_GET['id'];
$version = $_GET['version'];

if (!$id) {
  die('Where is $id?');
}

if (!$version) {
  die('Where is $version?');
}

$id = db_escape($id);
$version = db_escape($version);
$rslt = firstRow(db_query_get("select version, content from note where id = '$id' and version > '$version'"));

if (!$rslt) {
  print "OK";
} else {
  $version = $rslt['version'];
  $content = $rslt['content'];

  print "OUT_OF_DATE\n$version\n$content";
}
?>