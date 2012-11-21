<?php
if( !defined('IN') ) die('bad request');
include_once( AROOT . 'mod/app.class.php' );


define( 'LR_API_TOKEN_ERROR' , 10001 );
define( 'LR_API_USER_ERROR' , 10002 );
define( 'LR_API_DB_ERROR' , 10004 );
define( 'LR_API_NOT_IMPLEMENT_YET' , 10005 );
define( 'LR_API_ARGS_ERROR' , 10006 );
define( 'LR_API_DB_EMPTY_RESULT' , 10007 );


class apiMod extends appMod
{
	function __construct()
	{
		// 载入默认的
		parent::__construct();
		
	}
	
	public function index()
	{
		//print_r( $_REQUEST );
		$table = z(t(v('_table')));
		$action = z(t(v('_interface')));
		
		if( strlen( $table ) < 1 || strlen( $action ) < 1 )
			return $this->send_error( LR_API_ARGS_ERROR , 'BAD ARGS' );
			
			
		// user define code
		if( $my_code = get_var( "SELECT `code` FROM `__meta_code` WHERE `table` = '" . s( $table ) . "' AND `action` = '" . s($action) . "' LIMIT 1" ) )
		{
			return eval(  $my_code  );
			exit;
		}	
		
		
		// check table
		$tables = get_table_list(db());
		
		if( !in_array( $table , $tables ) )
			return $this->send_error( LR_API_ARGS_ERROR , 'TABLE NOT EXISTS' );
			
		if( ($table == c('token_table_name')) && ($action == 'get_token') )	return $this->get_token();
		
		
		$fields = get_fields( $table );
		
		$kv = new SaeKV();$kv->init();
			
		$ainfo = unserialize( $kv->get( 'msetting_' . $table . '_' . $action ));
		
		$in_code =   $kv->get( 'iosetting_input_' . $table . '_' . $action  )  ;
		$out_code =   $kv->get( 'iosetting_output_' . $table . '_' . $action  ) ;
		
		// run user defined input fliter
		if( strlen($in_code) > 0 ) eval( $in_code );
		
		
		if( $ainfo['on'] != 1 )
			return $this->send_error( LR_API_ARGS_ERROR , 'API NOT  AVAILABLE' );

		if( $ainfo['public'] != 1 )
			$this->check_token();
		
		$requires = array();
		$inputs = array();
		$outs = array();
		$likes = array();
		$equal = array();
		
		
		foreach( $fields as $field )
		{
			$finfo = unserialize( $kv->get( 'msetting_' . $table . '_' . $action .  '_' . $field  ) ) ;
			
			
			if( $finfo['required'] == 1 ) $requires[] = $field;
			if( $finfo['input'] == 1 ) $inputs[] = $field;
			if( $finfo['output'] == 1 ) $outputs[] = $field;
			if( $finfo['like'] == 1 ) $likes[] = $field;
			if( $finfo['equal'] == 1 ) $equals[] = $field;	
		}
		
		// check require
		if( count(  $requires ) > 0 )
		{
			foreach( $requires as $require )
			{
				if( strlen( v($require) ) < 1 ) return $this->send_error( LR_API_ARGS_ERROR ,  z(t($require)) .' FIELD REQUIRED' );
			}
		}
		
		// build sql
		
			switch( $action )
			{
				case 'insert':
					
					if( count( $inputs ) < 1 )  $this->send_error( LR_API_ARGS_ERROR , 'INPUT MUST HAS 1 FIELD AT LEAST' );
					if( count( $outputs ) < 1 )  $this->send_error( LR_API_ARGS_ERROR , 'OUTPUT MUST HAS 1 FIELD AT LEAST' );
					
					foreach( $inputs as $input )
					{
						$dsql[] = "'" . s(v($input)) . "'";
					}
					
					$sql = "INSERT INTO `" . s($table) . "` ( " . rjoin( ' , ' , '`' , $inputs ) . " ) VALUES ( " . join( ' , ' ,  $dsql ) . " )";
					
					//echo $sql;
					
					run_sql( $sql );
					
					if( mysql_errno() != 0 ) $this->send_error( LR_API_DB_ERROR , 'DATABASE ERROR ' . mysql_error() );
					
					$lid = last_id();
					if( $lid < 1 ) $this->send_error( LR_API_DB_ERROR , 'DATABASE ERROR ' . mysql_error() );
					
					if( !$data = get_data( "SELECT " . rjoin( ' , ' , '`' , $outputs ). " FROM `" . s( $table ) . "` WHERE `id` = '" . intval( $lid ) . "'" , db() ))
						$this->send_error( LR_API_DB_ERROR , 'DATABASE ERROR ' . mysql_error() );
					else
					{
						if( strlen( $out_code ) > 0 ) eval( $out_code );
						$this->send_result( $data );
					}	
						
	
					
					break;
				
				case 'update':
					if( count( $inputs ) < 1 )  return $this->send_error( LR_API_ARGS_ERROR , 'INPUT MUST HAS 1 FIELD AT LEAST' );
					if( count( $requires ) < 1 )  return $this->send_error( LR_API_ARGS_ERROR , 'REQUIRE MUST HAS 1 FIELD AT LEAST' );
					
					foreach( $inputs as $input )
					{
						if( !in_array( $input , $likes ) && !in_array( $input , $equals ) )
						{
							if( isset( $_REQUEST[$input] ) )
								$dsql[] = " `" . s($input) . "` = '" . s(v($input)) . "' ";
						}
						else
						{
							if( in_array( $input , $likes ) )
							{
								$wsql[] = " `" . s( $input ) . "` LIKE '%" . s(v($input)) . "%' ";
							}
							else
							{
								$wsql[] = " `" . s( $input ) . "` = '" . s(v($input)) . "' ";
							}
						}
					}
	
					if( !isset($dsql) || !isset($wsql) ) return $this->send_error( LR_API_ARGS_ERROR , 'INPUT AND LIKE/EQUALS MUST HAS 1 FIELD AT LEAST' );
					
					$sql = "UPDATE `" . s( $table ) . "` SET " . join( ' , ' , $dsql ) . ' WHERE ' . join( ' AND ' , $wsql );
					
					//echo $sql ;
					run_sql( $sql );
					
					if( mysql_errno() != 0 ) $this->send_error( LR_API_DB_ERROR , 'DATABASE ERROR ' . mysql_error() );
					
					$lid = intval(v('id'));
					
					
					if( $lid < 1 ) $this->send_error( LR_API_DB_ERROR , 'DATABASE ERROR ' . mysql_error() );
					
					if( !$data = get_data( "SELECT " . rjoin( ' , ' , '`' , $outputs ). " FROM `" . s( $table ) . "` WHERE `id` = '" . intval( $lid ) . "'" ))
						$this->send_error( LR_API_DB_ERROR , 'DATABASE ERROR ' . mysql_error() );
					else
					{
						if( strlen( $out_code ) > 0 ) eval( $out_code );
						$this->send_result( $data );
					}	
						
					
					break;
					
					
				case 'remove':
					
					if( count( $inputs ) < 1 )  return $this->send_error( LR_API_ARGS_ERROR , 'INPUT MUST HAS 1 FIELD AT LEAST' );
					if( count( $requires ) < 1 )  return $this->send_error( LR_API_ARGS_ERROR , 'REQUIRE MUST HAS 1 FIELD AT LEAST' );
					
					foreach( $inputs as $input )
					{
						if( in_array( $input , $likes ) )
						{
							$wsql[] = " `" . s( $input ) . "` LIKE '%" . s(v($input)) . "%' ";
						}
						elseif( in_array( $input , $equals ) )
						{
							$wsql[] = " `" . s( $input ) . "` = '" . s(v($input)) . "' ";
						}
					}
	
					if( !isset($wsql) ) return $this->send_error( LR_API_ARGS_ERROR , 'INPUT AND LIKE/EQUALS MUST HAS 1 FIELD AT LEAST' );
					
					if( count( $outputs ) > 0 )
					{
						$sql = "SELECT " . rjoin( ',' , '`' , $outputs ) . " FROM `" . s( $table ) . "` WHERE  ". join( ' AND ' , $wsql );			
						$data = get_line( $sql );
						
						if( mysql_errno() != 0 ) 
							return $this->send_error( LR_API_DB_ERROR , 'DATABASE ERROR ' . mysql_error() );
					}
					
					$sql = "DELETE FROM `" . s( $table ) . "` WHERE " . join( ' AND ' , $wsql );			
					run_sql( $sql );
					if( mysql_errno() != 0)
						$this->send_error( LR_API_DB_ERROR , 'DATABASE ERROR ' . mysql_error() );
					else	
						if(  count( $outputs ) < 1 ) 
							return $this->send_result( array( 'msg' => 'ok' ) );
						else
						{
							if( strlen( $out_code ) > 0 ) eval( $out_code );
							return 	$this->send_result( $data );
						}
												
					break;
					
					
				
				
				
				
				case 'list':
				default:
					$since_id = intval( v('since_id') );
					$max_id = intval( v('max_id') );
					$count = intval(v('count'));
					
					$order = strtolower(z(t(v('ord'))));
					$by = strtolower(z(t(v('by'))));
					
					
					if( $order == 'asc' ) $ord = ' ASC ';
					else $ord = ' DESC ';
					
					if( strlen($by) > 0 )
						$osql = ' ORDER BY `' . s( $by ) . '` ' . $ord . ' ';
					else
						$osql = '';
					
					if( $count < 1 ) $count = 10;
					if( $count > 100 ) $count = 100;
					
					
					if( count( $outputs ) < 1 )  $this->send_error( LR_API_ARGS_ERROR , 'OUTPUT MUST HAS 1 FIELD AT LEAST' );
					
					$sql = "SELECT " . rjoin( ',' , '`' , $outputs ) . " FROM `" . s( $table ) . "` WHERE 1 ";
					
					if( $since_id > 0 ) $wsql = " AND `id` > '" . intval( $since_id ) . "' ";
					elseif( $max_id > 0 ) $wsql = " AND `id` < '" . intval( $max_id ) . "' ";
					
					if( (count( $inputs ) > 0) && ((count($likes)+count($equals)) > 0) )
					{
						// AND `xxx` == $xxx
						if( count($likes) > 0 )
						{
							foreach( $likes as $like )
							{
								if( z(t(v($like))) != '' )
								$wwsql[] = " AND `" . s( $like ) . "` LIKE '%" . s(v($like)) . "%' ";
							}
						}
						
						if( count($equals) > 0 )
						{
							foreach( $equals as $equal )
							{
								if( z(t(v($equal))) != '' )
								$wwsql[] = " AND `" . s( $equal ) . "` = '" . s(v($equal)) . "' ";
							}
						}
						
						if( isset( $wwsql ) )
						$wsql = $wsql . join( ' ' , $wwsql );
					}
					
					
					$sql = $sql . $wsql . $osql .  " LIMIT " . $count ;
					
					
					//echo $sql;
					if($idata = get_data( $sql ))
					{
						$first = reset( $idata );
						$max_id = $first['id'];
						$min_id = $first['id'];
						
						foreach( $idata as $item )
						{
							if( $item['id'] > $max_id ) $max_id = $item['id'];
							if( $item['id'] < $min_id ) $min_id = $item['id'];
						}
						
						$data = array( 'items' => $idata , 'max_id' => $max_id , 'min_id' => $min_id );
					}
					else
						$data = $idata;
					
					
					
					
					if( mysql_errno() != 0  )
						return $this->send_error( LR_API_DB_ERROR , 'DATABASE ERROR ' . mysql_error() );
					else
					{
						if( strlen( $out_code ) > 0 ) eval( $out_code );
						return $this->send_result( $data );
	
					}
					
					
							
					
					
					
					
					
			}
		
		
		//return $this->send_error( LR_API_ARGS_ERROR , 'FIELD NOT EXISTS' );
			
			
		
	}
	
