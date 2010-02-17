<?php
  // See license.txt
  // 
  // Todo (maybe): Refactor
  // Provide easy linking function (are we to be run in an iframe?)
  // Set title depending on state (searching, x hits and so on).
  // Pick up and provide defaults for parameters/query.

include 'blastconfig.php';
include 'blastutil.php';


if ($_REQUEST['id'] != '' )
  {
    if (!ctype_digit($_REQUEST['id']))
      exiterror("Illegal characters in id!");

    $xmlstr = "";

    if (file_exists("work/done_${_REQUEST['id']}"))
      {
	// Job should be done now?

	$s = stat('work/blastoutput_' . $_REQUEST['id']);
	
	if ($s['size'])
	  {
	    $xmlstr = file_get_contents('work/blastoutput_' . $_REQUEST['id']);
	    $xml = new SimpleXMLElement($xmlstr);
	    
	    $optionsfile = file_get_contents('work/blastoptions_' . $_REQUEST['id']);
	    
	    
	    if (strpos($optionsfile,"query: ") != 0)
	      {
		$formdata = unserialize(base64_decode(substr($optionsfile,
							     strpos($optionsfile,"query: ")+7)));
	      }
	    
	  }

	if (!$xmlstr || !$xml)
	  {

	    print(file_get_contents('blast_header.template.html'));
	    print "<span class='loading'>Sorry, something seems to have gone awry with your search\n" .
	      " did you provide a correct fasta sequence?</span>";
	    print(file_get_contents('blast_footer.template.html'));
	    errorexit("Blast gave errors");
	  }
      }
  }


// Early exit functions first 

// DB query track

if ($_REQUEST['op'] == 'dbs' && $_REQUEST['js'] != '' && $_REQUEST['program'] != '')
  {
    // Should be safe to use $_REQUEST['program'] without additional washing.

    // Return an object with the program (string) and dbs (array).    
    $result = '{ "program":"' . $_REQUEST['program'] . '", "dbs": [' ;

    $dbs = dbs_per_prog($_REQUEST['program']);

    // Build a list of all the dbs: "dbname",
    $dbstring = "";
    foreach($dbs as $db)
      {		
	$dbstring = $dbstring . "\n\"$db\",";
      }
    
    $result = $result . 
      substr($dbstring, 0, -1) .   //Remove the last comma
      "]\n}";
    
    print($result);

    // That's all we want to say
    exit(0);
  }


// Loading track

if ($_REQUEST['op'] == 'loaded' && $_REQUEST['js'] != '')
  {
    // Errors?
    $s = stat('work/errors_' . $_REQUEST['id']);
	
    if ($s['size'] || $xmlstr != '')
      print("1");
    else
      print("0");

    // That's all we want to say
    exit(0);
  }

if ($_REQUEST['op'] == 'loaded' && $_REQUEST['js'] == '')
  {
    $host  = $_SERVER['HTTP_HOST'];
    $uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    $extra = "blastxml.php?op=loaded&id=${_REQUEST['id']}";
    $timer = 3;

    if($xmlstr != '') // Page loaded okay?
      {
	$extra = "blastxml.php?op=render&id=${_REQUEST['id']}";
	$timer = 1;
      }

    header("Refresh: $timer; url=http://$host$uri/$extra");

    print(file_get_contents('blast_header.template.html'));
    print "<span class='loading'>Working, please wait (this page will reload automatically)</span>";
    print(file_get_contents('blast_footer.template.html'));
    exit(0);
  }




// Do we want to actually output the whole page?

if ($_REQUEST['js'] == '' && 
    $_REQUEST['op'] != 'submit' &&
    $_REQUEST['op'] != 'gbrowse') 
  {
    print(file_get_contents('blast_header.template.html'));
  }


if ( $_REQUEST['js'] == '' &&
     $_REQUEST['op'] != 'submit' &&
     $_REQUEST['op'] != 'loaded' &&
     $_REQUEST['op'] != 'gbrowse')

  {    
    include 'blastform.php';
    print '<div id="resultset">';
  }



/******************************
 *
 *
 ******************************/

