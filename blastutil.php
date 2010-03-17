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


function db_adjust_coords($s)
{
  global $config;

  foreach ($config['blastdbs'] as $currentdb) 
    {

      if ($currentdb['file'] == $s)
	return $currentdb['adjustcoordinates'];
    }

  return false;
}

function db_name_to_file($s)
{
  global $config;

  foreach ($config['blastdbs'] as $currentdb) 
    {
      if ($currentdb['name'] == $s)
	return $currentdb['file'];
    }

}

function db_file_to_name($s)
{
  global $config;

  foreach ($config['blastdbs'] as $currentdb) 
    {
      if ($currentdb['file'] == $s)
	return $currentdb['name'];
    }

  return "Unknown";
}

function dbs_per_prog($p)
{
  global $config;
  $ret = array();

  foreach ($config['blastdbs'] as $currentdb) 
    {
      if (in_array($p,$currentdb['progs']))
	$ret[] = $currentdb['name'];
    }

  return $ret;
}



function parse_gfffile($gene)
{
  global $config;
  $gfffile = $config['cdsgff'];
  
  $f = file($gfffile,FILE_SKIP_EMPTY_LINES);
  
  $cdsforgene = $gene;
  $cdses = array();

  $ids = array($gene);
  $oldids = 0;

  while($ids != $oldids)
    {
      $oldids = $ids;

      // For each line in file
      foreach ($f as $line) 
	{
	  foreach ($ids as $id)
	    {
	      // Go through all IDs we have and check if we have a matching line
	      if (preg_match("(Name|Parent|ID)=$id",$line) > 0)
		{
		  // Get ID
		  $matches = array();
		  preg_match("ID=[^;]*",$line,$matches);
		  
		  $newid = substr($matches[0],3);

		  if (!in_array($newid, $ids))
		    {
		      $ids[] = $newid;
		    }

		  // Tab separated
		  $l = explode("\t", $line);
		  
		  if ($l[0] == $gene && $l[2] == 'CDS') // This line matters to us?
		    {
		      $cdses[] = array_slice($l,3,2);  
		    }
		  
		}
	      
	    }
      
	}
    }

  function cmp($a, $b)
  {
    // Normal handling of first index
    if ($a[0] < $b[0])
      {
	return -1;
      }
    
    if ($a[0] > $b[0])
      {
	return 1;
      }
    
    // First index same? (Probably shouldn't happen, but)
    
    if ($a[1] == $b[1])
      {
	// Same
	return 0;
	  }
    
    return ($a[1] < $b[1]) ? -1 : 1;
  }
  
  
  // Sort so we don't need to go through the entire array 
  usort($cdses, "cmp");            
}

function transform_coordinate($gene,$offset)
{

  static $cdses = 0;
  static $cdsforgene = '';

  if ($gene != $cdsforgene)
    {
      // Don't do this again if we don't have to
      $cdsforgene = $gene;
      $cdses = parse_gfffile($gene);
    }
    
  $i = 0;
  $pos = 0;
  // While what's missing is more than the length of the current
  while ( ($offset-$pos) > ($cdses[$i][1]-$cdses[$i][0]) )
    {
      $pos = $pos+$cdses[$i][1]-$cdses[$i][0];
      $i++;
    }

  // Offseted position = remainder + CDS tart
  $offsetedcoord = $cdses[$i][0]+($offset-$pos);
}

function extron_limits($gene, $offseted_coord)
{

  static $cdses = 0;
  static $cdsforgene = '';

  if ($gene != $cdsforgene)
    {
      // Don't do this again if we don't have to
      $cdsforgene = $gene;
      $cdses = parse_gfffile($gene);
    }

  foreach($cdses as $current_cds)
    {
      if (($offseted_coord >= $current_cds[0]) &&
	  ($offseted_coord <= $current_cds[1]))

	return $current_cds;
    }

  return false;
}


?>
