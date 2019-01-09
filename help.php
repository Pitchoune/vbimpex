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
// For auth
define('IDIR', (($getcwd = getcwd()) ? $getcwd : '.'));

if (function_exists('set_time_limit') AND get_cfg_var('safe_mode')==0)
{
	@set_time_limit(0);
}

ignore_user_abort(true);
error_reporting(E_ALL  & ~E_NOTICE);

if (!is_file(IDIR . '/ImpExConfig.php'))
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
// Auth
// #############################################################################
$auth_redirect = 'help.php';
require_once (IDIR . '/impex_auth.php');
require_once (IDIR . '/db_mysql.php');

require_once (IDIR . '/ImpExFunction.php');
require_once (IDIR . '/ImpExSession.php');
require_once (IDIR . '/ImpExController.php');
require_once (IDIR . '/ImpExDisplay.php');
require_once (IDIR . '/ImpExDisplayWrapper.php');

// If there is no global file in the same dir, then we aren't in vB
// we are standalone, else assume we are.

if (file_exists('../includes/config.php'))
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
		// Should neber be here.
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

// #############################################################################
// Database connect & session start
// #############################################################################
$ImpEx = new ImpExController();

$dbtype = strtolower($impexconfig['target']['databasetype']);

if ($dbtype == 'mysqli')
{
	$Db_target = new ImpEx_Database_Mysqli($ImpEx);
}
else
{
	$Db_target = new Impex_Database($ImpEx);
}

$Db_target->appname			= 'ImpEx Target';
$Db_target->appshortname	= 'ImpEx Target';

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

// #############################################################################
// Main page and dB connection
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

echo $ImpExDisplay->page_header() . '<h3>' . $ImpExDisplay->phrases['help_page'] . '</h3>';

if (is_file(IDIR . '/ImpExConfig.php'))
{
	require_once(IDIR . '/ImpExConfig.php');
	$impex_config = false;

	// They haven't set the target details
	if ($impexconfig['target']['password'] != 'password')
	{
		$using_local_config = '<p>' . $ImpExDisplay->phrases['using_impex_config'] . '</p>';

		$targetdatabasetype = $impexconfig['target']['databasetype'];
		$targetserver 		= $impexconfig['target']['server'];
		$targetuser 		= $impexconfig['target']['user'];
		$targetpassword 	= $impexconfig['target']['password'];
		$targetdatabase 	= $impexconfig['target']['database'];
		$targettableprefix 	= $impexconfig['target']['tableprefix'];
		$targettablecharset	= $impexconfig['target']['charset'];
		$impex_config = true;
	}
	else if (file_exists('./includes/config.php') AND !$impex_config)
	{
		require_once('./includes/config.php');

		$using_local_config = '<p>' . $ImpExDisplay->phrases['using_local_config'] . '</p>';

		$targetdatabasetype = $config['Database']['dbtype'] ? $config['Database']['dbtype'] : 'mysql';
		$targetserver 		= $config['MasterServer']['servername'];
		$targetuser 		= $config['MasterServer']['username'];
		$targetpassword 	= $config['MasterServer']['password'];
		$targetdatabase 	= $config['Database']['dbname'];
		$targettableprefix 	= $config['Database']['tableprefix'];
		$targettablecharset	= $config['Mysqli']['charset'];
	}
	else
	{
		echo $ImpExDisplay->table_header();
		echo $ImpExDisplay->make_table_header($ImpExDisplay->phrases['help_error'] );
		echo $ImpExDisplay->make_description($ImpExDisplay->phrases['cant_read_config']);
		echo $ImpExDisplay->table_footer();
		echo $ImpExDisplay->page_footer();
	}
}
else
{
	// No config
	echo $ImpExDisplay->table_header();
	echo $ImpExDisplay->make_table_header($ImpExDisplay->phrases['help_error'] );
	echo $ImpExDisplay->make_description($ImpExDisplay->phrases['cant_find_config']);
	echo $ImpExDisplay->table_footer();
	echo $ImpExDisplay->page_footer();
}

$forumfields = array (
	'moderator'				=>  'importmoderatorid',
	'usergroup'				=>  'importusergroupid',
	'ranks'					=>  'importrankid',
	'poll'					=>  'importpollid',
	'forum'					=>  'importforumid',
	'forum'					=>  'importcategoryid',
	'user'					=>  'importuserid',
	'style'					=>  'importstyleid',
	'thread'				=>  'importthreadid',
	'post'					=>  'importthreadid',
	'thread'				=>  'importforumid',
	'smilie'				=>  'importsmilieid',
	'pmtext'				=>  'importpmid',
	'avatar'				=>  'importavatarid',
	'customavatar'			=>  'importcustomavatarid',
	'customprofilepic'		=>  'importcustomprofilepicid',
	'post'					=>  'importpostid',
	'attachment'			=>  'importattachmentid',
	'filedata'				=>  'importfiledataid',
);

