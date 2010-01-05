<?php

function colorgaps($s) 
{
  $s=str_replace(' ','<span class="hspgap"> </span>',$s);
  $s=str_replace('-','<span class="hspgap">-</span>',$s);
  
  return $s;
}

function exiterror($s)
{
  error_log($s);
  exit($s);
}
?>