<?php
if( !defined('IN') ) die('bad request');
include_once( AROOT . 'controller'.DS.'app.class.php' );

class exportController extends appController
{
	function __construct()
	{
		parent::__construct();
	}
	
	public function index()
	{
	
	}

	public function lp3()
	{
		// 首先获取所有表
		if($tables = get_table_list(db()))
		$data['tables'] = $tables;
		$data['actions'] = $actions = array( 'list' , 'insert' , 'remove' , 'update' );
		
		foreach( $tables as $table )
		{
			
			foreach(  $actions as $action )
			{
				$data['in_code'][$table][$action] =   kget( 'iosetting_input_' . $table . '_' . $action  )  ;
				$data['out_code'][$table][$action] =   kget( 'iosetting_output_' . $table . '_' . $action  ) ;
		
				
				$data['ainfo'][$table][$action] = unserialize( kget( 'msetting_' . $table . '_' . $action ));
				$data['in_code'][$table][$action] =   kget( 'iosetting_input_' . $table . '_' . $action  )  ;
				$data['out_code'][$table][$action] =   kget( 'iosetting_output_' . $table . '_' . $action  ) ;
				
				$fields = get_fields( $table );		
				foreach( $fields as $field )
				{
					$finfo = unserialize( kget( 'msetting_' . $table . '_' . $action .  '_' . $field  ) ) ;
					
					
					if( $finfo['required'] == 1 ) $data['requires'][$table][$action][] = $field;
					if( $finfo['input'] == 1 ) $data['inputs'][$table][$action][] = $field;
					if( $finfo['output'] == 1 ) $data['outputs'][$table][$action][] = $field;
					if( $finfo['like'] == 1 ) $data['likes'][$table][$action][] = $field;
					if( $finfo['equal'] == 1 ) $data['equals'][$table][$action][] = $field;
				}
				
			}
			
			// 取得自定义接口
			$data['my_actions'] = get_data( "SELECT * FROM `__meta_code` WHERE `table` = '" . s( $table ) . "' ORDER BY `id` DESC" );
				
				
		}
		
		ob_start();
		@extract( $data );
		require( AROOT . 'meta/api.sample.php' );
		$code = ob_get_contents();
		ob_end_clean();
		$code = "<?php \r\n". $code . '?>';
		
		include AROOT . 'function/phpbeautifier/PhpBeautifier.inc';
	
		$beautify = new PhpBeautifier();
		$beautify -> tokenSpace = true;//put space between tokens
		$beautify -> blockLine = true;//put empty lines between blocks of code (if, while etc)
		$beautify -> optimize = false;//optimize strings (for now), if a double quoted string does not contain variables of special carachters transform it to a single quoted string to save parsing time
		if( v('read') == 1 )
			highlight_string( $beautify -> process( $code ) );
		else
			echo $beautify -> process( $code );
		//echo $code;
		
		/*
		$smarty->assign( 'data' ,  $data  );
		echo $smarty->fetch( AROOT . 'meta/api.sample.php' );
		*/
		
		//echo 'lp3';
	}
	
}