$blogfields = array(
	'blog'					=>  'importblogid',
	'blog_category'			=>  'importblogcategoryid',
	'blog_categoryuser'		=>  'importblogcategoryid',
	'blog_moderator'		=>  'importblogmoderatorid',
	'blog_custom_block'		=>  'importcustomblockid',
	'blog_groupmembership'	=>	'importbloggroupmembershipid',
	'blog_rate'				=>  'importblograteid',
	'blog_subscribeentry'	=>  'importblogsubscribeentryid',
	'blog_subscribeuser'	=>  'importblogsubscribeuserid',
	'blog_text'				=>  'importblogtextid',
	'blog_trackback'		=>  'importblogtrackbackid',
	'blog_user'				=>  'importbloguserid',
);

$cmsfields = array(
	'cms_article'			=> 'importcmscontentid',
	'cms_category'			=> 'importcmscategoryid',
	'cms_grid'				=> 'importcmsgridid',
	'cms_layout'			=> 'importcmslayoutid',
	'cms_layoutwidget'		=> 'importid',
	'cms_widget'			=> 'importcmswidgetid',
	'cms_widgetconfig'		=> 'importid',
	'cms_navigation'		=> 'importid',
	'cms_node'				=> 'importcmsnodeid',
	'cms_nodecategory'		=> 'importid',
	'cms_nodeconfig'		=> 'importid',
	'cms_nodeinfo'			=> 'importid',
	'cms_rate'				=> 'importcmsrateid',
	'cms_sectionorder'		=> 'importid',
);

switch ($_GET['type'])
{
	case 'cms':
		$target = $cmsfields;
		break;
	case 'blog':
		$target = $blogfields;
		break;
	default:
		$target = $forumfields;
}

if (empty($_GET['action']))
{

	echo $ImpExDisplay->phrases['action_1'];

	if ($using_local_config)
	{
		echo '<p>' . $ImpExDisplay->phrases['using_local_config'] . '</p>';
	}

	echo $ImpExDisplay->phrases['action_2'];
	echo $ImpExDisplay->phrases['action_3'];


	echo $ImpExDisplay->phrases['delete_session_and_data'];
	echo $ImpExDisplay->phrases['action_4'];
	echo $ImpExDisplay->phrases['action_7'];
	echo $ImpExDisplay->phrases['action_9'];

	echo $ImpExDisplay->phrases['remove_importids'];
	echo $ImpExDisplay->phrases['action_5'];
	echo $ImpExDisplay->phrases['action_8'];
	echo $ImpExDisplay->phrases['action_10'];

	echo $ImpExDisplay->phrases['action_6'];
}