if ($_REQUEST['op']=='submit') {

  // Handle submit track

  $jobid =  false;
  $count = 0;

  // Make sure we have a working directory
  
  if( !file_exists("work") )
    {
      mkdir("work") || exiterror("Failed to create work directory.");
    }

  while (!$jobid && $count<10) 
    {
      $jobid = rand();
      $inputfile = fopen("work/blastinput_" . $jobid, 'x');
      $count++;

      if (!$inputfile)
	$jobid = false;
    }

  if (!$jobid) 
    exiterror("Problem when creating temporary file.");

  $host  = $_SERVER['HTTP_HOST'];
  $uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
  $extra = 'blastxml.php?op=loaded&id=' . $jobid;


  // Basic sanity
  if ( !in_array( $_REQUEST['program'],
		  $blastprograms ))
    exiterror("Illegal data for program");

  if ( !in_array( $_REQUEST['db'],
		  dbs_per_prog($_REQUEST['program'])))
    exiterror("Unsuitable db " . $_REQUEST['db'] . 
	      " for program " . $_REQUEST['program']);
  
  if ( !in_array( $_REQUEST['matrix'],
		  $matrixvalues ))
    exiterror("Illegal data for matrix");

  if ( !is_numeric( $_REQUEST['expect']) )
    exiterror("Illegal data for expect");
  
  if ( !ctype_digit($_REQUEST['geneticcode']) ||
       intval($_REQUEST['geneticcode'])<1 || 
       intval($_REQUEST['geneticcode'])>15 )
    exiterror("Illegal data for geneticcode");

  if ( !ctype_digit($_REQUEST['dbgeneticcode']) || 
       intval($_REQUEST['dbgeneticcode'])<1 || 
       intval($_REQUEST['dbgeneticcode'])>15 )
    exiterror("Illegal data for dbgeneticcode");

  if ( !ctype_digit($_REQUEST['oofalign']) || 
       intval($_REQUEST['oofalign'])<0 || 
       intval($_REQUEST['oofalign'])>1000 )
    exiterror("Illegal data for oofalign");

  if ( !ctype_digit($_REQUEST['alignments']) ||
       intval($_REQUEST['alignments'])<0 || 
       intval($_REQUEST['alignments'])>500 )
    exiterror("Illegal data for alignments");
  

  

  // Grab the query contents, either from file or from textarea.
  if ($_FILES['queryfile']['size'] > 0)
    $inputdata = file_get_contents($_FILES['queryfile']['tmp_name']);
  else 
    $inputdata = $_REQUEST['querysequence']; 
  

  if (strlen($inputdata) == 0)
    exiterror("No query given!");

  // Write it.
  fwrite($inputfile, $inputdata) || 
    exiterror("Failure while creating temporary file for query.");

  fclose($inputfile);

  if ($_REQUEST['js'] == '')
    {
      // Set redirect header prepared earlier
      header("Location: http://$host$uri/$extra");
    }
  else
    {
      // For AJAX, leave the id for easy pickup.
      print("$jobid");
    }

  if ($_FILES['dbfile']['size'] > 0) 
    $dbopt = ' -subject ' . $_FILES['dbfile']['tmp_name'];
  else
    $dbopt = ' -db ' . db_name_to_file($_REQUEST['db']);

  $matrix = "";
  if (in_array($_REQUEST['program'], $matrixprogs))
    {
      $matrix = " -matrix " . $_REQUEST['matrix'];
    }

  $frameshiftpenalty = "";
  if (in_array($_REQUEST['program'], $frameshiftpenaltyprogs))
    {
      $frameshiftpenalty = " -frame_shift_penalty " . $_REQUEST['oofalign'];
    }

  $gencodes = "";
  if (in_array($_REQUEST['program'], $gencodesprogs))
    {
      $gencodes = " -query_gencode " . $_REQUEST['geneticcode'] .
	" -query_gencode " . $_REQUEST['dbgeneticcode'];
    }
    

  $ungapped = "";
  if ($_REQUEST['ungapped_alignment'] == 'F')
    {
      $ungapped = " -ungapped ";
    }


  $cmd = $_REQUEST['program'] .                 // What to run
    //    " -m7 -O work/blastoutput_$jobid ".         // XML output
    //' -b ' . intval($_REQUEST['alignments']) .   // Number of alignments
    //" -M " . $_REQUEST['matrix']  .              // Matrix
    //' -w ' . intval($_REQUEST['oofalign']) .      // Frame shift penalty
    //' -e ' . doubleval($_REQUEST['expect']) .
    //' -i blastinput_' . $jobid .
    " -outfmt 5 -out work/blastoutput_$jobid " .
    " -num_alignments " . intval($_REQUEST['alignments']) .
    $matrix . 
    $gencodes .
    $frameshiftpenalty .
    $ungapped .
    " -evalue " . doubleval($_REQUEST['expect']) .
    " -query work/blastinput_$jobid " .
    $dbopt .                                         // DB/Subject
    $blastextraparameters[$_REQUEST['program']];     // Pick up any extra options we want to pass for specific programs


  fwrite(fopen("work/blastoptions_$jobid","w"),
	 "cmd: $cmd\n" .
	 "query: ". base64_encode(serialize($_REQUEST))
	 );

  // Run command, move output to done_... afterwards.
  system(escapeshellcmd($cmd) . " 2>work/errors_$jobid >work/output_$jobid && mv work/output_$jobid work/done_$jobid");
 };




