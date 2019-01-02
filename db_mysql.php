<?php
/*======================================================================*\
|| ####################################################################
|| # vBulletin Impex
|| # ---------------------------------------------------------------- # ||
|| # All PHP code in this file is Copyright 2000-2014 vBulletin Solutions Inc.
|| # This code is made available under the Modified BSD License -- see license.txt # ||
|| # http://www.vbulletin.com 
|| ####################################################################
\*======================================================================*/

error_reporting(E_ALL & ~E_NOTICE);
// db class for mysql
// this class is used in all scripts
// do NOT fiddle unless you know what you are doing

if (!defined('IDIR')) { die; }

if (!defined('DB_EXPLAIN'))
{
	define('DB_EXPLAIN', false);
}

if (!defined('DB_QUERIES'))
{
	define('DB_QUERIES', false);
}

class ImpEx_Database_Mysql
{
	const DBARRAY_BOTH  = 0;
	const DBARRAY_ASSOC = 1;
	const DBARRAY_NUM   = 2;

	var $functions = array(
		'connect'            => 'mysql_connect',
		'pconnect'           => 'mysql_pconnect',
		'select_db'          => 'mysql_select_db',
		'query'              => 'mysql_query',
		'query_unbuffered'   => 'mysql_unbuffered_query',
		'fetch_row'          => 'mysql_fetch_row',
		'fetch_array'        => 'mysql_fetch_array',
		'fetch_field'        => 'mysql_fetch_field',
		'free_result'        => 'mysql_free_result',
		'fetch_assoc'        => 'mysql_fetch_assoc',
		'result'             => 'mysql_result',
		'data_seek'          => 'mysql_data_seek',
		'error'              => 'mysql_error',
		'errno'              => 'mysql_errno',
		'affected_rows'      => 'mysql_affected_rows',
		'num_rows'           => 'mysql_num_rows',
		'num_fields'         => 'mysql_num_fields',
		'field_name'         => 'mysql_field_name',
		'insert_id'          => 'mysql_insert_id',
		'escape_string'      => 'mysql_real_escape_string',
		'real_escape_string' => 'mysql_real_escape_string',
		'close'              => 'mysql_close',
		'client_encoding'    => 'mysql_client_encoding',
		'ping'               => 'mysql_ping',
		'get_server_info'	 => 'mysql_get_server_info',
	);

	var $registry = null;

	var $fetch_types = array(
		self::DBARRAY_NUM   => 'MYSQL_NUM',
		self::DBARRAY_ASSOC => 'MYSQL_ASSOC',
		self::DBARRAY_BOTH  => 'MYSQL_BOTH'
	);

	var $appname = 'ImpEx';

	var $appshortname = 'ImpEx';

	var $database = null;

	var $connection = null;

	var $connection_recent = null;

	var $shutdownqueries = array();

	var $sql = '';

	var $reporterror = true;

	var $error = '';

	var $errno = '';

	var $maxpacket = 0;

	var $locked = 0;

	var $querycount = 0;

	function __construct(&$registry)
	{
		if (is_object($registry))
		{
			$this->registry =& $registry;
		}
		else
		{
			trigger_error("ImpEx_Database::Registry object is not an object", E_USER_ERROR);
		}
	}

	function connect($database, $servername, $port, $username, $password, $usepconnect = false, $configfile = '', $charset = '')
	{
		$this->database = $database;

		$port = $port ? $port : 3306;

		$this->connection = $this->db_connect($servername, $port, $username, $password, $usepconnect, $configfile, $charset);

		if ($this->connection)
		{
			$this->select_db($this->database);
			return true;
		}
	}

	function db_connect($servername, $port, $username, $password, $usepconnect, $configfile = '', $charset = '')
	{
		if (function_exists('catch_db_error'))
		{
			set_error_handler('catch_db_error');
		}

		// catch_db_error will handle exiting, no infinite loop here
		do
		{
			$link = $this->functions[$usepconnect ? 'pconnect' : 'connect']("$servername:$port", $username, $password);
		}
		while ($link == false AND $this->reporterror);

		restore_error_handler();

		if (!empty($charset))
		{
			if (function_exists('mysql_set_charset'))
			{
				mysql_set_charset($charset);
			}
			else
			{
				$this->sql = "SET NAMES $charset";
				$this->execute_query(true, $link);
			}
		}

		return $link;
	}

	function select_db($database = '')
	{
		if ($database != '')
		{
			$this->database = $database;
		}

		if ($check_write = @$this->select_db_wrapper($this->database, $this->connection))
		{
			$this->connection_recent =& $this->connection;
			return true;
		}
		else
		{
			$this->connection_recent =& $this->connection;
			return false;
		}
	}

	function select_db_wrapper($database = '', $link = null)
	{
		return $this->functions['select_db']($database, $link);
	}

	function force_sql_mode($mode)
	{
		$reset_errors = $this->reporterror;

		if ($reset_errors)
		{
			$this->hide_errors();
		}

		if (is_string($mode))
		{
			$this->query_write("SET @@sql_mode = '" . $this->escape_string($mode) . "'");
		}

		if ($reset_errors)
		{
			$this->show_errors();
		}
	}