	public function get_token()
	{
		$token_account_field = c('token_account_field');
		$token_password_field = c('token_password_field');
		$token_table_name = c('token_table_name');
		
		
		$account = z(t(v($token_account_field)));
		$password = z(t(v($token_password_field)));
		$token_table_name  = z(t($token_table_name)); 
		
		$sql = "SELECT * FROM `" . s( $token_table_name ) . "` WHERE `" . s($token_account_field) . "` = '" . s( $account ) . "' AND `" . s($token_password_field) . "` = '" . md5( $password ) . "' LIMIT 1";
		
		if( $user = get_line( $sql ) )
		{
			
			session_start();
			$token = session_id();
			$_SESSION['token'] = $token;
			$_SESSION['uid'] = $user['id'];
			$_SESSION['account'] = $user[c('token_account_field')];
			
			
			return $this->send_result( array( 'token' => $token , 'uid' => $user['id'] ) );
			
		}
		else
		{
			return $this->send_error( LR_API_TOKEN_ERROR , 'BAD ACCOUNT OR PASSWORD' );
		}
		
	}
	
	private function check_token()
	{
		$token = z(t(v('token')));
		if( strlen( $token ) < 2 ) return $this->send_error( LR_API_TOKEN_ERROR , 'NO TOKEN' );
		
		session_id( $token );
		session_start();
		
		if( $_SESSION['token'] != $token ) return $this->send_error( LR_API_TOKEN_ERROR , 'BAD TOKEN' );
	}
	
	public function send_error( $number , $msg )
	{	
		$obj = array();
		$obj['err_code'] = intval( $number );
		$obj['err_msg'] = $msg;
		
		header('Content-type: application/json');
		die( json_encode( $obj ) );
	}
	
	public function send_result( $data )
	{
		$obj = array();
		$obj['err_code'] = '0';
		$obj['err_msg'] = 'success';
		$obj['data'] = $data;

		header('Content-type: application/json');
		die( json_encode( $obj ) );
	}

	
	
	
	
	
}


?>