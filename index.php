<?php
/*======================================================================*\
|| ######################################################################## ||
|| # vBulletin Impex
|| # ----------------------------------------------------------------
|| # All PHP code in this file is Copyright 2000-2014 vBulletin Solutions Inc.
|| # This code is made available under the Modified BSD License -- see license.txt
|| # http://www.vbulletin.com
|| ######################################################################## ||
\*======================================================================*/

// #############################################################################
// Set time limit, error handeling and aborting
// #############################################################################

if (function_exists('set_time_limit') AND get_cfg_var('safe_mode')==0)
{
	@set_time_limit(0);
}

ignore_user_abort(true);
error_reporting(E_ALL  & ~E_NOTICE);

// #############################################################################
// Define constants
// #############################################################################

define('IDIR', (($getcwd = getcwd()) ? $getcwd : '.'));
define('VB_AREA', 'ImpEx');


// #############################################################################
// Require config & Language
// #############################################################################

if(!is_file(IDIR . '/ImpExConfig.php'))
{
	echo 'Cannot find ImpExConfig.php, have you configured the file and renamed it ?';
	exit;
}
else
{
	require_once (IDIR . '/ImpExConfig.php');
	require_once (IDIR . '/impex_language_' . $impexconfig['system']['language'] . '.php');
}


// #############################################################################
// Requires
// #############################################################################

$auth_redirect = 'index.php';
require_once (IDIR . '/impex_auth.php');
require_once (IDIR . '/db_mysql.php');

require_once (IDIR . '/ImpExFunction.php');
require_once (IDIR . '/ImpExSession.php');
require_once (IDIR . '/ImpExController.php');
require_once (IDIR . '/ImpExDisplay.php');
require_once (IDIR . '/ImpExDisplayWrapper.php');


// #############################################################################
// Checking for standalnoe
// #############################################################################

if (file_exists('../includes/config.php')) // If that is there then its installed
{
	// If the admincp was renamed, lets try and find it depending on vBulletin version
	$admincpdir = '';

	require('../includes/config.php');

	if (!empty($admincpdir)) // 3.0.x will overwrite this
	{
		// Version 3.0.x
		chdir("../" . $admincpdir . "/");
	}
	else if (!empty($config['Misc']['admincpdir']))
	{
		if (is_dir("../" . $config['Misc']['admincpdir'] . "/"))
		{
			// Version 3.6.x
			chdir("../");
			require_once('./includes/adminfunctions.php'); // Only for 3.6.x
		}
		else
		{
			// The config.php path is invalid
			die('config.php admincp path does not exist');
		}
	}
	else
	{
		// Should never be here.
	}

	if (!defined('DIR'))
	{
		define('DIR', (($getcwd = getcwd()) ? $getcwd : '.'));
	}

	// Get the admincp global
	require_once(DIR . '/global.php'); // Works for 3.6.x and 3.0.x as global didn't change/move.

	$usewrapper = true;
}
else
{
	// Running standalone
	if (!defined('DIR'))
	{
		define('DIR', (($getcwd = getcwd()) ? $getcwd : '.'));
	}

	chdir('../'); // make sure our includes use the same paths


	$usewrapper = false;
}

// #############################################################################
// Try to locate vBulletin config, or use ImpExConfig
// #############################################################################

// $usewrapper is a flag for standalone, so if its true were installed
if ($usewrapper)
{
	require_once (DIR . '/includes/config.php');

	$using_local_config = '<p>' . $impex_phrases['using_local_config'] . '</p>';

	// Only if it is all there or a 3.5 config file
	if ($config['Database']['dbtype'] AND $config['MasterServer']['servername'] AND $config['MasterServer']['username']	AND $config['MasterServer']['password'] AND $config['Database']['dbname'])
	{
		// Over write ImpExConfig.php
		$impexconfig['target']['databasetype']	= 'mysqli';
		$impexconfig['target']['server']		= trim($config['MasterServer']['servername']) . ":" . trim($config['MasterServer']['port']);
		$impexconfig['target']['user']			= trim($config['MasterServer']['username']);
		$impexconfig['target']['password']		= trim($config['MasterServer']['password']);
		$impexconfig['target']['database']		= trim($config['Database']['dbname']);
		$impexconfig['target']['tableprefix']	= trim($config['Database']['tableprefix']);
		$impexconfig['target']['charset']		= trim($config['Mysqli']['charset']);
	}
}
else
{
	$using_local_config = '<p>' . $impex_phrases['using_impex_config'] . '</p>';
}


