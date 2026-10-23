<?php
$s=file_get_contents("resources/views/documents/show.blade.php");
$i=strpos($s,"KB");
echo substr($s,$i-10,25)."\n";
echo bin2hex(substr($s,$i-10,25))."\n";
?>