/******************************
 *
 * Gbrowse
 ******************************/

if ($_REQUEST['id'] != '' && $_REQUEST['op']=='gbrowse') {

  // Handle gbrowse track

  // Output gff describing the selected hit


  header("Content-type: text/plain");

  $hitnum = $_REQUEST['hitnum'];
  if ($hitnum == '')
    exiterror('Missing hitnum for Gbrowse.');

  $adjust = db_adjust_coords($xml->BlastOutput_db);

  // Make up a unique id.
  $id = "search_" . $_REQUEST['id'] . '_' . $hitnum;
  $qid = "query" . $_REQUEST['id'];

  foreach($xml->BlastOutput_iterations->Iteration as $iter) 
    {
      foreach ($iter->Iteration_hits->Hit as $hit) 
	{
	  if ( $hitnum == $hit->Hit_num )
	    {
	      // Found the right hit.


	      // Fix up reference
	      $ref = $hit->Hit_def;
	      
	      foreach ($gbrowseprefixes as $prefix)
		{
		  // Remove prefixes in array
		  if(strncmp($ref,$prefix,strlen($prefix)) === 0)
		    {
		      // We have a prefix? Remove it
		      $ref = substr($ref,strlen($prefix));
		    }
		}
	      
	      



	      print "[UserBlast]\n" .
		"glyph=segments\n" .
		"strand_arrow=1\n\n";


	      $parts = "reference=$ref\n" .
		'"UserBlast" "' . $hit->Hit_def . '" ' ;

	      $first = 1;
	      foreach($hit->Hit_hsps->Hsp as $hsp)
		{

		  // Insert starting comma if needed
		  if (!$first)
		    {
		      $parts = $parts . ',';
		    }

		  
		  $first = 0;


		  $hf = $hsp->xpath('Hsp_hit-from');
		  $ht = $hsp->xpath('Hsp_hit-to');

		  $from = $hf[0];
		  $to = $ht[0];

		  if($adjust)
		    {
		      // Adjust coordinates per gff file?

		      $diff = $to-$from;

		      $from = transform_coordinates($ref,$from);
		      $to = transform_coordinates($ref,$to);

		      // Compare distance, if it has changed, introns interfere.

		      if (($to-$from) != $diff) 
			{
			  // We cross at least one extron/intron border. Let's
			  // split this hsp.

			  // Insert an extra HSP from from to the end of it's extron
			  $upper = extron_limits($gene,$from);

			  // FIXME: Handle case "not found" (false)

			  $parts = $parts . $from . '..' . $upper[1] . ',';

			  // FIXME: We should probably check and insert HSPs for any
			  // extron in between.

			  // Set $from to the start of the extron $to belongs to.
			  $tmp = extron_limits($gene,$to);
			  $from = $tmp[0];
			}
		    }


		  $parts = $parts . 
		    $from . '..' . $to;
									    
		}
	      

	      // Ok, created all the parts and collected the lengths
	      
	      print ("$parts\n");
     
	    }		
	}
    }
  
  exit();
 };



