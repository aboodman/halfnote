<?
require("_database.php");
require("_functions.php");

$id = $_GET['id'];
$version = $_GET['version'];
$content = file_get_contents('php://input');
$success = false;

if (!$id) {
  $rslt = insertNote($content);
  $id = $rslt['id'];
  $version = $rslt['version'];
  $success = true;
}
else {
  $conflict = updateNote($id, $version, $content);

  if ($conflict) {
    $version = $conflict;
  } else {
    $version += 1;
  }
}

setNoteID($id);

if (!$conflict) {
  print "OK\n$id\n$version";
} else {
  print "CONFLICT\n$id\n$version";
}
?>