if ($_GET['action'] == 'delsess')
{
	echo $ImpExDisplay->phrases['dell_session_1'];
	echo $ImpExDisplay->phrases['dell_session_2'];
	echo $ImpExDisplay->phrases['dell_session_3'];
	echo $ImpExDisplay->phrases['dell_session_4'];

	$Db_target->query("
		DELETE FROM " . $targettableprefix . "datastore
		WHERE title='ImpExSession'
	");

	echo $ImpExDisplay->phrases['dell_session_5'];
	echo $ImpExDisplay->phrases['dell_session_6'];
}

if ($_GET['action'] == 'delall')
{
	echo $ImpExDisplay->phrases['deleting_session'];

	$Db_target->query("
		DELETE FROM " . $targettableprefix . "datastore
		WHERE title = 'ImpExSession'
	");

	echo $ImpExDisplay->phrases['session_deleted'];

	foreach ($target AS $tablename => $colname)
	{
		$Db_target->reporterror = 0;

		$is_it_there = $Db_target->query_first("
			DESCRIBE " . $targettableprefix . "" . $tablename . " " . $colname . "
		");

		$Db_target->reporterror = 1;

		if ($is_it_there)
		{
			echo $ImpExDisplay->phrases['deleting_from'] . " " . $targettableprefix . "" . $tablename . " ....";
			flush();

			$Db_target->query("
				DELETE FROM " . $targettableprefix . "" . $tablename . "
				WHERE " . $colname . " <> 0
			");

			echo "...<b>" . $ImpExDisplay->phrases['completed'] . "</b></p>";
			flush();
		}

	}

	echo $ImpExDisplay->phrases['click_to_return'];
}

if ($_GET['action'] == 'delids')
{
	echo $ImpExDisplay->phrases['deleting_session'];

	$Db_target->query("
		DELETE FROM " . $targettableprefix . "datastore
		WHERE title = 'ImpExSession'
	");

	echo $ImpExDisplay->phrases['session_deleted'];

	foreach ($target AS $tablename => $colname)
	{
		$Db_target->reporterror = false;

		$is_it_there = $Db_target->query_first("
			DESCRIBE " . $targettableprefix . $tablename . " " . $colname . "
		");

		$Db_target->reporterror = true;

		if ($is_it_there)
		{
			echo "<p>" . $ImpExDisplay->phrases['del_ids_1'] . " " . $colname . " " . $ImpExDisplay->phrases['del_ids_2'] . " " . $tablename . " " . $ImpExDisplay->phrases['del_ids_3'] . "";
			flush();

			$Db_target->query("
				UPDATE " . $targettableprefix . $tablename . " SET
					" . $colname . "= 0
				WHERE " . $colname . " <> 0
			");

			echo "...<b>" . $ImpExDisplay->phrases['completed'] . "</b></p>";
			flush();
		}
	}

	echo $ImpExDisplay->phrases['click_to_return'];
}

if ($_GET['action'] == 'deldupe')
{
	echo $ImpExDisplay->phrases['deleting_duplicates'];

	// Users
	$dupe_users = $Db_target->query("
		SELECT MAX(userid) AS userid,
			COUNT(*) AS count
		FROM " . $targettableprefix . "user
		WHERE importuserid > 0
		GROUP BY importuserid
	");

	while ($user = $Db_target->fetch_array($dupe_users))
	{
		$user_to_delete[] = $user['userid'];
	}

	if ($user_to_delete)
	{
		$users_found = count($user_to_delete);
	}
	else
	{
		$users_found = 0;
	}

	if ($users_found)
	{
		$Db_target->query("
			DELETE FROM " . $targettableprefix . "user
			WHERE userid IN(" . implode(',', $user_to_delete) . ")
		");
	}

	// Forums
	$dupe_forums = $Db_target->query("
		SELECT MAX(forumid) AS forumid,
			COUNT(*) AS count
		FROM " . $targettableprefix . "forum
		WHERE importforumid > 0
		GROUP BY importforumid
	");

	while ($forum = $Db_target->fetch_array($dupe_forums))
	{
		$forum_to_delete[] = $forum['forumid'];
	}

	if ($forum_to_delete)
	{
		$forums_found = count($forum_to_delete);
	}
	else
	{
		$forums_found = 0;
	}

	if ($forums_found)
	{
		$Db_target->query("
			DELETE FROM " . $targettableprefix . "forum
			WHERE forumid IN(" . implode(',', $forum_to_delete) . ")
		");
	}

	// Threads
	$dupe_threads = $Db_target->query("
		SELECT MAX(threadid) AS threadid,
			COUNT(*) AS count
		FROM " . $targettableprefix . "thread
		WHERE importthreadid > 0
		GROUP BY importthreadid
	");

	while ($thread = $Db_target->fetch_array($dupe_threads))
	{
		$thread_to_delete[] = $thread['threadid'];
	}

	if ($thread_to_delete)
	{
		$threads_found = count($thread_to_delete);
	}
	else
	{
		$threads_found = 0;
	}

	if ($threads_found)
	{
		$Db_target->query("
			DELETE FROM " . $targettableprefix . "thread
			WHERE threadid IN(" . implode(',', $thread_to_delete) . ")
		");
	}

	// Posts
	$dupe_posts = $Db_target->query("
		SELECT MAX(postid) AS postid,
			COUNT(*) AS count
		FROM " . $targettableprefix . "post
		WHERE importpostid > 0
		GROUP BY importpostid
	");

	while ($post = $Db_target->fetch_array($dupe_posts))
	{
		$post_to_delete[] = $post['postid'];
	}

	if ($post_to_delete)
	{
		$posts_found = count($post_to_delete);
	}
	else
	{
		$posts_found = 0;
	}

	if ($posts_found)
	{
		$Db_target->query("
			DELETE FROM " . $targettableprefix . "post
			WHERE postid IN(" . implode(',', $post_to_delete) . ")
		");
	}

	echo '<br />' . $ImpExDisplay->phrases['users'] . ' :: ' . $users_found . '';
	echo '<br />' . $ImpExDisplay->phrases['forums'] . ' :: ' . $forums_found . '';
	echo '<br />' . $ImpExDisplay->phrases['threads'] . ' :: ' . $threads_found . '';
	echo '<br />' . $ImpExDisplay->phrases['posts'] . ' :: ' . $posts_found . '';

	echo '<br /><br />...<b>' . $ImpExDisplay->phrases['completed'] . '</b></p>';
	echo $ImpExDisplay->phrases['click_to_return'];
}

echo $ImpExDisplay->page_footer();

?>