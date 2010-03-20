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



  function exoncmp($a, $b)
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
  


function parse_gfffile($gene)
{
  global $config;
  $gfffile = $config['cdsgff'];
  
  $f = file($gfffile,FILE_SKIP_EMPTY_LINES);
  
  $exonsforgene = $gene;
  $exons = array();
  $exonlines = array();

  $ids = array($gene);
  $oldids = 0;
  
  while($ids != $oldids)
    {
      $oldids = $ids;

      // Go through all IDs we have and check if we have a matching line
     
      foreach ($ids as $id)
	{      
	  // For each line in file
	  foreach (preg_grep("/(Name|Parent|ID)=$id/",$f) as $line) 
	    {
	      // Get ID
	      $matches = array();
	      if ( preg_match("/ID=[^;]*/",$line,$matches) )
		{
		  $newid = substr($matches[0],3);
		  
		  if (!in_array($newid, $ids))
		    {
		      $ids[] = $newid;
		    }
		}
	      

	      // Tab separated
	      $l = explode("\t", $line);
	      
	      if ($l[2] == 'CDS' && !in_array($line,$exonlines) ) // This line matters to us?
		{
		  $ref = $l[0];  // Keep track of base reference.
		  $exons[] = array_slice($l,3,2);  
		  $exonlines[] = $line;
		}
	    }
	}
    }

  
  // Sort so we don't need to go through the entire array 
  usort($exons, "exoncmp")
;            
  return(array($exons,$ref));
}

function transform_coordinate($gene,$offset)
{

  static $exons = 0;
  static $exonsforgene = '';

  if ($gene != $exonsforgene)
    {
      // Don't do this again if we don't have to
      $exonsforgene = $gene;
      $exons = parse_gfffile($gene);
    }
    
  $base = $exons[1];
  $i = 0;
  $pos = 0;
  // While what's missing is more than the length of the current
  while ( ($offset-$pos) > ($exons[0][$i][1]-$exons[0][$i][0]) )
    {
      $pos = $pos+$exons[0][$i][1]-$exons[0][$i][0];
      $i++;
    }

  // Offseted position = remainder + CDS start
  $offsetedcoord = $exons[0][$i][0]+($offset-$pos);
  return array($base,$offsetedcoord);
}

function extron_limits($gene, $offseted_coord)
{

  static $exons = 0;
  static $exonsforgene = '';

  if ($gene != $exonsforgene)
    {
      // Don't do this again if we don't have to
      $exonsforgene = $gene;
      $exons = parse_gfffile($gene);
    }

  foreach($exons[1] as $current_exon)
    {
      if (($offseted_coord >= $current_exon[0]) &&
	  ($offseted_coord <= $current_exon[1]))

	return $current_exon;
    }

  return false;
}


?>