/******************************
 * "Illustration" track
 *
 ******************************/



// Illustration track

if ($_REQUEST['id'] != '' && $_REQUEST['op']='render' )
  {

    
    // Handle error case first.

    if (!$xml)
      {
	$s = stat('work/errors_' . $_REQUEST['id']);
	
	if ($s['size'] > 0)
	  {
	      print("<pre>" . 
		    file_get_contents('work/errors_'. $_REQUEST['id'] ) .

		    "</pre>");  

	  }

      }

    // Do we have any hits in the output?

    if ( !$xml->BlastOutput_iterations->Iteration->Iteration_hits->Hit )
      {
	print("<div class='nohits'>Sorry, no hits. Try modifying your search parameters!</div>");
      }
    else
      {
	// Misc. info about the query
	print("<div class='syncline'></div>\n"
	      . "<div class='queryinfo'>\n");
	print("Tool: <a href=\"${blasturl}\">" . $xml->BlastOutput_program . " (" . 
	     $xml->BlastOutput_version . ")</a><br/>\n");
    
	print("DB: " . db_file_to_name($xml->BlastOutput_db) . " <br/>\n\n");



	$ql = $xml->xpath('BlastOutput_query-len');
	print("Query length: " . $ql[0] ." letters<br/>\n");

	$host  = $_SERVER['HTTP_HOST'];
	$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
	$extra = "blastxml.php?op=render&amp;id=${_REQUEST['id']}";
	$thisurl ="http://$host$uri/$extra";


	print("Link to these results: <a href=\"$thisurl\">$thisurl</a><br/>\n\n");
	print("</div>\n");
	print("<div class='syncline hitseparator'></div>\n");
	print("<div class='results_explanation'>\n");



 	foreach($xml->BlastOutput_iterations->Iteration as $iter) 
	  {

	    // Seems we can't access this the normal way
	    $qla = $iter->xpath('Iteration_query-len');
	    $ql = $qla[0];

	    // Draw the query graph


	    // Output header structure
	    print("<div class='syncline'></div>  \n" .
		  "<div class='resultsleft'> \n" .
		  "  <span class='hitinfo'>Definition</span> \n" .
		  "  <span class='hitscore'>Score</span> \n" .
		  "  <span class='hite'>E</span> \n" .
		  "  <span class='button viewhsps' style='visibility: hidden;' ></span> \n" .
		  "  <span class='viewhit button' style='visibility: hidden;' >Gbrowse</span> \n" .
		  "</div>\n\n" .	  
		  // Right side
		  "<div class='resultsright'> \n" .

		  "  <div class='leftarrow noarrow'></div> \n" .  // Offset "query" so we have room for an arrow on the left
		  "  <div class='hspgraph' style='width: {$config['arrowlength']}em;'  >  \n" .
		  "     <div class='queryline'>Query</div>  \n" .
		  "  </div>   \n" . 
		  //"  <img alt='Query frame indication, assumed +1' src='greenrarrow.png' class='rightarrow' />   \n". 
		  "  <div class='greenarrow rightarrow'></div> \n" .
		  "  <div class='hsptext'> \n" .
		  "    1 - $ql \n" .
		  "  </div> \n" .
		  "</div> \n\n\n" . 
		  "<div class='syncline hitseparator'></div>");
 


	    foreach ($iter->Iteration_hits->Hit as $hit) 
	      {



		$hitid = $hit->Hit_id;
		$hitdef = $hit->Hit_def;
		$hitacc = $hit->Hit_accession;
		$hitlen = $hit->Hit_len;
		$hitnum = $hit->Hit_num;
	    
		$hitscore=0;
	    
		$hite = INF;

		$rs = '';
		$hspscores = '';

		// Go through all HSPs and calculate best score and e for this hit
		// Also, construct right hand graph.

		foreach($hit->Hit_hsps->Hsp as $hsp)
		  {
		    // Calculate best score and Evalue for this hit
		    if ($hsp->Hsp_score > $hitscore)
		      $hitscore = $hsp->Hsp_score;
		
		    if ($hsp->Hsp_evalue < $hite)
		      $hite = $hsp->Hsp_evalue;


		    // Extract start and beginning for arrows.

		    $tmp = $hsp->xpath('Hsp_query-from');
		    $queryfrom = $tmp[0];
		    $befproc = 1.0*$config['arrowlength']*$tmp[0]/$ql;

		    $tmp = $hsp->xpath('Hsp_query-to');
		    $queryto = $tmp[0];
		    $afterproc =  1.0*$config['arrowlength']*($ql-$tmp[0])/$ql;


		    // We include text about where in the hit we found the match
		    $tmp = $hsp->xpath('Hsp_hit-from');
		    $hitfrom = $tmp[0];

		    $tmp = $hsp->xpath('Hsp_hit-to');
		    $hitto = $tmp[0];

		    $lineproc =  1.0*$config['arrowlength']-$befproc-$afterproc;

		    // Get strand
		    $tmp = $hsp->xpath('Hsp_query-frame');
		    $queryframe = $tmp[0];

		    $tmp = $hsp->xpath('Hsp_hit-frame');
		    $hitframe = $tmp[0];


		    $tmp = $hsp->xpath('Hsp_align-len');
		    $alignlen = $tmp[0];





		    // Set appropiate arrows

		    print("<!-- query  $queryframe  hit $hitframe -->\n\n");
		    if ( ($queryframe+0) == ($hitframe+0) )
		      {
			$arrowbefore = 'noarrow';
			$arrowafter = '';
		      }
		    else
		      {
			$arrowbefore = '';
			$arrowafter = 'noarrow';
		      }

		    // Calculate HSP color, use the same rule as NCBIs visual blast
	
		    $hspscore = $hsp->Hsp_score;
		    $color = "black";

		    if ($hspscore > 40)
		      $color = "blue";

		    if ($hspscore > 50)
		      $color = "lime";
		
		    if ($hspscore > 80)
		      $color = "fuchsia";

		    if ($hspscore > 200)
		      $color = "red";
	


		    //Create right hand graph code.
		
		

		    // Believe it or not, this is much more readable with variables inlined...
		    //
		    // we start with a set of divs to make up the arrows, and put the information text
		    // to the right of that. Finally, we do clear both to force a new line.

		    $rs = $rs . 
		      "  <div style='color: $color;' class='hspgraph'>  \n" .
		      "    <span style='width: ${befproc}em; '  class='before'>&nbsp;</span>  \n"  .
		      "    <div class='${color}arrow leftarrow ${arrowbefore}' ></div> \n" .
		      "    <div style='background: $color; width: ${lineproc}em;' class='hit'></div> \n" .  
		      "    <div class='rightarrow ${color}arrow ${arrowafter}' ></div> \n" .
		      "  </div> \n" . 
		      "<div class='hsptext'>H: ${hitfrom} - ${hitto} <br/> " .
		      "Q: $queryfrom - $queryto </div> " .
		      "<div class='hspseparator syncline'></div>\n\n\n";

		    // Create score boxes - "header"

		    $hspscores = $hspscores . 
		      "<div class='hspsummary hsps${hitnum}'> \n" .
		      "  <div class='hspnum' id='hspinfo${hitnum}_{$hsp->Hsp_num}' " .
		      "       onclick=\"toggle('#comparisionhsp${hitnum}_{$hsp->Hsp_num}'," .
		      "'#toggle${hitnum}_{$hsp->Hsp_num}','&#x25ba;','&#x25bc;');\"> \n" .
		      "    <span class='jsonly' id='toggle${hitnum}_{$hsp->Hsp_num}'>&#x25ba;</span> \n" .
		      "    HSP {$hsp->Hsp_num}   \n".
		      "  </div><div class='hspscore'>  \n" .
		      "    Score: " . sprintf("%.3g",$hspscore) . " \n" .
		      "  </div><div class='hspe'>  \n" .
		      "    E: " . sprintf("%.3g",$hsp->Hsp_evalue) . "  \n" .
		      "  </div><div class='hspidentity'> \n".
		      '    Identity: ' . $hsp->Hsp_identity . '/' . $alignlen. ' = ' . 
		      sprintf("%.3g", $hsp->Hsp_identity / $alignlen) .
		      '  </div><div class="hspstrand"> ' .
		      "    Frames: ${queryframe} / ${hitframe} \n" .
		      "  </div>\n" .
		      "  <div class='syncline'></div> \n" .
		      "  <div class='hspcompare' id='comparisionhsp${hitnum}_{$hsp->Hsp_num}'> \n\n";

		    $lettersperline = 100;

		    // Create sequence alignment box
		    for ($i=0; $i<$alignlen; $i=$i+$lettersperline)
		      {	
		    
			$upperi = min($alignlen, $i+$lettersperline);

			$hspscores = $hspscores .
			  '<div class="sequenceindex">' .
			  ($queryfrom + $i) .
			  '</div >' .
			  '<div class="sequence">' .
			  colorgaps(substr($hsp->Hsp_qseq,$i,$lettersperline)) .
			  '</div>' .
			  '<div class="sequenceindex">' .
			  ($queryfrom + $upperi -1 ) .
			  '</div>' .
			  '<br/>' .
			  '<div class="sequenceindex"></div>' .
			  '<div class="sequence">' . 
			  colorgaps(substr($hsp->Hsp_midline,$i,$lettersperline)) .
			  '</div><br/>' .
			  '<div class="sequenceindex">' .
			  ($hitfrom + $i) .
			  '</div>' .
			  '<div class="sequence sequenceseparator">' . 
			  colorgaps(substr($hsp->Hsp_hseq,$i,$lettersperline)) .
			  '</div>' .
			  '<div class="sequenceindex">' .
			  ($hitfrom + $upperi -1 ) .
			  '</div>' .
			  '<div class="syncline" ></div>';
		     
		      }

		
		    $hspscores = $hspscores . '</div></div>' ;
		  }

		print('<div><div class="resultsleft">');


		print( '  <span class="hitinfo"><span>' . 
		       preg_replace('/[^a-zA-Z0-9 ]/','</span>$0<span>',$hitdef) .
		       '</span>, ' . $hitlen . ' letters</span>');
		print( '  <span class="hitscore">' . sprintf("%.3g",$hitscore) .'</span>');
		print( '  <span class="hite">' . sprintf("%.3g",$hite) . '</span>');



		print("  <span class='viewhsps button jsonly' id='showhsps${hitnum}' " .
		      " onclick=\"toggle('.hsps$hitnum','#showhsps${hitnum}'," .
		      "'Show HSPs','Hide HSPs')\">Show HSPs</span>");




		// gbrowse button
		if ($config['gbrowseurl'] != '' )
		  {
		    // Direct link to hit
		    print("  <span class='viewhit jsonly button' onclick='loadInGbrowse(\"" .
			  "http://$host$uri/blastxml.php?op=gbrowse&amp;id=${_REQUEST['id']}&amp;hitnum=$hitnum" .
			  '","' .
			  $config['gbrowseurl'] . "\")'>\n".
			  "GBrowse</span>");
		  }



		print('<div class="syncline"></div>' );
		print('</div>');  // Closes resultsleft


		// Right side
		print('<div class="resultsright">' . $rs );
		print('</div></div>');
		print ('<div class="hspseparator syncline"></div>');


		print('<div id="hspscore' . $hitnum . '" class="hspscores" >');
		print($hspscores);
		print('</div>');
		print ('<div class="hitseparator syncline"></div>');
	      }
	  }

    

	print("</div>");



      }
  }




/******************************
 *
 *
 ******************************/



if ($_REQUEST['js'] == '')  {
  print("</div>");
  print(file_get_contents('blast_footer.template.html'));  
 }

?>
