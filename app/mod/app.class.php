<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'mod/core.class.php' );

class appMod extends coreMod
{
	function __construct()
	{
		// 载入默认的
		session_start();
		parent::__construct();
	}		
	
}


?>