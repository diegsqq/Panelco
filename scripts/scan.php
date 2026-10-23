<?php
$p="resources/views/documents/show.blade.php";
$s=file_get_contents($p);
$chars=[];
for($i=0;$i<mb_strlen($s,'UTF-8');$i++){
  $ch=mb_substr($s,$i,1,'UTF-8');
  if(ord($ch)>127){ $chars[$ch]=($chars[$ch]??0)+1; }
}
foreach($chars as $ch=>$n){ echo "U+".sprintf('%04X',mb_ord($ch,'UTF-8'))."  ".$n."  ".$ch."\n"; }
?>
PY