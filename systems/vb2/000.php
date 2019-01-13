<?php if (!defined('IDIR')) { die; }
/*======================================================================*\
|| ####################################################################
|| # vBulletin Impex
|| # ----------------------------------------------------------------
|| # All PHP code in this file is Copyright 2000-2014 vBulletin Solutions Inc.
|| # This code is made available under the Modified BSD License -- see license.txt
|| # http://www.vbulletin.com 
|| ####################################################################
\*======================================================================*/

/**
* vb2 API module
*
* @package			ImpEx.vb2
*/
class vb2_000 extends ImpExModule
{
	/**
	* Class version
	*
	* This is the version of the source system that is supported
	*
	* @var    string
	*/
	var $_version = '2.3.10';
	var $_tier = '3';

	/**
	* Module string
	*
	* @var    array
	*/
	var $_modulestring 	= 'vBulletin';
	var $_homepage 	= 'http://www.vbulletin.com';

	/**
	* Valid Database Tables
	*
	* @var    array
	*/
	var $_valid_tables = array (
		'access', 'adminlog', 'adminutil', 'announcement', 'attachment', 'avatar', 'bbcode',
		'calendar_events', 'custom_avatar', 'customavatar', 'forum', 'forumpermission', 'icon',
		'moderator', 'modlog', 'pmstats', 'poll', 'pollvote', 'post', 'privatemessage', 'profilefield',
		'regimage', 'replacement', 'replacementset', 'savethreads', 'search', 'searchindex', 'session',
		'setting', 'settinggroup', 'smilie', 'style', 'subscribeforum', 'subscribethread', 'template',
		'templateset', 'thread', 'threadrate', 'user', 'useractivation', 'userfield', 'usergroup',
		'usertitle', 'word'
	);

	public function __construct()
	{
	}

	/**
	* Returns the user_id => username array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	public function get_vb2_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
	{
		$return_array = array();

		$user_list = $Db_object->query("
			SELECT userid,username
			FROM user
			ORDER BY userid
				LIMIT " . $start . "," . $per_page . "
		");

		while ($user = $Db_object->fetch_array($user_list))
		{
			$return_array["$user[userid]"] = $user['username'];
		}
		return $return_array;
	}

	/**
	* Generic data return function
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	*
	* @return	array
	*/
	public function get_details($Db_object, $databasetype, $tableprefix, $displayobject, $start, $per_page, $type, $orderby = false)
	{
		$return_array = array();

		// Check that there isn't a empty value
		if (empty($per_page))
		{
			return $return_array;
		}

		$details_list = $Db_object->query("
			SELECT *
			FROM " . $type . "
			" . ($orderby ? "ORDER BY " . $orderby : "") . "
			" . ($per_page != -1 ? "LIMIT " . $start . "," . $per_page : "") . "
		");

		while ($detail = $Db_object->fetch_array($details_list))
		{
			if ($orderby)
			{
				$return_array["$detail[$orderby]"] = $detail;
			}
			else
			{
				$return_array[] = $detail;
			}
		}
		return $return_array;
	}

	public function update_vb2_imported_parent_forum_ids(&$Db_object, &$databasetype, &$tableprefix)
	{
		$forum_ids = $this->get_forum_ids($Db_object, $databasetype, $tableprefix);

		// parentid
		$parentid_list = $Db_object->query("
			SELECT forumid, parentid, parentlist
			FROM " . $tableprefix . "forum
			WHERE importforumid <> 0
		");

		while ($row = $Db_object->fetch_array($parentid_list))
		{
			if ($row['parentid'] == '-1')
			{
				$new_parent_id = '-1';
			}
			else
			{
				$new_parent_id = $forum_ids["$row[parentid]"];
			}

			$parent_list_old = $row['parentlist'];
			$parent_list_old = explode(',', $parent_list_old);

			unset($parent_list_new);

			foreach ($parent_list_old AS $value)
			{
				$parent_list_new[] = $forum_ids[$value];
			}

			$parent_list_new = implode(',', $parent_list_new);

			$parent_list_new .= ',-1';

			$Db_object->query("
				UPDATE " . $tableprefix . "forum SET
					parentid = " . intval($new_parent_id) . ",
					parentlist = '" . addslashes($parent_list_new) . "'
				WHERE forumid = " . intval($row['forumid']) . "
			");
		}

		return true;
	}

	public function update_poll_ids($Db_object, $databasetype, $tableprefix)
	{
		$result = $Db_object->query("
			SELECT pollid, threadid, importthreadid
			FROM " . $tableprefix . "thread
			WHERE open = 10
				AND pollid <> 0
				AND importthreadid <> 0
		");

		while ($thread = $Db_object->fetch_array($result))
		{
			$new_thread_id = $Db_object->query_first("
				SELECT threadid
				FROM " . $tableprefix . "thread
				WHERE importthreadid = " . $thread['pollid'] . "
			");

			if ($new_thread_id['threadid'])
			{
				// Got it
				$Db_object->query("
					UPDATE " . $tableprefix . "thread SET
						pollid = " . $new_thread_id['threadid'] . "
					WHERE threadid = " . $thread['threadid'] . "
				");
			}
			else
			{
				// Why does it miss some ????
			}
		}
	}

	public function get_vb2_attachment_post_id(&$Db_object, &$databasetype, &$tableprefix, &$attachment_id)
	{
		$details = $Db_object->query_first("
			SELECT postid
			FROM post
			WHERE attachmentid = " . $attachment_id . "
		");

		return $details[0];
	}
}

?>
