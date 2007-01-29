<?
print("PHP: " . sha1("abc"));
?>
<br>
<script language="javascript" src="schmrypto.js"></script>
<script language="javascript">
    var t0 = new Date().getTime();
    var userId = "12";
    var id = "33";
    var ct = masterEncrypt("hello, world. this is just a note! hello, world. this is just a note! hello, world. this is just a note! hello, world. this is just a note! hello, world. this is just a note! hello, world. this is just a note! hello, world. this is just a note! hello, world. this is just a note! hello, world. this is just a note! hello, world. this is just a note! hello, world. this is just a note! ");
    document.writeln(ct + "<br>");
    document.writeln(masterDecrypt(ct));
    var t1 = new Date().getTime();
    alert(t1 - t0);
</script>