	function execute_query($buffered = true, &$link)
	{
		$this->connection_recent =& $link;
		$this->querycount++;

		if ($queryresult = $this->functions[$buffered ? 'query' : 'query_unbuffered']($this->sql, $link))
		{
			// unset $sql to lower memory .. this isn't an error, so it's not needed
			$this->sql = '';

			return $queryresult;
		}
		else
		{
			$this->halt();

			// unset $sql to lower memory .. error will have already been thrown
			$this->sql = '';
		}
	}

	function query_write($sql, $buffered = true)
	{
		$this->sql =& $sql;
		return $this->execute_query($buffered, $this->connection);
	}

	function query_read($sql, $buffered = true)
	{
		$this->sql =& $sql;
		return $this->execute_query($buffered, $this->connection);
	}

	function query_read_slave($sql, $buffered = true)
	{
		$this->sql =& $sql;
		return $this->execute_query($buffered, $this->connection);
	}

	function query($sql, $buffered = true)
	{
		$this->sql =& $sql;
		return $this->execute_query($buffered, $this->connection);
	}

	function &query_first($sql, $type = DBARRAY_ASSOC)
	{
		$this->sql =& $sql;
		$queryresult = $this->execute_query(true, $this->connection);
		$returnarray = $this->fetch_array($queryresult, $type);
		$this->free_result($queryresult);
		return $returnarray;
	}

	function found_rows()
	{
		$this->sql = "SELECT FOUND_ROWS()";
		$queryresult = $this->execute_query(true, $this->connection_recent);
		$returnarray = $this->fetch_array($queryresult, DBARRAY_NUM);
		$this->free_result($queryresult);

		return intval($returnarray[0]);
	}

	function &query_first_slave($sql, $type = DBARRAY_ASSOC)
	{
		$returnarray = $this->query_first($sql, $type);
		return $returnarray;
	}

	function &query_insert($table, $fields, &$values, $buffered = true)
	{
		return $this->insert_multiple("INSERT INTO $table $fields VALUES", $values, $buffered);
	}

	function &query_replace($table, $fields, &$values, $buffered = true)
	{
		return $this->insert_multiple("REPLACE INTO $table $fields VALUES", $values, $buffered);
	}

	function insert_multiple($sql, &$values, $buffered)
	{
		if ($this->maxpacket == 0)
		{
			// must do a READ query on the WRITE link here!
			$vars = $this->query_write("SHOW VARIABLES LIKE 'max_allowed_packet'");
			$var = $this->fetch_row($vars);
			$this->maxpacket = $var[1];
			$this->free_result($vars);
		}

		$i = 0;
		$num_values = sizeof($values);
		$this->sql = $sql;

		while ($i < $num_values)
		{
			$sql_length = strlen($this->sql);
			$value_length = strlen("\r\n" . $values["$i"] . ",");

			if (($sql_length + $value_length) < $this->maxpacket)
			{
				$this->sql .= "\r\n" . $values["$i"] . ",";
				unset($values["$i"]);
				$i++;
			}
			else
			{
				$this->sql = (substr($this->sql, -1) == ',') ? substr($this->sql, 0, -1) : $this->sql;
				$this->execute_query($buffered, $this->connection);
				$this->sql = $sql;
			}
		}
		if ($this->sql != $sql)
		{
			$this->sql = (substr($this->sql, -1) == ',') ? substr($this->sql, 0, -1) : $this->sql;
			$this->execute_query($buffered, $this->connection);
		}

		if (sizeof($values) == 1)
		{
			return $this->insert_id();
		}
		else
		{
			return true;
		}
	}

	function shutdown_query($sql, $arraykey = -1)
	{
		if ($arraykey === -1)
		{
			$this->shutdownqueries[] = $sql;
			return true;
		}
		else
		{
			$this->shutdownqueries["$arraykey"] = $sql;
			return true;
		}
	}

	function num_rows($queryresult)
	{
		return @$this->functions['num_rows']($queryresult);
	}

	function num_fields($queryresult)
	{
		return @$this->functions['num_fields']($queryresult);
	}

	function field_name($queryresult, $index)
	{
		return @$this->functions['field_name']($queryresult, $index);
	}

	function insert_id()
	{
		return @$this->functions['insert_id']($this->connection);
	}

	function client_encoding()
	{
		return @$this->functions['client_encoding']($this->connection);
	}

	function close()
	{
		return @$this->functions['close']($this->connection);
	}

	function escape_string($string)
	{
		return $this->functions['real_escape_string']($string, $this->connection);
	}

	function escape_string_like($string)
	{
		return str_replace(array('%', '_') , array('\%' , '\_') , $this->escape_string($string));
	}

	function sql_prepare($value)
	{
		if (is_string($value))
		{
			return "'" . $this->escape_string($value) . "'";
		}
		else if (is_numeric($value) AND floatval($value) == $value)
		{
			return $value;
		}
		else if (is_bool($value))
		{
			return $value ? 1 : 0;
		}
		else if (is_null($value))
		{
			return "''";
		}
		else if (is_array($value))
		{
			foreach ($value as $key => $item)
			{
				$value[$key] = $this->sql_prepare($item);
			}
			return $value;
		}
		else
		{
			return "'" . $this->escape_string($value) . "'";
		}
	}

	function fetch_array($queryresult, $type = DBARRAY_ASSOC)
	{
		return @$this->functions['fetch_array']($queryresult, $this->fetch_types["$type"]);
	}

	function fetch_row($queryresult)
	{
		return @$this->functions['fetch_row']($queryresult);
	}

	function fetch_field($queryresult)
	{
		return @$this->functions['fetch_field']($queryresult);
	}

