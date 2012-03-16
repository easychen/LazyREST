<?php
if( !defined('IN') ) die('bad request');
include_once( AROOT . 'mod/app.class.php' );

class defaultMod extends appMod
{
	function __construct()
	{
		// 载入默认的
		parent::__construct();
		if( g('a') != 'login' && g('a') != 'login_check' && g('a') != 'install')
		{
			if( !is_login() ) return info_page('<a href="?a=login">请先登入</a>');
		} 
	}
	
	public function install()
	{
		if( !table_exists( '__meta_user' ) && !table_exists( '__meta_code' ))
		{
			$sql = "CREATE TABLE IF NOT EXISTS `__meta_code` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `table` varchar(32) NOT NULL,
  `action` varchar(32) NOT NULL,
  `code` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `table` (`table`,`action`)
)";
			run_sql( $sql );
			
			$sql = "CREATE TABLE IF NOT EXISTS `__meta_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(32) NOT NULL,
  PRIMARY KEY (`id`)
) ";
			run_sql( $sql );
			$email = 'admin'.rand(1,999).'@admin.com';
			$password = substr(md5(rand( 10000,8000 ) . time()) , 0 , 8);
			
			$sql = "INSERT INTO `__meta_user` ( `email` , `password` ) VALUES ( '" . s($email) . "' , '" . md5($password) . "' ) ";
			
			run_sql( $sql );
			
