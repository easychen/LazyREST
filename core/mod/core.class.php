<?php
if( !defined('IN') ) die('bad request');


class coreMod 
{
	function __construct()
	{
		// load datafunction
		
		$data_function_file = AROOT . 'function/' . g('m') . '.function.php';
		if( file_exists( $data_function_file ) )
		{
			require_once( $data_function_file );
		}
		
	}
	
	public function index()
	{
		// 
	} 
}

?>