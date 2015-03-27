if( !defined('IN') ) die('bad request');
include_once( AROOT . 'mod/app.class.php' );


define( 'LR_API_TOKEN_ERROR' , 10001 );
define( 'LR_API_USER_ERROR' , 10002 );
define( 'LR_API_DB_ERROR' , 10004 );
define( 'LR_API_NOT_IMPLEMENT_YET' , 10005 );
define( 'LR_API_ARGS_ERROR' , 10006 );
define( 'LR_API_DB_EMPTY_RESULT' , 10007 );


class apiController extends appController
{
	function __construct()
	{
		// 载入默认的
		parent::__construct();
		
	}
	
	<?php foreach( $tables as $table ): ?>
	<?php foreach( $actions as $action ): ?>
	public function <?=$table?>_<?=$action?>()
	{
		<?php if( $ainfo[$table][$action]['on'] != '1' ):?>
			return $this->send_error( LR_API_ARGS_ERROR , 'API NOT  AVAILABLE' );
		<?php else: ?>
			<?php if( $ainfo[$table][$action]['public'] != '1' ): ?>
				$this->check_token();
			<?php endif; // end ainfo public ?>	
			
			<?php if( strlen($in_code[$table][$action]) > 0 ): ?>
			<?=$in_code[$table][$action];?>
			<?php endif; ?>
			
			// requires
			<?php if( count($requires[$table][$action]) > 0 ): ?>
				<?php foreach( $requires[$table][$action] as $require ): ?>
				if( strlen( v('<?=$require?>') ) < 1 ) return $this->send_error( LR_API_ARGS_ERROR ,  '<?=$require?> FIELD REQUIRED' );
				<?php endforeach; // require ?>
			<?php endif;//requires ?>
			
			
			// actions
			<?php if( $action == 'list' ): ?>
			
			<?php if( count($outputs[$table][$action]) < 1 ): ?>
			// warning: output OUTPUT MUST HAS 1 FIELD AT LEAST
			return false; }
			<?php continue; ?>
			<?php endif; // outputs check ?>
			
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
			
			$sql = "SELECT <?php echo rjoin( ',' , '`' , $outputs[$table][$action] )?> FROM `<?=$table?>` WHERE 1 ";
			
			if( $since_id > 0 ) $wsql = " AND `id` > '" . intval( $since_id ) . "' ";
			elseif( $max_id > 0 ) $wsql = " AND `id` < '" . intval( $max_id ) . "' ";
			
