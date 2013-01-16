<?php
	include 'PhpBeautifier.inc';
	
	$beautify = new PhpBeautifier();
	$beautify -> tokenSpace = true;//put space between tokens
	$beautify -> blockLine = true;//put empty lines between blocks of code (if, while etc)
	$beautify -> optimize = true;//optimize strings (for now), if a double quoted string does not contain variables of special carachters transform it to a single quoted string to save parsing time
	$beautify -> file( 'test.php', 'beautified.php' );
?>