// #############################################################################
// Database connect & session start
// #############################################################################

$ImpEx = new ImpExController();

$dbtype = strtolower($impexconfig['target']['databasetype']);

if ($dbtype == 'mysqli')
{
	$Db_target = new ImpEx_Database_Mysqli($ImpEx);
	$Db_source = new ImpEx_Database_Mysqli($ImpEx);
}
else
{
	$Db_target = new ImpEx_Database_Mysql($ImpEx);
	$Db_source = new ImpEx_Database_Mysql($ImpEx);
}

$Db_target->appname 		= 'ImpEx Target';
$Db_target->appshortname 	= 'ImpEx Target';

$Db_target->connect(
	$impexconfig['target']['database'],
	$impexconfig['target']['server'],
	3306,
	$impexconfig['target']['user'],
	$impexconfig['target']['password'],
	$impexconfig['target']['persistent'],
	'',
	$impexconfig['target']['charset']
);

// Allow setting of SQL mode, not generally required
if (isset($impexconfig['system']['set_sql_mode']))
{
	$Db_target->force_sql_mode($impexconfig['system']['set_sql_mode']);
}
else
{
	$Db_target->force_sql_mode(''); // Force blank mode if none set, avoids Strict Mode issues.
}

$session_state = $ImpEx->return_session($Db_target, $impexconfig['target']['tableprefix']);

if ($session_state)
{
	$ImpExSession = $session_state;
}
else
{
	$ImpExSession = new ImpExSession();
}


// #############################################################################
// Requires ImpExDatabase version (has to be done here as it needs the session)
// #############################################################################

require_once(IDIR . '/ImpExDatabaseCore.php');

#ImpExDatabase_<product>_version.php

switch ($ImpExSession->get_session_var('targetsystem'))
{
	case 400:
		require_once (IDIR . '/ImpExDatabase_400.php');
		break;
	case 360:
		require_once (IDIR . '/ImpExDatabase_360.php');
		break;
	case 350:
		require_once (IDIR . '/ImpExDatabase_350.php');
		break;
	case 309:
		require_once (IDIR . '/ImpExDatabase_309.php');
		break;
	case 'blog10':
		require_once (IDIR . '/ImpExDatabase_blog_001.php');
		break;
	case 'blog40':
		require_once (IDIR . '/ImpExDatabase_blog_400.php');
		break;
	case 'cms10':
		require_once (IDIR . '/ImpExDatabase_cms_001.php');
		break;
	default:
		require_once (IDIR . '/ImpExDatabase_360.php');
		break;
}

// Module extends database
require_once (IDIR . '/ImpExModule.php');
require_once (IDIR . '/ImpExData.php');

// #############################################################################
// Instantiate ImpExDisplay
// #############################################################################

if ($usewrapper)
{
	// Use internal vBulletin rendering functions from AdminCP
	$ImpExDisplay = new ImpExDisplayWrapper();
}
else
{
	// Use standalone rendreing functions
	$ImpExDisplay = new ImpExDisplay();
}

$ImpExDisplay->phrases =& $impex_phrases;

// #############################################################################
// create vbfields
// #############################################################################

if ($ImpExSession->get_session_var('vbfields') != 'done')
{
	require_once(IDIR. '/vbfields.php');
	$queries = &retrieve_vbfields_queries($impexconfig['target']['tableprefix']);

	foreach ($queries AS $query)
	{
		$Db_target->query($query);
	}

	$ImpExSession->add_session_var('vbfields', 'done');
}

// #############################################################################
// initalise error store
// #############################################################################

