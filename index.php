<?php
// the front page of lazyphp

if( !isset( $_SERVER['HTTP_APPNAME'] ) )
{
	error_reporting(E_ALL);
	ini_set( 'display_errors' , true );
	ini_set( 'magic_quotes_gpc' , false );
}
else
{
	sae_set_display_errors(true);
}


// 常量
define( 'IN' , true );
define( 'DS' , '/' );
define( 'ROOT' , dirname( __FILE__ ) . DS );
define( 'CROOT' , ROOT . 'core' . DS  );
define( 'AROOT' , ROOT . 'app' . DS  );




// global functiones
include_once( CROOT . 'function' . DS . 'init.function.php' );
include_once( CROOT . 'function' . DS . 'core.function.php' );

@include_once( AROOT . 'function' . DS . 'app.function.php' );

include_once( CROOT . 'config' .  DS . 'core.config.php' );
include_once( AROOT . 'config' . DS . 'app.config.php' );

session_set_cookie_params( c('cookie_time') , c('cookie_path') , $_SERVER['HTTP_HOST']  );



$m = $GLOBALS['m'] = v('m') ? v('m') : c('default_mod');
$a = $GLOBALS['a'] = v('a') ? v('a') : c('default_action');
$m = basename(strtolower( $m ));

$post_fix = '.class.php';

$mod_file = AROOT . 'mod'  . DS . $m . $post_fix;

if( !file_exists( $mod_file ) ) die('Can\'t find controller file - ' . $m . $post_fix );
require( $mod_file );

if( !class_exists( $m.'Mod' ) ) die('Can\'t find class - '   . $m . 'Mod');

$class_name =$m.'Mod'; 

$o = new $class_name;
if( !method_exists( $o , $a ) ) die('Can\'t find method - '   . $a . ' ');


//if(ereg('gzip',$_SERVER['HTTP_ACCEPT_ENCODING']))  ob_start("ob_gzhandler");

// use call_user-func since call_user_method was deprecated as of PHP 4.1.0.
call_user_func(array($o, $a));



?>