<!-- Form for blast query entry. -->
<!-- Mingled php/html, please refrain from using anything outside of -->
<!-- $config and $formdata -->

<div id="form" class="blastform">
  <form enctype="multipart/form-data"
	action="blastxml.php?op=submit" 
	method="post" 
	id="blastform"
	onsubmit="return submitForm();"> 


<!--
<?php
   print_r($formdata);
?>    
-->   
 
    <div class="paramblock">
      <div class="paramheader">Enter query sequences here in Fasta format</div>
      <textarea name='querysequence' id='querysequence' rows='6' cols='60' ><?php
   print($formdata['querysequence']);
?></textarea>
      <br/>
      Or upload sequence file in fasta format: <input type="file" id='queryfile' name="queryfile" /> 
    </div>

    <div class="paramblock">
      <div class="paramheader">Program</div>
      
      <select id="program" name='program' onchange='formChange();'> 
	<?php
	   foreach ($config['blastprograms'] as $currentprogram)
	   {
	     if ($currentprogram == $formdata['program'])
	       $selected = "selected='selected' ";
	     else
	       $selected = "";

	     print "<option value='$currentprogram' $selected>$currentprogram</option>\n";
	   }
	   ?>
      </select>
    </div>

    <div class="paramblock">
      <div class="paramheader">Database</div>

      <?php
	 print "<select id='db'  name ='db'>\n"; 
	 
	 foreach ($config['blastdbs'] as $currentdb) 
	 {
	   if ($currentdb->name == $formdata['db'])
	       $selected = "selected='selected' ";
	     else
	       $selected = "";

	   print "<option value='" . $currentdb->name . 
	     "' $selected> " . $currentdb->name . "</option> \n";
	 }

	 print "</select>\n\n\n";
	 ?>
      <br/>
      And/or upload sequence fasta file <input type="file" name="dbfile" /> 
    </div> 



    <div class="paramblock" >

	<input type="submit" name="bblast" value="Do search!" id="blastbutton"  />
	<input type="reset" value="Reset" />

    </div>
    
    <hr id="advancedseparator" class="syncline" /> 

    <div class="paramheader" onclick="toggle('#advancedform','#advancedarrow','&#x25ba;','&#x25bc;');">
      <span class="jsonly" id="advancedarrow">&#x25ba;</span>
      Advanced options
    </div>

    <div id="advancedform">
      <!--Seems we need some content inside this box for jquery toggle to work -->
      &nbsp;

      <div class="paramblock">

	<div class="paramheader">
	  Filter
	</div>

	<input type="checkbox" value="L" name="filter" /> Low complexity<br/>
	<input type="checkbox" value="m" name="filter" /> Mask for lookup table only<br/>
	The query sequence is not filtered	<br/>
	for low complexity regions by default.


      </div>

      <div class="paramblock">
	<div class="paramheader">Expect value</div>
	<input type="text" value="<?php 
        {
          print($formdata['expect'] ? $formdata['expect'] : "0.1");
        }
?>" size="5" name ="expect" />
      </div>


      <div class="paramblock  notallprograms blastp blastx tblastn tblastx">
	<div class ="paramheader">Matrix</div>

	<select name ="matrix"> 
	  <?php
	   $default =  $formdata['matrix'] ? $formdata['matrix'] : 'BLOSUM62'; 
	   foreach($config['matrixvalues'] as $currentmatrix)
	   {
	     if ($currentmatrix == $default)
	       $selected = "selected='selected' ";
	     else
	       $selected = "";

	     print("<option $selected>$currentmatrix</option>\n");
	   } 
	     ?>
	</select> 
      </div>

      <div class="paramblock">
	<div class="paramheader">Perform ungapped alignment</div>

	<input type="checkbox" name="ungapped_alignment" value="F" />
      </div>

      <div class="paramblock notallprograms blastx">
	<div id="querygenetic">
	  <div class="paramheader">Query Genetic Codes</div>

	  <select name ="geneticcode"> 
	    <option value ="1"> Standard (1)</option>
	    <option value ="2"> Vertebrate Mitochondrial (2) </option>
	    <option value ="3"> Yeast Mitochondrial (3) </option>
	    <option value ="4"> Mold, Protozoan, and Coelocoel Mitochondrial (4) </option>
	    <option value ="5"> Invertebrate Mitochondrial (5) </option>
	    <option value ="6"> Ciliate Nuclear (6)</option>
	    <option value ="9"> Echinoderm Mitochondrial (9)</option> 
	    <option value ="10"> Euplotid Nuclear (10) </option>
	    <option value ="11"> Bacterial (11) </option>
	    <option value ="12"> Alternative Yeast Nuclear (12) </option>
	    <option value ="13"> Ascidian Mitochondrial (13) </option>
	    <option value ="14"> Flatworm Mitochondrial (14) </option>
	    <option value ="15"> Blepharisma Macronuclear (15) </option>

	  </select> 
	</div>

	<div id="databasegenetic" class="notallprograms tblastn tblastx">
	  <div class="paramheader">
	    Database Genetic Codes 
	  </div>

	  <select name ="dbgeneticcode"> 
	    <option value ="1"> Standard (1)</option>
	    <option value ="2"> Vertebrate Mitochondrial (2)</option>
	    <option value ="3"> Yeast Mitochondrial (3)</option>
	    <option value ="4"> Mold, Protozoan, and Coelocoel Mitochondrial (4) </option>
	    <option value ="5"> Invertebrate Mitochondrial (5)</option>
	    <option value ="6"> Ciliate Nuclear (6)</option>
	    <option value ="9"> Echinoderm Mitochondrial (9)</option>
	    <option value ="10"> Euplotid Nuclear (10)</option>
	    <option value ="11"> Bacterial (11)</option>
	    <option value ="12"> Alternative Yeast Nuclear (12)</option>
	    <option value ="13"> Ascidian Mitochondrial (13)</option>
	    <option value ="14"> Flatworm Mitochondrial (14)</option>
	    <option value ="15"> Blepharisma Macronuclear (15)</option>
	  </select> 

	</div>

	<div id="frameshift" class="notallprograms blastx tblastn">
	  <div class="paramheader">Frame shift penalty</div>
	  <select name ="oofalign"> 
	   <?php
	{
	  $oofs = array(6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,25,30,50,1000,0);
	  $default = $formdata['oofalign'] ? $formdata['oofalign'] : 0;

	  $extra = array(0=>'(no OOF)');

	  foreach($oofs as $currentoof)
	  {
	    if ($currentoof == $default)
	      $selected = "selected='selected' ";
	     else
	       $selected = "";
	    
	    $extratext = $extra[$currentoof];
	    
	    print "<option $selected value='$currentoof'>$currentoof $extratext</option>\n";
	  }
	}
?>
	  </select> 
	</div>
      </div>


      <div class="paramblock">
	<div class="paramheader">Misceallaneous options:</div>


	Alignments:
	<select name ="alignments">
	   <?php
	{
	  $alignments = array(0,10,50,150,250,500);
	  $default = $formdata['alignments'] ? $formdata['alignments'] : 50;

	  foreach($alignments as $currentalignment)
	  {
	    if ($currentalignment == $default)
	      $selected = "selected='selected' ";
	    else
	      $selected = "";
	   	    
	    print "<option $selected value='$currentalignment'>$currentalignment</option>\n";
	  }
	}
	   
?>
	</select> 
      </div>



    </div>

  </form>


</div>
<hr />