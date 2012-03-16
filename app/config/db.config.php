<?php

// ---------

if( c('on_sae') )
{

	$GLOBALS['config']['db']['db_host'] = SAE_MYSQL_HOST_M;
	$GLOBALS['config']['db']['db_port'] = SAE_MYSQL_PORT;

	$GLOBALS['config']['db']['db_user'] =  SAE_ACCESSKEY;
	$GLOBALS['config']['db']['db_password'] = SAE_SECRETKEY;
	$GLOBALS['config']['db']['db_name'] = SAE_MYSQL_DB;
	
	$GLOBALS['config']['db']['db_host_readonly'] = SAE_MYSQL_HOST_S;
	
	

}
else
{
	$GLOBALS['config']['db']['db_host'] = 'localhost';
	$GLOBALS['config']['db']['db_port'] = 3306;
	$GLOBALS['config']['db']['db_user'] = 'root';
	$GLOBALS['config']['db']['db_password'] = 'root';
	$GLOBALS['config']['db']['db_name'] = 'lz_product';

}


?>