if ($ImpExSession->get_session_var('errortable') != 'done')
{
	// Just incase the session was removed and the error table is still there.
	$Db_target->query("
		DROP TABLE IF EXISTS " . $impexconfig['target']['tableprefix'] . "impexerror
	");

	// Define MySQL type/engine following MySQL version (ImpEx can be run on 4.0.0 or 4.2.5)
	$mysqlversion = $Db_target->query_first("
		SELECT VERSION() AS version
	");

	if (version_compare($mysqlversion['version'], '5.2', '=<'))
	{
		$engine = 'TYPE';
	}
	else
	{
		$engine = 'ENGINE';
	}

	// Create a new one.
	$Db_target->query("
		CREATE TABLE " . $impexconfig['target']['tableprefix'] . "impexerror (
			errorid BIGINT(20) UNSIGNED NOT NULL auto_increment,
			errortype VARCHAR(10) NOT NULL DEFAULT '',
			classnumber VARCHAR(3) NOT NULL DEFAULT '',
			importid BIGINT(20) NOT NULL DEFAULT 0,
			error VARCHAR(250) DEFAULT 'NULL',
			remedy VARCHAR(250) DEFAULT 'NULL',
			PRIMARY KEY (errorid)
		) " . $engine . "=MyISAM
	");

	$ImpExSession->add_session_var('errortable', 'done');
}

$ImpExSession->_target_db =& $Db_target;

// #############################################################################
// Add vars to session
// #############################################################################

$ImpExSession->add_session_var('systempath', IDIR);
$ImpExSession->add_session_var('sourceexists', $impexconfig['sourceexists']);

$ImpExSession->add_session_var('targettableprefix', $impexconfig['target']['tableprefix']);
$ImpExSession->add_session_var('targetdatabasetype', strtolower($impexconfig['target']['databasetype']));

$ImpExSession->add_session_var('sourcetableprefix', $impexconfig['source']['tableprefix']);
$ImpExSession->add_session_var('sourcedatabasetype', strtolower($impexconfig['source']['databasetype']));

$ImpExSession->add_session_var('errorlogging', $impexconfig['system']['errorlogging']);
$ImpExSession->add_session_var('pagespeed', $impexconfig['system']['pagespeed']);

if ($impexconfig['sourceexists'])
{
	if ($impexconfig['source']['databasetype'] == 'mssql')
	{
		// Check if mssql support is in php or should a connection be made via pure style .......
		if (!function_exists('mssql_connect'))
		{
			if (function_exists('sqlsrv_connect'))
			{
				$impexconfig['source']['databasetype'] = 'sqlsrv';
			}
			else
			{
				$ImpExDisplay->display_error($ImpExDisplay->phrases['no_mssql_support']);
				$ImpExDisplay->display_error($ImpExDisplay->phrases['no_mssql_support_link']);
				exit;
			}
		}
	}

	$Db_source->appname 		= 'ImpEx Source';
	$Db_source->appshortname 	= 'ImpEx Source';

	$Db_source->connect(
		$impexconfig['source']['database'],
		$impexconfig['source']['server'],
		3306,
		$impexconfig['source']['user'],
		$impexconfig['source']['password'],
		$impexconfig['source']['persistent'],
		'',
		$impexconfig['source']['charset']
	);

	if ($Db_source->connection)
	{ // got connected
		switch ($Db_source->errno())
		{
			case 1046:
				$ImpExDisplay->display_error($ImpExDisplay->phrases['no_source_set']);
				exit;
			break;
			case 1049:
				$ImpExDisplay->display_error($ImpExDisplay->phrases['source_not_exist']);
				exit;
			break;
		}
	}
	else
	{
		$ImpExDisplay->display_error($ImpExDisplay->phrases['failed_connection']);
		exit;
	}

	// php versions before 4.2.0 do nasty things with multiple connections to the same server
	// See http://uk.php.net/manual/en/function.mysql-connect.php
	if (($Db_target->connection === $Db_source->connection) AND phpversion() < '4.2.0')
	{
		$Db_target->require_db_reselect = true;
		$Db_source->require_db_reselect = true;
	}
}

$ImpEx->get_post_values($ImpExSession, $_POST);


// #############################################################################
// Autosubmit
// #############################################################################

$ImpExDisplay->update_basic('autosubmit', $ImpExSession->get_session_var('autosubmit'));


// #############################################################################
// Autosubmit & Home
// #############################################################################

$currentmoduleworking	= $ImpExSession->any_working();
$system					= $ImpExSession->get_session_var('system');
$module 				= $ImpExSession->get_session_var('module');

if ($currentmoduleworking != NULL)
{
	$start_at = intval($ImpExSession->get_session_var('startat'));
	$per_page = intval($ImpExSession->get_session_var('perpage'));

	// Set the title here before the default is set
	$ImpExDisplay->_screenbasic['title'] = $ImpExSession->_moduletitles[$module] . " {$start_at} :: " . ($start_at + $per_page);
}

if ($module == '000' OR $module == NULL)
{
	if ($system != '' AND $system != 'NONE')
	{
		// When there is a system chosen, but not running, build the module list.
		require_once (IDIR . "/systems/" . $system . "/000.php");
		$ImpExSession->build_module_list($ImpExDisplay);
	}
	else
	{
		if ($system == '')
		{
			// Catching a blank string
			$ImpExSession->set_session_var('system', 'NONE');
		}
		else
		{
			// Nothing chosen yet
			$ImpExSession->set_session_var('system', $system);
			$ImpExDisplay->update_basic('title', $system);
		}
	}
}

echo $ImpExDisplay->page_header() .
		'<br /> <div align="center"><a href="help.php">' . $ImpExDisplay->phrases['db_cleanup'] .
		'</a> ||| <a href="http://www.vbulletin.com/docs/html/impex" target="blank_"> ' .
		$ImpExDisplay->phrases['online_manual'] . '</a></div>';

if ($using_local_config)
{
	echo '<div align="center"> ' . $using_local_config . '</div><br />';
}


// #############################################################################
// Resume
// #############################################################################

if ($currentmoduleworking != NULL)
{
	// Ensure we have the $system_000.php module to extend from
	require_once (IDIR . '/systems/' . $system . '/000.php');

	// Get the one we are working with.
	require_once (IDIR . '/systems/' . $system . '/' . $currentmoduleworking . '.php');

	// Create that class
	$classname = $system . '_' . $currentmoduleworking;
	$ModuleCall = new $classname($ImpExSession);

	$ModuleCall->system = $system;

	if ($module == '001' AND $ImpExSession->get_session_var('targetsystem') == 400)
	{
		$ModuleCall->_import_ids = array_merge($ModuleCall->_import_ids, $ModuleCall->_import_ids_400);
	}

	// Then call resume on it
	$ModuleCall->resume($ImpExSession, $ImpExDisplay, $Db_target, $Db_source);
}

// #############################################################################
// Init
// #############################################################################

if ($module != '000' AND $module != NULL AND $currentmoduleworking == FALSE)
{
	$ImpExDisplay->update_basic('displaymodules', 'FALSE');

	// Ensure we have the $system_000.php module to extend from
	if (is_file(IDIR . '/systems/' . $system . '/000.php'))
	{
		require_once(IDIR . '/systems/' . $system . '/000.php');
	}
	else
	{
		die($ImpExDisplay->phrases['no_system']);
	}

	// Check if its a core module
	if ($module < 900)
	{
		require_once (IDIR . '/systems/' . $system . '/' . $module . '.php');
		// Create the name of the class to instantiate
		$classname = "{$system}_{$module}";
	}
	else
	{
		require_once (IDIR . '/cleanup.php');
		// Create the name of the class to instantiate
		$classname = "core_{$module}";
	}

	// Instantiate it
	$ModuleCall = new $classname($ImpExDisplay);

	// If its been FINISHED, then we are restarting it
	if ($ImpExSession->get_session_var($module) == 'FINISHED')
	{
		$ModuleCall->restarted();
	}

	// Sets the current to working
	$ModuleCall->using($ImpExSession);

	// Actually calls the init and does the work
	$ModuleCall->init($ImpExSession, $ImpExDisplay, $Db_target, $Db_source);
}


// #############################################################################
// Update & Display
// #############################################################################

$ImpEx->updateDisplay($ImpExSession, $ImpExDisplay);
echo $ImpExDisplay->display($ImpExSession);

if ($displayerrors)
{
	echo $ImpExSession->display_errors('all', $ImpExDisplay);
}

// #############################################################################
// Session End
// #############################################################################

$ImpEx->store_session($Db_target, $impexconfig['target']['tableprefix'], $ImpExSession);

echo "\n<!-- From {$system} to " . $ImpExSession->get_session_var('targetsystem') . "-->\n";
echo "\n<!-- PWD " . getcwd() . "-->\n";

echo $ImpExDisplay->page_footer();

?>