	function fetch_assoc($queryresult)
	{
		return @$this->functions['fetch_assoc']($queryresult);
	}

	function data_seek($queryresult, $index)
	{
		return @$this->functions['data_seek']($queryresult, $index);
	}

	function free_result($queryresult)
	{
		$this->sql = '';
		return @$this->functions['free_result']($queryresult);
	}

	function result($queryresult, $number=0, $field=0)
	{
		$this->sql = '';
		return @$this->functions['result']($queryresult, $number, $field);
	}

	function affected_rows()
	{
		$this->rows = $this->functions['affected_rows']($this->connection_recent);
		return $this->rows;
	}

	function ping()
	{
		if (!@$this->functions['ping']($this->connection))
		{
			$this->close();
			// make database connection
			$this->connect(
				$this->registry->config['Database']['dbname'],
				$this->registry->config['MasterServer']['servername'],
				$this->registry->config['MasterServer']['port'],
				$this->registry->config['MasterServer']['username'],
				$this->registry->config['MasterServer']['password'],
				$this->registry->config['MasterServer']['usepconnect'],
				$this->registry->config['SlaveServer']['servername'],
				$this->registry->config['SlaveServer']['port'],
				$this->registry->config['SlaveServer']['username'],
				$this->registry->config['SlaveServer']['password'],
				$this->registry->config['SlaveServer']['usepconnect'],
				$this->registry->config['Mysqli']['ini_file'],
				(isset($this->registry->config['Mysqli']['charset']) ? $this->registry->config['Mysqli']['charset'] : '')
			);
		}
	}

	function lock_tables($tablelist)
	{
		if (!empty($tablelist) AND is_array($tablelist))
		{
			$sql = '';
			foreach($tablelist AS $name => $type)
			{
				$sql .= (!empty($sql) ? ', ' : '') . TABLE_PREFIX . $name . " " . $type;
			}

			$this->query_write("LOCK TABLES $sql");
			$this->locked = true;
		}
	}

	function unlock_tables()
	{
		// Must be called from exec_shutdown as tables can get stuck locked if pconnects are enabled
		// Note: the above case never actually happens as we skip the lock if pconnects are enabled (to be safe)
		if ($this->locked)
		{
			$this->query_write("UNLOCK TABLES");
		}
	}

	function error()
	{
		if ($this->connection_recent === null)
		{
			$this->error = '';
		}
		else
		{
			$this->error = $this->functions['error']($this->connection_recent);
		}
		return $this->error;
	}

	function errno()
	{
		if ($this->connection_recent === null)
		{
			$this->errno = 0;
		}
		else
		{
			$this->errno = $this->functions['errno']($this->connection_recent);
		}
		return $this->errno;
	}

	function show_errors()
	{
		$this->reporterror = true;
	}

	function hide_errors()
	{
		$this->reporterror = false;
	}

