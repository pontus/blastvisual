<?php

$blasturl = 'http://blast.ncbi.nlm.nih.gov/Blast.cgi?CMD=Web&amp;PAGE_TYPE=BlastDocs&amp;DOC_TYPE=Download';

$blastprograms = array('blastn','blastp','blastx','tblastn','tblastx');

$blastextraparameters = array('blastp' => ' -task blastp',
			      'blastn' => ' -task blastn');


$blastdbs = array( array(name =>'Populus trichocarpa v2 genome',
			 file => 'db/nucleotide/Populus_trichocarpa.v2.masked.fa',
			 adjustcoordinates => false,
			 progs => array('blastn','tblastn','tblastx')),
		   array(name => 'Populus trichocarpa v2 transcripts',
			 file => 'db/nucleotide/Populus_trichocarpa.v2.fa',
			 adjustcoordinates => true,
			 progs => array('blastn','tblastn','tblastx')),
		   array(name => 'Populus trichocarpa v2 peptide',
			 file =>  'db/protein/Populus.trichocarpa.v2.0.peptide.fa',
			 adjustcoordinates => true,
			 progs => array('blastp','blastx')));

$matrixvalues = array('PAM30','PAM70','BLOSUM45','BLOSUM62','BLOSUM80');
$matrixprogs = array('blastp','blastx','tblastn','tblastx');

$gbrowseurl = 'http://130.239.72.85/mgb2/gbrowse/popgeniev2';
$gbrowseprefixes = array('Poptr1.1:');

$frameshiftpenaltyprogs = array('blastx','tblastn');
$gencodesprogs = array('blastx');


$cdsgff = 'db/Populus.trichocarpa.v2.0.gff2';

// Length of arrows in ems;
$arrowlength = 35;

$config = array( 'blastprograms' => $blastprograms,
		 'blastdbs' => $blastdbs,
		 'matrixvalues' => $matrixvalues,
		 'gbrowseurl' => $gbrowseurl,
		 'arrowlength' => $arrowlength,
		 'gencodeprogs' => $gencodesprogs,
		 'gbrowseprefixes' => $gbrowseprefixes,
		 'cdsgff' => $cdsgff,
		 'frameshiftpenaltyprogs' => $frameshiftpenaltyprogs
		 );


?>