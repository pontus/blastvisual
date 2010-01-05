<?php

$blasturl = 'http://blast.ncbi.nlm.nih.gov/Blast.cgi?CMD=Web&amp;PAGE_TYPE=BlastDocs&amp;DOC_TYPE=Download';

$blastprograms = array('blastn','blastp','blastx','tblastn','tblastx');

$blastextraparameters = array('blastp' => ' -task blastp',
			      'blastn' => ' -task blastn');


$blastdbs = array('P. trichocarpa v1.1 genome',
		  'P. trichocarpa v1.1 transcripts',
		  'P. trichocarpa v1.1 proteins');

$matrixvalues = array('PAM30','PAM70','BLOSUM45','BLOSUM62','BLOSUM80');
$matrixprogs = array('blastp','blastx','tblastn','tblastx');

$gbrowseurl = 'http://www.popgenie.db.umu.se/cgi-bin/gbrowse/';
$gbrowseprefixes = array('Poptr1.1:');


// Length of arrows in ems;
$arrowlength = 35;

$config = array( 'blastprograms' => $blastprograms,
		 'blastdbs' => $blastdbs,
		 'matrixvalues' => $matrixvalues,
		 'gbrowseurl' => $gbrowseurl,
		 'arrowlength' => $arrowlength
		 );

$dbsperprog = array('blastn' => array('P. trichocarpa v1.1 genome',
				      'P. trichocarpa v1.1 transcripts'),
		    'blastp' => array('P. trichocarpa v1.1 proteins'),
		    'blastx' => array('P. trichocarpa v1.1 proteins'),
		    'tblastn' => array('P. trichocarpa v1.1 genome',
				       'P. trichocarpa v1.1 transcripts'),
		    'tblastx' => array('P. trichocarpa v1.1 genome',
				       'P. trichocarpa v1.1 transcripts') );

$dbtofile = array('P. trichocarpa v1.1 genome' => 'db/nucleotide/poplar.masked.fasta',
		  'P. trichocarpa v1.1 transcripts' => 'db/nucleotide/transcripts.Poptr1_1.JamboreeModels.fasta',
		  'P. trichocarpa v1.1 proteins' => 'db/protein/proteins.Poptr1_1.JamboreeModels.fasta'
		  );

?>