	function halt($errortext = '')
	{
		global $vbulletin;

		if ($this->connection_recent)
		{
			$this->error = $this->error($this->connection_recent);
			$this->errno = $this->errno($this->connection_recent);
		}

		if ($this->reporterror)
		{
			if ($errortext == '')
			{
				$this->sql = "Invalid SQL:\r\n" . chop($this->sql) . ';';
				$errortext =& $this->sql;
			}

			// Try and stop e-mail flooding.
			if (!$vbulletin->options['disableerroremail'])
			{
				$tempdir = @sys_get_temp_dir();
				$unique = 'vb'.md5(COOKIE_SALT).'.err';
				$tempfile = realpath($tempdir).DIRECTORY_SEPARATOR.$unique;

				/* If its less than a minute since the last e-mail
				and the error code is the same as last time, disable e-mail */
				if ($data = @file_get_contents($tempfile))
				{
					$errc = intval(substr($data, 10));
					$time = intval(substr($data, 0, 10));
					if ($time AND (TIMENOW - $time) < 60
						AND intval($this->errno) == $errc)
					{
						$vbulletin->options['disableerroremail'] = true;
					}
					else
					{
						$data = TIMENOW.intval($this->errno);
						@file_put_contents($tempfile, $data);
					}
				}
				else
				{
					$data = TIMENOW.intval($this->errno);
					@file_put_contents($tempfile, $data);
				}
			}

			date_default_timezone_set('UTC');

			$vboptions      =& $vbulletin->options;
			$technicalemail =& $vbulletin->config['Database']['technicalemail'];
			$bbuserinfo     =& $vbulletin->userinfo;
			$requestdate    = date('l, F jS Y @ h:i:s A', TIMENOW);
			$date           = date('l, F jS Y @ h:i:s A');
			$scriptpath     = str_replace('&amp;', '&', $vbulletin->scriptpath);
			$referer        = REFERRER;
			$ipaddress      = IPADDRESS;
			$classname      = get_class($this);

			if ($this->connection_recent)
			{
				$this->hide_errors();
				list($mysqlversion) = $this->query_first("SELECT VERSION() AS version", DBARRAY_NUM);
				$this->show_errors();
			}

			$display_db_error = (VB_AREA == 'Upgrade' OR VB_AREA == 'Install' OR $vbulletin->userinfo['permissions']['adminpermissions'] & $vbulletin->bf_ugp_adminpermissions['cancontrolpanel']);

			// Hide the MySQL Version if its going in the source
			if (!$display_db_error)
			{
				$mysqlversion = '';
			}

			eval('$message = "' . str_replace('"', '\"', file_get_contents(DIR . '/includes/database_error_message.html')) . '";');

			// add a backtrace to the message
			$trace_output = "\n";
			if ($vbulletin->debug OR ($technicalemail != '' AND !$vbulletin->options['disableerroremail']))
			{
				$trace = debug_backtrace();

				foreach ($trace AS $index => $trace_item)
				{
					$param = (in_array($trace_item['function'], array('require', 'require_once', 'include', 'include_once')) ? $trace_item['args'][0] : '');

					// remove path
					$param = str_replace(DIR, '[path]', $param);
					$trace_item['file'] = str_replace(DIR, '[path]', $trace_item['file']);

					$trace_output .= "#$index $trace_item[class]$trace_item[type]$trace_item[function]($param) called in $trace_item[file] on line $trace_item[line]\n";
				}
			}

			if ($vbulletin->debug)
			{
				$message .= "\n\nStack Trace:\n$trace_output\n";
			}

			require_once(DIR . '/includes/functions_log_error.php');
			if (function_exists('log_vbulletin_error'))
			{
				log_vbulletin_error($message, 'database');
			}

			if ($technicalemail != '' AND !$vbulletin->options['disableerroremail'] AND verify_email_vbulletin_error($this->errno, 'database'))
			{
				$bodytext = ($vbulletin->debug ? $message : "$message\n\nStack Trace:\n$trace_output\n");

				// If vBulletinHook is defined then we know that options are loaded, so we can then use vbmail
				if (class_exists('vBulletinHook', false))
				{
					@vbmail($technicalemail, $this->appshortname . ' Database Error!', $bodytext, true, $technicalemail);
				}
				else
				{
					@mail($technicalemail, $this->appshortname . ' Database Error!', preg_replace("#(\r\n|\r|\n)#s", (@ini_get('sendmail_path') === '') ? "\r\n" : "\n", $bodytext), "From: $technicalemail");
				}
			}

			if (defined('STDIN'))
			{
				echo $message;
				exit;
			}

			// send ajax reponse after sending error email
			if ($vbulletin->GPC['ajax'])
			{
				require_once(DIR . '/includes/class_xml.php');
				$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');

				$error = '<p>Database Error</p>';
				if ($vbulletin->debug OR VB_AREA == 'Upgrade')
				{
					$error .= "\r\n\r\n$errortext";
					$error .= "\r\n\r\n{$this->error}";
				}

				eval('$ajaxmessage = "' . str_replace('"', '\"', file_get_contents(DIR . '/includes/database_error_message_ajax.html')) . '";');

				$xml->add_group('errors');
					$xml->add_tag('error', $error);
					$xml->add_tag('error_html', $ajaxmessage);
				$xml->close_group('errors');

				$xml->print_xml();
			}

			if (!headers_sent())
			{
				if (SAPI_NAME == 'cgi' OR SAPI_NAME == 'cgi-fcgi')
				{
					header('Status: 503 Service Unavailable');
				}
				else
				{
					header($_SERVER['SERVER_PROTOCOL'] . ' 503 Service Unavailable');
				}
			}

			if ($display_db_error)
			{
				// display error message on screen
				$message = '<form><textarea rows="15" cols="70" wrap="off" id="message">' . htmlspecialchars_uni($message) . '</textarea></form>';
			}
			else if ($vbulletin->debug)
			{
				// display hidden error message
				$message = "\r\n<!--\r\n" . htmlspecialchars_uni($message) . "\r\n-->\r\n";
			}
			else
			{
				$message = '';
			}

			if ($vbulletin->options['bburl'])
			{
				$imagepath = $vbulletin->options['bburl'];
			}
			else
			{
				// this might not work with too many slashes in the archive
				$imagepath = (VB_AREA == 'Forum' ? '.' : '..');
			}

			eval('$message = "' . str_replace('"', '\"', file_get_contents(DIR . '/includes/database_error_page.html')) . '";');

			// This is needed so IE doesn't show the pretty error messages
			$message .= str_repeat(' ', 512);
			die($message);
		}
		else if (!empty($errortext))
		{
			$this->error = $errortext;
		}
	}

	public function get_server_info()
	{
		return @$this->functions['get_server_info']($this->connection);
	}
}

class ImpEx_Database_Mysqli extends ImpEx_Database_Mysql
{
	var $functions = array(
		'connect'            => 'mysqli_real_connect',
		'pconnect'           => 'mysqli_real_connect', // mysqli doesn't support persistent connections THANK YOU!
		'select_db'          => 'mysqli_select_db',
		'query'              => 'mysqli_query',
		'query_unbuffered'   => 'mysqli_unbuffered_query',
		'fetch_row'          => 'mysqli_fetch_row',
		'fetch_array'        => 'mysqli_fetch_array',
		'fetch_field'        => 'mysqli_fetch_field',
		'fetch_assoc'        => 'mysqli_fetch_assoc',
		'free_result'        => 'mysqli_free_result',
		'data_seek'          => 'mysqli_data_seek',
		'error'              => 'mysqli_error',
		'errno'              => 'mysqli_errno',
		'affected_rows'      => 'mysqli_affected_rows',
		'num_rows'           => 'mysqli_num_rows',
		'num_fields'         => 'mysqli_num_fields',
		'field_name'         => 'mysqli_field_tell',
		'insert_id'          => 'mysqli_insert_id',
		'escape_string'      => 'mysqli_real_escape_string',
		'real_escape_string' => 'mysqli_real_escape_string',
		'close'              => 'mysqli_close',
		'client_encoding'    => 'mysqli_character_set_name',
		'ping'               => 'mysqli_ping',
		'get_server_info'	 => 'mysqli_get_server_info',
	);

