<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'controller' . DS . 'core.class.php' );

class appController extends coreController
{
	function __construct()
	{
		// 载入默认的
		parent::__construct();
		session_start();
	}

	// login check or something
	
	
}


?>