			if( mysql_errno() == 0 )
			{
				return info_page( "初始化成功，请使用【".$email."】和【" . $password . "】<a href='?a=login' target='_blank'>登录</a>。您可以通过phpmyadmin修改【__meta_user】表来管理账户"
					. '<br /><br /><a href="http://ftqq.com/2012/01/10/build-a-rest-server-in-5-minutes/" target="_blank">使用教程</a>'
				);
			}
		}
		
		return info_page("已经初始化或数据库错误，请稍后重试");
	}
	
	public function index()
	{
		$data['tables'] = get_table_list(db());
		
		//print_r( $data );
		$data['title'] = $data['top_title'] = '数据表';
		render( $data );
	}
	
	public function table_settings()
	{
		$table = z(t(v('table')));
		
		$tables = get_table_list(db());
		
		if( !in_array( $table , $tables ) )
			return info_page( '<a href="javascript:history.back(1);">table不存在，点击返回</a>' );
		
		$data['fields'] = get_fields_info( $table );
		
		$data['actions'] = array( 'list' => 'List' , 'insert'=>'Insert' , 'update'=> 'Update' , 'remove' => 'Remove'  );
		
		$data['table'] = $table;
		
		$data['my_actions'] = get_data( "SELECT * FROM `__meta_code` WHERE `table` = '" . s( $table ) . "' ORDER BY `id` DESC" );
		//print_r( $fields );
		$data['title'] = $data['top_title'] = 'API设置';
		
		$data['js'][] = 'codemirror.js';
		$data['js'][] = 'util/runmode.js';
		$data['js'][] = 'mode/php/php.js';
		$data['js'][] = 'mode/htmlmixed/htmlmixed.js';
		$data['js'][] = 'mode/css/css.js';
		$data['js'][] = 'mode/javascript/javascript.js';
		$data['js'][] = 'mode/xml/xml.js';
		$data['js'][] = 'mode/clike/clike.js';
		
		
		$data['css'][] = 'codemirror.css';
		$data['css'][] = 'theme/night.css';
		
		render( $data );
		
	}
	
	public function iosettings_save()
	{
		$action = z(t(v('action')));
		$table = z(t(v('table')));
		
		if( strlen( $action ) < 1 || strlen( $table ) < 1  )
		return ajax_echo( '参数不完整' );
		
		$in_code = t(v('in_code'));
		$out_code = t(v('out_code'));
		
		//print_r( $_REQUEST );
		
		
		
		$kv = new SaeKV();
		$kv->init();
		
		$data['input_settings'] =  $kv->set( 'iosetting_input_' . $table  . '_' . $action  ,  $in_code ) ;
		
		$data['out_settings'] =   $kv->set( 'iosetting_output_' . $table  . '_' . $action  , $out_code )  ;
		
		
		return ajax_echo('<script>window.location.reload();</script>');
		
	}
	
	public function fsettings_save()
	{
		//print_r( $_REQUEST ); 
		
		$action = z(t(v('action')));
		$table = z(t(v('table')));
		$field = z(t(v('field')));
		$tdid = z(t(v('tdid')));
		
		if( strlen( $action ) < 1 || strlen( $table ) < 1 || strlen( $field ) < 1 || strlen( $tdid ) < 1 )
		return ajax_echo( '参数不完整' );
		
		$kv = new SaeKV();
		$kv->init();
		
		$ret = array();
		foreach( $_REQUEST['st'] as $k=>$v )
		{
			$ret[z(t($k))] = intval( $v );
		}
		
		$_REQUEST['st'] = $ret;
		
		$kv->set( 'msetting_' . $table . '_' . $action .  '_' . $field ,  serialize( v('st') )  );
		//echo 'msetting_' . $table . '_' . $action .  '_' . $field . '`~'.serialize( v('st') );
		//print_r( $kv );
		
		//echo $kv->get( 'msetting_' . $table . '_' . $action .  '_' . $field );
		return ajax_echo('<script>window.location.reload();</script>');
		
		
	}
	
	public function action_add()
	{
		$data = array();
		$data['table'] = z(t(v('table')));
		return render( $data , 'ajax' );
	}
	
	public function action_modify()
	{
		$data = array();
		
		$action = z(t(v('action')));
		$table = $data['table'] = z(t(v('table')));
		
		$sql = "SELECT * FROM `__meta_code` WHERE `action` = '" . s( $action ) . "' AND `table` = '" . s( $table ) . "' LIMIT 1";
		$data['my_action'] = get_line( $sql );
		return render( $data , 'ajax' );
	}
	
	public function action_delete()
	{
		$action = z(t(v('action')));
		$table = z(t(v('table')));
		
		if( strlen( $action ) < 1 || strlen( $table ) < 1  )
		return ajax_echo( '参数不完整' );
		
		$sql = "DELETE FROM `__meta_code` WHERE `action` = '" . s( $action ) . "' AND `table` = '" . s( $table ) . "' LIMIT 1";
		
		run_sql( $sql );
		
		return ajax_echo('<script>window.location.reload();</script>');
	}
	
	public function action_save()
	{
		$action = z(t(v('action')));
		$table = z(t(v('table')));
		$code = t(v('code'));
		
		if( strlen( $action ) < 1 || strlen( $table ) < 1  )
		return ajax_echo( '参数不完整' );
		
		$sql = "REPLACE INTO `__meta_code` ( `table` , `action` , `code` ) VALUES ( '" . s( $table ) . "' , '" . s( $action ) . "' , '" . s($code) . "' ) ";
		
		run_sql( $sql );
		
		return ajax_echo('<script>window.location.reload();</script>');
		
	}
	
	public function asettings_save()
	{
		//print_r( $_REQUEST ); 
		
		$action = z(t(v('action')));
		$table = z(t(v('table')));
		
		if( strlen( $action ) < 1 || strlen( $table ) < 1  )
		return ajax_echo( '参数不完整' );
		
		$kv = new SaeKV();
		$kv->init();
		
		
		if( $_REQUEST['st']['public'] == 1 ) $_REQUEST['st']['basic'] == 0;
		else $_REQUEST['st']['basic'] == 1;
		
		if( $_REQUEST['st']['on'] == 1 ) $_REQUEST['st']['off'] == 0;
		else $_REQUEST['st']['off'] == 1;
		
		$kv->set( 'msetting_' . $table . '_' . $action  ,  serialize( v('st') )  );
		//echo 'msetting_' . $table . '_' . $action .  '_' . $field . '`~'.serialize( v('st') );
		//print_r( $kv );
		
		//echo $kv->get( 'msetting_' . $table . '_' . $action  );
		return ajax_echo('<script>window.location.reload();</script>');
		
		
	}
	
	public function action_settings()
	{
		$settings = array();
		
		$settings[] = array( 'text' => '开' , 'value' => 'on' , 'desp' => '开启' ) ;
		$settings[] = array( 'text' => '关' , 'value' => 'off' , 'desp' => '关闭' ) ;
		$settings[] = array( 'text' => '全' , 'value' => 'public' , 'desp' => '无需认证') ;
		$settings[] = array( 'text' => '认' , 'value' => 'basic' , 'desp' => '用户认证') ;
		
		
		$data['settings'] = $settings;
		$data['table'] = z(t(v('table')));
		$data['action'] = z(t(v('action')));
		
		$data['title'] = '接口属性设置';
		
		$kv = new SaeKV();
		$kv->init();
		
		$data['ainfo'] =  unserialize( $kv->get( 'msetting_' . $data['table'] . '_' . $data['action']   ) ) ;
		
		

		
		return render( $data , 'ajax' ); 
	
	}
	
	public function io_settings()
	{
		$data['table'] = z(t(v('table')));
		$data['action'] = z(t(v('action')));
		
		
		$data['title'] = 'I/O过滤设置';
		
		$kv = new SaeKV();
		$kv->init();
		
		$data['input_settings'] =   $kv->get( 'iosetting_input_' . $data['table'] . '_' . $data['action']  )  ;
		
		$data['output_settings'] =   $kv->get( 'iosetting_output_' . $data['table'] . '_' . $data['action']  ) ;
		
		//print_r(  $data );
		
		return render( $data , 'ajax' );
	}
	
	public function fields_settings()
	{
		$settings = array();
		
		$settings[] = array( 'text' => '入' , 'value' => 'input' , 'desp' => '作为输入参数' ) ;
		$settings[] = array( 'text' => '返' , 'value' => 'output' , 'desp' => '作为返回值' ) ;
		$settings[] = array( 'text' => '必' , 'value' => 'required' , 'desp' => '必填参数') ; 
		
		$settings[] = array( 'text' => '%' , 'value' => 'like' , 'desp' => 'Like匹配') ; 
		
		$settings[] = array( 'text' => '=' , 'value' => 'equal' , 'desp' => '相等匹配') ; 
		
		$data['settings'] = $settings;
		$data['table'] = z(t(v('table')));
		$data['field'] = z(t(v('field')));
		$data['action'] = z(t(v('action')));
		
		$data['tdid'] = intval(v('tdid'));
		
		$data['title'] = '字段属性设置';
		
		$kv = new SaeKV();
		$kv->init();
		
		$data['finfo'] =  unserialize( $kv->get( 'msetting_' . $data['table'] . '_' . $data['action'] .  '_' . $data['field']  ) ) ;
		
		

		
		return render( $data , 'ajax' );
	}
	
	public function logout()
	{
		foreach( $_SESSION as $k => $v )
		{
			unset( $_SESSION[$k] );
		}
		
		return info_page( '<a href="/">成功退出，点击返回首页</a>' );
	}
	
	public function login()
	{
		$data['title'] = $data['top_title'] = 'LazyRest - 最简单的Rest Server';
		render( $data );	
	}
	
	public function login_check()
	{
		$email = z(t(v('email')));
		$password = z(t(v('password')));
		
		if( strlen( $email ) < 1 || strlen( $password ) < 1 )
			return ajax_echo( "电子邮件和密码不能为空" );
		
		$sql = "SELECT `id` , `email` FROM `__meta_user` WHERE `email` = '" . s( $email ) . "' AND `password` = '" . md5( $password ) . "'";
		
		if( !$user = get_line($sql) ) return ajax_echo( "电子邮件和密码不匹配，请重试" );
		
		$_SESSION['uid'] = $user['id'];
		$_SESSION['email'] = $user['email'];
		$_SESSION['ulevel'] = 9;
		
		// do login
		return ajax_echo("成功登录，转向中…<script>location = '?a=index';</script>");
		
		
	}
	
	
	
}


?>
