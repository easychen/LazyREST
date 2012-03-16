<?php
function is_login()
{
	if( isset( $_COOKIE['PHPSESSID'] ) )
	{
		session_start();
		return ss('uid') > 0;
	}
	
	return false;
}

function is_admin()
{
	return ss('ulevel') > 5 ;
}

function rjoin(  $sp , $str , $array )
{
	$ret = array();
	foreach( $array as $key => $value )
	{
		$ret[] = $str.trim($value , $str ).$str;
	}
	
	return join( $sp , $ret );
}



?>