			<?php 
			if( (count( $inputs[$table][$action]  ) > 0) && ((count($likes[$table][$action] )+count($equals[$table][$action] )) > 0) )
			{
				if( count($likes[$table][$action] ) > 0 )
				{
					foreach( $likes[$table][$action]  as $like )
					{
						if( z(t(v($like))) != '' )
						{
						?>
						$wwsql[] = " AND `<?=$like?>` LIKE '%" . s(v('<?=$like?>')) . "%' ";
						<?php
						}
					}
				}
				
				if( count($equals[$table][$action]) > 0 )
				{
					foreach( $equals[$table][$action] as $equal )
					{
						if( z(t(v($equal))) != '' )
						{?>
						$wwsql[] = " AND `<?=$equal?>` = '" . s(v('<?=$equal?>')) . "' ";	
						<?php
						}
						
					}
				}
				?>
				if( isset( $wwsql ) )
				$wsql = $wsql . join( ' ' , $wwsql );
				<?php
			}
			?>
			
			
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
				<?php if( strlen($out_code[$table][$action]) > 0 ): ?>
				<?=$out_code[$table][$action];?>
				<?php endif; ?>
				return $this->send_result( $data );

			}
			<?php endif;// list action ?>
			
			<?php if( $action == 'insert' ): ?>
			
			<?php if( count($outputs[$table][$action]) < 1 ): ?>
			// warning: OUTPUT MUST HAS 1 FIELD AT LEAST
			return false;}
			<?php continue; ?>
			<?php endif; // outputs check ?>
			
			<?php if( count($inputs[$table][$action]) < 1 ): ?>
			// warning: INPUT MUST HAS 1 FIELD AT LEAST
			return false;}
			<?php continue; ?>
			<?php endif; // inputs check ?>
			
			
			
			<?php foreach( $inputs[$table][$action] as $input ): ?>
			$dsql[] = "'" . s(v('<?=$input?>')) . "'";
			<?php endforeach; // inputs ?>
			
			$sql = "INSERT INTO `<?=$table?>` ( <?php echo rjoin( ' , ' , '`' , $inputs[$table][$action] )  ?> ) VALUES ( " . join( ' , ' ,  $dsql ) . " )";
			
			//echo $sql;
			run_sql( $sql );
			
			if( mysql_errno() != 0 ) $this->send_error( LR_API_DB_ERROR , 'DATABASE ERROR ' . mysql_error() );
			
			$lid = last_id();
			if( $lid < 1 ) $this->send_error( LR_API_DB_ERROR , 'DATABASE ERROR ' . mysql_error() );
			
			if( !$data = get_data( "SELECT <?php echo rjoin( ' , ' , '`' , $outputs[$table][$action] )  ?> FROM `<?=$table?>` WHERE `id` = '" . intval( $lid ) . "'" ))
				$this->send_error( LR_API_DB_ERROR , 'DATABASE ERROR ' . mysql_error() );
			else
			{
				<?php if( strlen($out_code[$table][$action]) > 0 ): ?>
				<?=$out_code[$table][$action];?>
				<?php endif; ?>
				$this->send_result( $data );
			}	
				
			<?php endif;// insert action ?>
			
			<?php if( $action == 'update' ): ?>
			
			<?php if( count($outputs[$table][$action]) < 1 ): ?>
			// warning: OUTPUT MUST HAS 1 FIELD AT LEAST
			return false;}
			<?php continue; ?>
			<?php endif; // outputs check ?>
			
			<?php if( count($inputs[$table][$action]) < 1 ): ?>
			// warning: INPUT MUST HAS 1 FIELD AT LEAST
			return false;}
			<?php continue; ?>
			<?php endif; // inputs check ?>
			
			<?php foreach( $inputs[$table][$action] as $input )
			{
				if( !in_array( $input , $likes[$table][$action] ) && !in_array( $input , $equals[$table][$action] ) )
				{
					?>
					if( isset( $_REQUEST['<?=$input?>'] ) )
						$dsql[] = " `<?=$input?>` = '" . s(v('<?=$input?>')) . "' ";
					<?php	
				}
				else
				{
					if( in_array( $input , $likes[$table][$action] ) )
					{
						?>
						$wsql[] = " `<?=$input?>` LIKE '%" . s(v('<?=$input?>')) . "%' ";
						<?php
					}
					else
					{
						?>
						$wsql[] = " `<?=$input?>` = '" . s(v('<?=$input?>')) . "' ";
						<?php
					}
				}
			}
			?>

			if( !isset($dsql) || !isset($wsql) ) return $this->send_error( LR_API_ARGS_ERROR , 'INPUT AND LIKE/EQUALS MUST HAS 1 FIELD AT LEAST' );
			
			$sql = "UPDATE `<?=$table?>` SET " . join( ' , ' , $dsql ) . ' WHERE ' . join( ' AND ' , $wsql );
			
			//echo $sql ;
			run_sql( $sql );
			
			if( mysql_errno() != 0 ) $this->send_error( LR_API_DB_ERROR , 'DATABASE ERROR ' . mysql_error() );
			
			$lid = intval(v('id'));
			
			
			if( $lid < 1 ) $this->send_error( LR_API_DB_ERROR , 'DATABASE ERROR ' . mysql_error() );
			
			if( !$data = get_data( "SELECT <?=rjoin( ' , ' , '`' , $outputs['table']['action'] );?> FROM `<?=$table?>` WHERE `id` = '" . intval( $lid ) . "'" ))
				$this->send_error( LR_API_DB_ERROR , 'DATABASE ERROR ' . mysql_error() );
			else
			{
				
				<?php if( strlen($out_code[$table][$action]) > 0 ): ?>
				<?=$out_code[$table][$action];?>
				<?php endif; ?>
				
				$this->send_result( $data );
			}	
			<?php endif;// update action ?>
			
			<?php if( $action == 'remove' ): ?>
			
			<?php if( count($outputs[$table][$action]) < 1 ): ?>
			// warning: OUTPUT MUST HAS 1 FIELD AT LEAST
			return false;}
			<?php continue; ?>
			<?php endif; // outputs check ?>
			
			<?php if( count($inputs[$table][$action]) < 1 ): ?>
			// warning: INPUT MUST HAS 1 FIELD AT LEAST
			return false;}
			<?php continue; ?>
			<?php endif; // inputs check ?>
			
			<?php
			foreach( $inputs[$table][$action] as $input )
			{
				if( in_array( $input , $likes[$table][$action] ) )
				{
					?>
					$wsql[] = " `<?=$input?>` LIKE '%" . s(v('<?=$input?>')) . "%' ";
					<?php
				}
				elseif( in_array( $input , $equals[$table][$action] ) )
				{
					?>
					$wsql[] = " `<?=$input?>` = '" . s(v('<?=$input?>')) . "' ";
					<?php
				}
			}
			?>

			if( !isset($wsql) ) return $this->send_error( LR_API_ARGS_ERROR , 'INPUT AND LIKE/EQUALS MUST HAS 1 FIELD AT LEAST' );
			
			<?php  if( count( $outputs[$table][$action] ) > 0 ):?>
			$sql = "SELECT <?=rjoin( ' , ' , '`' , $outputs['table']['action'] )?> FROM `<?=$table?>` WHERE  ". join( ' AND ' , $wsql );			
			$data = get_line( $sql );
				
			if( mysql_errno() != 0 ) 
				return $this->send_error( LR_API_DB_ERROR , 'DATABASE ERROR ' . mysql_error() );
			<?php endif; // outputs ?>
			
			$sql = "DELETE FROM `<?=$table?>` WHERE " . join( ' AND ' , $wsql );			
			run_sql( $sql );
			if( mysql_errno() != 0)
				$this->send_error( LR_API_DB_ERROR , 'DATABASE ERROR ' . mysql_error() );
			else
			{
				<?php if(  count( $outputs[$table][$action] ) < 1 ): ?>
					return $this->send_result( array( 'msg' => 'ok' ) );
				<?php else: ?>	
					<?php if( strlen($out_code[$table][$action]) > 0 ): ?>
						<?=$out_code[$table][$action];?>
					<?php endif; ?>					
					return 	$this->send_result( $data );
				<?php endif; // output ?>

			}	
							
			<?php endif;// remove action ?>
			
			
		<?php endif; // end ainfo on ?>
	
	}
	<?php endforeach; // end actions ?>
	
	<?php foreach( $my_actions as $action ): ?>
	
	<?php if( strlen($action['code']) > 1  ): ?>
	public function <?=$table?>_<?=$action['action']?>()
	{
		<?=$action['code']?>
	
	}	
	<?php endif; ?>
	<?php endforeach; // end my_actions ?>
	
	
	
	<?php endforeach; // end tables ?>
	
	
	public function get_token()
	{
		$token_account_field = '<?=c('token_account_field')?>';
		$token_password_field = '<?=c('token_password_field')?>';
		$token_table_name = '<?=c('token_table_name')?>';
		
		
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
			$_SESSION['account'] = $user[$token_account_field];
			
			
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
		
		die( json_encode( $obj ) );
	}
	
	public function send_result( $data )
	{
		$obj = array();
		$obj['err_code'] = '0';
		$obj['err_msg'] = 'success';
		$obj['data'] = $data;
		
		die( json_encode( $obj ) );
	}

	
	
	
	
	
}
