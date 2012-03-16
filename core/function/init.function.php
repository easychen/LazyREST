<?php

if( !isset($_SERVER['HTTP_APPNAME']) )
{
	function transcribe($aList, $aIsTopLevel = true) 
	{
	   $gpcList = array();
	   $isMagic = get_magic_quotes_gpc();

	   foreach ($aList as $key => $value) {
	       if (is_array($value)) {
	           $decodedKey = ($isMagic && !$aIsTopLevel)?stripslashes($key):$key;
	           $decodedValue = transcribe($value, false);
	       } else {
	           $decodedKey = stripslashes($key);
	           $decodedValue = ($isMagic)?stripslashes($value):$value;
	       }
	       $gpcList[$decodedKey] = $decodedValue;
	   }
	   return $gpcList;
	}

	$_GET = transcribe( $_GET ); 
	$_POST = transcribe( $_POST ); 
	$_REQUEST = transcribe( $_REQUEST );	
	
}

?>