	var $fetch_types = array(
		self::DBARRAY_NUM   => MYSQLI_NUM,
		self::DBARRAY_ASSOC => MYSQLI_ASSOC,
		self::DBARRAY_BOTH  => MYSQLI_BOTH
	);

	function db_connect($servername, $port, $username, $password, $usepconnect, $configfile = '', $charset = '')
	{
		if (function_exists('catch_db_error'))
		{
			set_error_handler('catch_db_error');
		}

		$link = mysqli_init();
		$servername = $usepconnect ? 'p:' . $servername : $servername ;

		if (!empty($configfile))
		{
			mysqli_options($link, MYSQLI_READ_DEFAULT_FILE, $configfile);
		}

		// this will execute at most 5 times, see catch_db_error()
		do
		{
			$connect = $this->functions['connect']($link, $servername, $username, $password, '', $port);
		}
		while ($connect == false AND $this->reporterror);

		restore_error_handler();

		if (!empty($charset))
		{
			if (function_exists('mysqli_set_charset'))
			{
				mysqli_set_charset($link, $charset);
			}
			else
			{
				$this->sql = "SET NAMES $charset";
				$this->execute_query(true, $link);
			}
		}

		return (!$connect) ? false : $link;
	}

	function execute_query($buffered = true, &$link)
	{
		$this->connection_recent =& $link;
		$this->querycount++;

		if ($queryresult = $this->functions['query']($link, $this->sql, ($buffered ? MYSQLI_STORE_RESULT : MYSQLI_USE_RESULT)))
		{
			// unset $sql to lower memory .. this isn't an error, so it's not needed
			$this->sql = '';

			return $queryresult;
		}
		else
		{
			$this->halt();

			// unset $sql to lower memory .. error will have already been thrown
			$this->sql = '';
		}
	}

	function select_db_wrapper($database = '', $link = null)
	{
		return $this->functions['select_db']($link, $database);
	}

	function escape_string($string)
	{
		return $this->functions['real_escape_string']($this->connection, $string);
	}

	function field_name($queryresult, $index)
	{
		$field = @$this->functions['fetch_field']($queryresult);
		return $field->name;
	}

	function result($queryresult, $number=0, $field=0)
	{
		$numrows = $this->num_rows($queryresult);

		if ($numrows AND $number <= ($numrows-1) AND $number >= 0)
		{
			$this->data_seek($queryresult, $number);

			$resrow = (is_numeric($field)) ? $this->fetch_row($queryresult) : $this->fetch_assoc($queryresult);

			if (isset($resrow[$field]))
			{
				return $resrow[$field];
			}
		}

		return false;
	}

	function show_errors()
	{
		$this->reporterror = true;
		mysqli_report(MYSQLI_REPORT_ERROR);
	}

	function hide_errors()
	{
		$this->reporterror = false;
		mysqli_report(MYSQLI_REPORT_OFF);
	}
}

class ImpEx_Database_Sqlsrv
{
	var $functions = array(
		'connect' => 'sqlsrv_connect',
		'query' => 'sqlsrv_query',
		'fetch_array' => 'sqlsrv_fetch_array',
		'error' => 'sqlsrv_errors'
	);
}

class ImpEx_Database_ODBC
{
	var $functions = array(
		'connect' => 'odbc_connect',
		'query' => 'odbc_exec',
		'fetch_array' => 'odbc_fetch_array'
	);
}

class ImpEx_Database_Mssql
{
	var $functions = array(
		'connect' => 'mssql_connect',
		'select_db' => ' mssql_select_db',
		'query' => 'mssql_query',
		'fetch_assoc' => 'mssql_fetch_assoc',
		'fetch_array' => 'mssql_fetch_array',
		'error' => 'mssql_get_last_message'
	);

	function db_connect($servername, $port, $username, $password, $usepconnect, $configfile = '', $charset = '')
	{
		$this->link = $this->functions['connect']($server, $user, $password);

		if (!$this->link)
		{
			$this->halt('Link-ID == false, connect failed');
			return false;
		}

		$this->select_db($database);
		return true;
	}

	function select_db_wrapper($database = '', $link = null)
	{
		if (!($this->msdb = $this->functions['select_db']($database, $link)))
		{
			echo 'Cannot use database ' . $database . '<br />';
			return false;
		}

		return true;
	}

	function execute_query($buffered = true, &$link)
	{
		if ($q = @$this->functions['query']($this->sql))
		{
			return $q;
		}
		else
		{
			$this->halt('Invalid SQL: ' . $this->sql);
			exit;
		}
	}
}

















class DB_Sql_vb_impex
{
	var $database = '';
	var $type = '';	// mysql/mssql/sqlsrv (mssql PHP 5.3)
	var $odbcconnect = '';

	var $link_id = 0;

	var $errdesc = '';
	var $errno = 0;
	var $reporterror = 1;

	var $appname = 'vBulletin';
	var $appshortname = 'vBulletin (cp)';

	var $require_db_reselect = false; // deal with bentness in php < 4.2.0

