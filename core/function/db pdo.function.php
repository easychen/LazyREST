<?php

function db()
{
	if( !isset( $GLOBALS['LZ_DB'] ) )
	{
		include_once( AROOT .  'config/db.config.php' );

		$db_config = $GLOBALS['config']['db'];
		try
		{
			 $GLOBALS['LZ_DB'] = new PDO("{$db_config['db_type']}:host={$db_config['db_host']};port={$db_config['db_port']};dbname={$db_config['db_name']}", $db_config['db_user'],  $db_config['db_password'] );

			$GLOBALS['LZ_DB']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
	
		}
		catch(PDOException $e)
		{
			echo $e->getMessage();
			return false;
		}
				
		$GLOBALS['LZ_DB']->exec( "SET NAMES UTF8" );
	}

	
	return $GLOBALS['LZ_DB'];
}


function s( $str , $db = NULL )
{
	if( $db == NULL ) $db = db();
	return $db->quote( $str );
}

function get_data( $sql , $db = NULL )
{
	if( $db == NULL ) $db = db();
	
	$GLOBALS['LZ_LAST_SQL'] = $sql;
	$data = Array();
	try 
	{
		$result = $db->query($sql);

		while( $row = $result->fetch(PDO::FETCH_ASSOC) )
		{
			$data[] = $row;
		}
	}
	catch(PDOException $e)
    {
		echo $e->getMessage();
		return false;
    }

	if( count( $data ) > 0 )
		return $data;
	else
		return false;
}

function get_line( $sql , $db = NULL )
{
	$data = get_data( $sql , $db  );
	return @reset($data);
}

function get_var( $sql , $db = NULL )
{
	$data = get_line( $sql , $db  );
	return $data[ @reset(@array_keys( $data )) ];
}

function last_id( $db = NULL )
{
	if( $db == NULL ) $db = db();
	return $db->lastInsertId();
}



function run_sql( $sql , $db = NULL ) 
{
	if( $db == NULL ) $db = db();
	try 
	{
		$ret = $db->exec( $sql );
	}
	catch( PDOException $e  )
	{
		$GLOBALS['LZ_DB_LAST_ERROR'] = $e->getMessage();
		return false;
	}
	
	return $ret;
}

function db_error()
{
	if( isset( $GLOBALS['LZ_DB_LAST_ERROR'] ) )
	return $GLOBALS['LZ_DB_LAST_ERROR'];
}

function close_db( $db = NULL )
{
	if( $db == NULL )
		$GLOBALS['LZ_DB'] = NULL ;
	else
		$db = NULL;
}

?>