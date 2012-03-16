<?php
// the main 

// lazy functiones




function v( $str )
{
	return isset( $_REQUEST[$str] ) ? $_REQUEST[$str] : false;
}

function z( $str )
{
	return strip_tags( $str );
}

function c( $str )
{
	return isset( $GLOBALS['config'][$str] ) ? $GLOBALS['config'][$str] : false;
}

function g( $str )
{
	return isset( $GLOBALS[$str] ) ? $GLOBALS[$str] : false;	
}

function e($message = null,$code = null) 
{
	throw new Exception($message,$code);
}

function t( $str )
{
	return trim($str);
}

// session management
function ss( $key )
{
	return isset( $_SESSION[$key] ) ?  $_SESSION[$key] : false;
}

function ss_set( $key , $value )
{
	return $_SESSION[$key] = $value;
}

function is_debug()
{
	return $GLOBALS['debug_mark'];
}

function debug( $mark = true )
{
	$GLOBALS['debug_mark'] = $mark ;
}

// render functiones
function render( $data = NULL , $layout = 'default' )
{
	$GLOBALS['layout'] = $layout;
	$layout_file = AROOT . 'view/layout/' . $layout . '/index.tpl.html';
	if( file_exists( $layout_file ) )
	{
		@extract( $data );
		require( $layout_file );
	}
	else
	{
		$layout_file = CROOT . 'view/layout/' . $layout . '/index.tpl.html';
		if( file_exists( $layout_file ) )
		{
			@extract( $data );
			require( $layout_file );
		}	
	}
}

function info_page( $info , $layout = 'default' )
{
	$GLOBALS['m'] = 'default';
	$GLOBALS['a'] = 'info';
	$data['title'] = $data['top_title'] = '系统消息';
	$data['info'] = $info;
	render( $data , $layout );
}




// db functions
include_once( CROOT .  'function/db.function.php' );



function ajax_echo( $info )
{
	if( is_debug() )
	{
		return $info;
	}
	else
	{
		header("Content-Type:text/xml;charset=utf-8");
		header("Expires: Thu, 01 Jan 1970 00:00:01 GMT");
		header("Cache-Control: no-cache, must-revalidate");
		header("Pragma: no-cache");
		echo $info;
	}
}

function ajax_box( $content , $title = '系统消息' , $close_time = 0 , $forward = '' )
{
	if( is_debug() )
	{
		return $content;
	}
	else
	{
		require_once( AROOT . 'view/layout/ajax/box.tpl.html' );
	}
}


function fliter( $array , $pre )
{
	$ret = array();
	foreach( $array as $key=>$value )
	{
		if( strpos( $key , $pre ) === 0 )
			$ret[$key] = $value;
	}
	return $ret;
}

function uses( $m )
{
	load( 'function/' . basename($m) . '.function.php' );
}

function load( $file_path ) 
{
	$file = AROOT . $file_path;
	if( file_exists( $file ) )
		include_once( $file );
	else
		include_once( CROOT . $file_path );
}

function file_get_url( $file )
{
	$len = strlen( 'saestor://' );
	if( substr( $file , 0 , $len ) == 'saestor://' )
	{
		$path = str_replace( 'saestor://' , '' , $file );
		$info = explode( '/' , $path );
		$domain = array_shift( $info );
		return 'http://'. $_SERVER['HTTP_APPNAME'] .'-'.$domain . '.stor.sinaapp.com/' . join('/',$info);
	}
	else
	{
		return c('site_url') . '/' . str_replace( ROOT , '' , $file );
	}
}

if( !function_exists('memcache_init') )
{
	function memcache_init()
	{
		return memcache_connect( c('mc_host') , c('mc_port') );
	}
	
}

function send_mail( $email , $subject , $content )
{
	if( c('on_sae') )
	{
		$m = new SaeMail();
		$m->quickSend( $email , $subject , $content , c('smtp_account') , c('smtp_password') , c('smtp_server') , c('smtp_port') );
		return $m->errmsg();
	}
	else
	{
		return @mail( $email , $subject , $content );
	}
}



?>