	function connect($server, $user, $password, $usepconnect, $charset = "")
	{
		if ($this->type == 'sqlsrv')
		{
			$connectinfo = array(
				'UID'                  => $user,
				'PWD'                  => $password,
				'Database'             => $this->database,
				'ReturnDatesAsStrings' => true,
			);
			if (!($this->link_id = sqlsrv_connect($server, $connectinfo)))
			{
				echo 'Connection failed';
				return false;
			}
		}

		if ($this->type == 'mssql')
		{
			$this->link_id = mssql_connect($server, $user, $password);

			if (!$this->link_id)
			{
				$this->halt('Link-ID == false, connect failed');
				return false;
			}

			$this->select_db($this->database);
			return true;
		}


		if ($this->type == "odbc")
		{
			$this->odbcconnect = odbc_connect("Driver={SQL Server};Server=$server;Database={$this->database}",$user,$password);
			$this->link_id = $this->odbcconnect;
		}

		if ($this->type == "mysqli")
		{
			$this->link_id = mysqli_init();
			mysqli_real_connect($this->link_id, $server, $user, $password);
			$this->select_db($this->database);
		}

		// connect to db server

		global $querytime;
		// do query

		if (DB_QUERIES)
		{
			echo "Connecting to database\n";

			global $pagestarttime;
			$pageendtime = microtime();
			$starttime = explode(' ', $pagestarttime);
			$endtime = explode(' ', $pageendtime);

			$beforetime = $endtime[0] - $starttime[0] + $endtime[1] - $starttime[1];

			echo "Time before: $beforetime\n";
			if (function_exists('memory_get_usage'))
			{
				echo "Memory Before: " . number_format((memory_get_usage() / 1024)) . 'KB' . " \n";
			}
		}

		if (0 == $this->link_id)
		{
			if ($usepconnect == true)
			{
				if (!function_exists('mysql_pconnect'))
				{
					die ('function mysql_pconnect() called though not supported in the PHP build');
				}

				if (phpversion() >= '4.2.0')
				{
					$this->link_id = @mysql_pconnect($server, $user, $password, true);
				}
				else
				{
					$this->link_id = @mysql_pconnect($server, $user, $password);
				}
			}
			else
			{
				if (!function_exists('mysql_connect'))
				{
					die ('function mysql_connect() called though not supported in the PHP build');
				}

				if (phpversion() >= '4.2.0')
				{
					$this->link_id = @mysql_connect($server, $user, $password, true);
				}
				else
				{
					$this->link_id = @mysql_connect($server, $user, $password);
				}
			}

			if (!$this->link_id)
			{
				$this->halt('Link-ID == false, connect failed');
				return false;
			}

			$this->select_db($this->database);

			if (DB_QUERIES)
			{
				$pageendtime = microtime();
				$starttime = explode(' ', $pagestarttime);
				$endtime = explode(' ', $pageendtime);

				$aftertime = $endtime[0] - $starttime[0] + $endtime[1] - $starttime[1];
				$querytime += $aftertime - $beforetime;

				echo "Time after: $aftertime\n";
				echo "Time taken: " . ($aftertime - $beforetime) . "\n";

				if (function_exists('memory_get_usage'))
				{
					echo "Memory After: " . number_format((memory_get_usage() / 1024)) . 'KB' . " \n";
				}

				echo "\n<hr />\n\n";
			}

			if (!empty($charset))
			{
                $this->query("SET NAMES {$charset}");
			}

			return true;
		}
	}

	function affected_rows()
	{
		$this->rows = mysql_affected_rows($this->link_id);
		return $this->rows;
	}

	function geterrdesc()
	{
		$this->error = mysql_error($this->link_id);
		return $this->error;
	}

	function geterrno()
	{
		if ($this->type == 'mysql')
		{
			$this->errno = mysql_errno($this->link_id);
			return $this->errno;
		}
	}

	function select_db($database = '')
	{
		// select database
		if (!empty($database))
		{
			$this->database = $database;
		}

		if ($this->type == 'mssql')
		{
			if (!($this->msdb = mssql_select_db($this->database, $this->link_id)))
			{
				echo 'Cannot use database ' . $this->database . '<br />';
				return false;
			}

			return true;
		}


		if ($this->type == 'mysql')
		{
			$connectcheck = @mysql_select_db($this->database, $this->link_id);

			if ($connectcheck)
			{
				return true;
			}
			else
			{
				$this->halt('cannot use database ' . $this->database);
				return false;
			}
		}

		if ($this->type == 'mysqli')
		{
			$connectcheck = @mysqli_select_db($this->link_id, $this->database);

			if ($connectcheck)
			{
				return true;
			}
			else
			{
				$this->halt('cannot use database ' . $this->database);
				return false;
			}
		}
	}

	function query_unbuffered($query_string)
	{
		return $this->query($query_string, 'mysqli_unbuffered_query');
	}

	function shutdown_query($query_string, $arraykey = 0)
	{
		global $shutdownqueries;

		if (NOSHUTDOWNFUNC)
		{
			return $this->query($query_string);
		}
		else if ($arraykey)
		{
			$shutdownqueries["$arraykey"] = $query_string;
		}
		else
		{
			$shutdownqueries[] = $query_string;
		}
	}

	function query($query_string, $query_type = 'mysqli_query')
	{
		global $query_count, $querytime;

		if ($this->type == 'sqlsrv')
		{
			if ($q = @sqlsrv_query($this->link_id, $query_string))
			{
				return $q;
			}
			else if ($q === false)
			{
				$this->halt('Invalid SQL: ' . $query_string);
				exit;
			}
		}

		if ($this->type == 'mssql')
		{
			if ($q = @mssql_query($query_string))
			{
				return $q;
			}
			else
			{
				$this->halt('Invalid SQL: ' . $query_string);
				exit;
			}
		}

		if ($this->type == 'odbc')
		{
			return @odbc_exec($this->odbcconnect, $query_string);
		}

		if (DB_QUERIES)
		{
			echo 'Query' . ($query_type == 'mysql_unbuffered_query' ? ' (UNBUFFERED)' : '') . ":\n<i>" . htmlspecialchars($query_string) . "</i>\n";

			global $pagestarttime;
			$pageendtime = microtime();
			$starttime = explode(' ', $pagestarttime);
			$endtime = explode(' ', $pageendtime);

			$beforetime = $endtime[0] - $starttime[0] + $endtime[1] - $starttime[1];

			echo "Time before: $beforetime\n";
			if (function_exists('memory_get_usage'))
			{
				echo "Memory Before: " . number_format((memory_get_usage() / 1024)) . 'KB' . " \n";
			}
		}

		// do query
		if ($this->require_db_reselect)
		{
			$this->select_db($this->database);
		}

		// Do the actual query ::
		if ($this->type == 'mysqli')
		{
			if (use_utf8_encode)
			{
				$query_id = $query_type($this->link_id, utf8_encode($query_string));
			}
			else
			{
				$query_id = $query_type($this->link_id, $query_string);
			}
		}
		else
		{
			if (use_utf8_encode)
			{
				$query_id = $query_type(utf8_encode($query_string), $this->link_id);
			}
			else
			{
				$query_id = $query_type($query_string, $this->link_id);
			}
		}


		if (!$query_id)
		{
			$this->halt('Invalid SQL: ' . $query_string);
		}

		$query_count++;

		if (DB_QUERIES)
		{
			$pageendtime = microtime();
			$starttime = explode(' ', $pagestarttime);
			$endtime = explode(' ', $pageendtime);

			$aftertime = $endtime[0] - $starttime[0] + $endtime[1] - $starttime[1];
			$querytime += $aftertime - $beforetime;

			echo "Time after: $aftertime\n";
			echo "Time taken: " . ($aftertime - $beforetime) . "\n";

			if (function_exists('memory_get_usage'))
			{
				echo "Memory After: " . number_format((memory_get_usage() / 1024)) . 'KB' . " \n";
			}

			if (DB_EXPLAIN AND preg_match('#(^|\s)SELECT\s+#si', $query_string))
			{
				$explain_id = mysql_query("EXPLAIN " . $query_string, $this->link_id);
				echo "</pre>\n";
				echo '
				<table width="100%" border="1" cellpadding="2" cellspacing="1">
				<tr>
					<td><b>table</b></td>
					<td><b>type</b></td>
					<td><b>possible_keys</b></td>
					<td><b>key</b></td>
					<td><b>key_len</b></td>
					<td><b>ref</b></td>
					<td><b>rows</b></td>
					<td><b>Extra</b></td>
				</tr>
				';

				while ($array = mysql_fetch_assoc($explain_id))
				{
					echo "
					<tr>
						<td>$array[table]&nbsp;</td>
						<td>$array[type]&nbsp;</td>
						<td>$array[possible_keys]&nbsp;</td>
						<td>$array[key]&nbsp;</td>
						<td>$array[key_len]&nbsp;</td>
						<td>$array[ref]&nbsp;</td>
						<td>$array[rows]&nbsp;</td>
						<td>$array[Extra]&nbsp;</td>
					</tr>
					";
				}
				echo "</table>\n<br /><hr />\n";
				echo "\n<pre>";
			}
			else
			{
				echo "\n<hr />\n\n";
			}
		}

		return $query_id;
	}

	function fetch_array($query_id, $type = DBARRAY_BOTH)
	{
		if ($this->type == 'sqlsrv')
		{
			if (do_mysql_fetch_assoc)
			{
				return @sqlsrv_fetch_array($query_id, SQLSRV_FETCH_ASSOC);
			}
			else
			{
				return @sqlsrv_fetch_array($query_id);
			}
		}

		if ($this->type == 'mssql')
		{
			// retrieve row
			if (do_mysql_fetch_assoc)
			{
				return @mssql_fetch_assoc($query_id);
			}
			else
			{
				return @mssql_fetch_array($query_id);
			}
		}

		if ($this->type == 'odbc')
		{
			return odbc_fetch_array($query_id);
		}

		if ($this->type == 'mysql')
		{
			// retrieve row
			if (do_mysql_fetch_assoc)
			{
				return $this->fetch_assoc($query_id);
			}
			else
			{
				return $this->fetch_array($query_id);
			}
		}
	}

	function fetch_assoc($query_id)
	{
		if ($this->type == 'mysqli')
		{
			return @mysqli_fetch_assoc($query_id);
		}

		return @mysql_fetch_assoc($query_id);
	}

	function fetch_row($query_id)
	{
		if ($this->type == 'mysqli')
		{
			return mysqli_fetch_row($query_id);
		}

		return mysql_fetch_row($query_id);
	}

	function free_result($query_id)
	{
		// retrieve row
		return @mysql_free_result($query_id);
	}

	function query_first($query_string, $type = DBARRAY_BOTH)
	{
		// does a query and returns first row
		$query_id = $this->query($query_string);
		$returnarray = $this->fetch_array($query_id, $type);
		$this->free_result($query_id);
		$this->lastquery = $query_string;

		if ($this->type == 'odbc')
		{
			$moo = array (0 => array_pop($returnarray));
	 		return $moo;
		}

		return $returnarray;
	}

	function result($query_id, $row = 0, $col = 0)
	{
		if ($this->type == 'mysqli')
		{
			$numrows = $this->num_rows($query_id);

			if ($numrows AND $row <= ($numrows-1) AND $row >= 0)
			{
				$this->data_seek($row, $res);

				$resrow = (is_numeric($col)) ? $this->fetch_row($res) : $this->fetch_assoc($res);

				if (isset($resrow[$col]))
				{
					return $resrow[$col];
				}
			}

			return false;
		}

		return mysql_result($query_id);
	}

	function data_seek($pos, $query_id)
	{
		// goes to row $pos
		if ($this->type == 'mysqli')
		{
			return @mysqli_data_seek($query_id, $pos);
		}

		return @mysql_data_seek($query_id, $pos);
	}

	function num_rows($query_id)
	{
		// returns number of rows in query
		if ($this->type == 'mysqli')
		{
			return mysqli_num_rows($query_id);
		}

		return mysql_num_rows($query_id);
	}

	function num_fields($query_id)
	{
		// returns number of fields in query
		if ($this->type == 'mysqli')
		{
			return mysqli_num_fields($query_id);
		}

		return mysql_num_fields($query_id);
	}

	function field_name($query_id, $columnnum)
	{
		// returns the name of a field in a query
		if ($this->type == 'mysqli')
		{
			return mysqli_fetch_field_direct($query_id, $columnnum)->name;
		}

		return mysql_field_name($query_id, $columnnum);
	}

	function insert_id()
	{
		// returns last auto_increment field number assigned
		if ($this->type == 'mysqli')
		{
			return mysqli_insert_id($this->link_id);
		}

		return mysql_insert_id($this->link_id);
	}

	function close()
	{
		// closes connection to the database
		if ($this->type == 'mysqli')
		{
			return mysqli_close($this->link_id);
		}

		return mysql_close($this->link_id);
	}

	function print_query($htmlize = true)
	{
		// prints out the last query executed in <pre> tags
		$querystring = $htmlize ? htmlspecialchars($this->lastquery) : $this->lastquery;
		echo "<pre>$querystring</pre>";
	}

	function escape_string($string)
	{
		// escapes characters in string depending on Characterset
		if ($this->type == 'mysqli')
		{
			return mysqli_real_escape_string($this->link_id, $string);
		}

		return mysql_escape_string($string);
	}

	function halt($msg)
	{
		if ($this->link_id)
		{
			if ($this->type == 'mysql')
			{
				$this->errdesc = mysql_error($this->link_id);
				$this->errno = mysql_errno($this->link_id);
			}

			if ($this->type == 'mysqli')
			{
				$this->errdesc = mysqli_error($this->link_id);
				$this->errno = mysqli_errno($this->link_id);
			}

			if ($this->type == 'sqlsrv')
			{
				if (($errors = @sqlsrv_errors(SQLSRV_ERR_ERRORS)) != null)
				{
					foreach ($errors as $error)
					{
						$this->errdesc .= 'SQLSTATE: '. $error['SQLSTATE']. ' | ';
						$this->errdesc .= 'CODE: ' . $error['code'] . ' | ';
						$this->errdesc .= 'MESSAGE: ' . $error['message'] . "\r\n";
           			}
				}
			}

			if ($this->type == 'mssql')
			{
				$this->errdesc = @mssql_get_last_message();
			}
		}

		// prints warning message when there is an error
		global $technicalemail, $bbuserinfo, $vboptions, $_SERVER;

		if ($this->reporterror == 1)
		{
			$delimiter = "\r\n";

			$message  = 'ImpEx Database error' . "$delimiter$delimiter";
			$message .= $this->type . ' error: ' . $msg . "$delimiter$delimiter";
			$message .= $this->type . ' error: ' . $this->errdesc . "$delimiter$delimiter";
			if ($this->errno)
			{
				$message .= $this->type . ' error number: ' . $this->errno . "$delimiter$delimiter";
			}
			$message .= 'Date: ' . date('l dS of F Y h:i:s A') . $delimiter;
			$message .= 'Database: ' . $this->database . $delimiter;
			if ($this->type == 'mysql')
			{
				$message .= 'MySQL error: ' .mysql_error() . $delimiter;
			}

			echo "<html><head><title>ImpEx Database Error</title>";
			echo "<style type=\"text/css\"><!--.error { font: 11px tahoma, verdana, arial, sans-serif; }--></style></head>\r\n";
			echo "<body></table></td></tr></table></form>\r\n";
			echo "<blockquote><p class=\"error\">&nbsp;</p><p class=\"error\"><b>There seems to have been a problem with the database.</b><br />\r\n";
			echo "<form><textarea class=\"error\" rows=\"15\" cols=\"100\" wrap=\"off\">" . htmlspecialchars($message) . "</textarea></form></blockquote>";
			echo "\r\n\r\n</body></html>";
			exit;
		}
	}
}

/*======================================================================*/
?>