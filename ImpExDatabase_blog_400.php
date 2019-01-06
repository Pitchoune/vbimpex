<?php
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
* The database proxy object.
*
* This handles interaction with the different types of database.
*
* @package		ImpEx
*/

if (!class_exists('ImpExFunction')) { die('Direct class access violation'); }

require_once (IDIR . '/ImpExDatabase_blog.php');

class ImpExDatabase extends ImpExDatabaseBlog
{
	/**
	* Class version
	*
	* This will allow the checking for inter-operability of class version in different
	* versions of ImpEx
	*
	* @var	  string
	*/

	var $_import_blog_ids = array(
		array('blog'				=>	'importblogid'),
		array('blog_category'		=>	'importblogcategoryid'),
		array('blog_categoryuser'	=>	'importblogcategoryid'),
		array('blog_moderator'		=>	'importblogmoderatorid'),
		array('blog_custom_block'	=>	'importcustomblockid'),
		array('blog_groupmembership'=>	'importbloggroupmembershipid'),
		array('blog_rate'			=>	'importblograteid'),
		array('blog_subscribeentry' =>	'importblogsubscribeentryid'),
		array('blog_subscribeuser'	=>	'importblogsubscribeuserid'),
		array('blog_text'			=>	'importblogtextid'),
		array('blog_trackback'		=>	'importblogtrackbackid'),
		array('blog_user'			=>	'importbloguserid'),
		array('usergroup'			=>	'importusergroupid'),
		array('user'				=>	'importuserid'),
		array('usernote'			=>	'importusernoteid'),
		array('customavatar'		=>	'importcustomavatarid'),
		array('customprofilepic'	=>	'importcustomprofilepicid'),
	);

	/**
	* Imports the current objects values as a blog_user and returns the insert_id
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed		The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed		The prefix to the table name i.e. 'vb3_'
	*
	* @return	int insert_id
	*/
	public function import_blog_user(&$Db_object, &$databasetype, &$tableprefix)
	{
		// Check the dupe
		if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['pmtext'] === false))
		{
			$there = $Db_object->query_first("
				SELECT bloguserid
				FROM " . $tableprefix . "blog_user
				WHERE importbloguserid=" . intval(trim($this->get_value('mandatory', 'importbloguserid'))) . "
			");

			if (is_numeric($there[0]))
			{
				return false;
			}
		}

		if (!intval($this->get_value('mandatory', 'bloguserid')))
		{
			return false;
		}

		$Db_object->query("
			REPLACE INTO " . $tableprefix . "blog_user
				(bloguserid, title, description, allowsmilie, options,
				viewoption, comments, lastblog, lastblogid,
				lastblogtitle, lastcomment, lastcommenter, lastblogtextid,
				entries, deleted, moderation, draft, pending, ratingnum,
				ratingtotal, rating, subscribeown, subscribeothers,
				uncatentries, options_member, options_guest, options_buddy,
				options_ignore, isblogmoderator, comments_moderation,
				comments_deleted, categorycache, tagcloud, sidebar,
				custompages, customblocks, memberids, memberblogids, importbloguserid)
			VALUES
				(" . intval($this->get_value('mandatory', 'bloguserid')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'title')) . "',
				'" . addslashes($this->get_value('nonmandatory', 'description')) . "',
				" . intval($this->get_value('nonmandatory', 'allowsmilie')) . ",
				" . intval($this->get_value('nonmandatory', 'options')) . ",
				'" . $this->enum_check($this->get_value('mandatory', 'viewoption'), array('all','only','except'), 'all') . "',
				" . intval($this->get_value('nonmandatory', 'comments')) . ",
				" . intval($this->get_value('nonmandatory', 'lastblog')) . ",
				" . intval($this->get_value('nonmandatory', 'lastblogid')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'lastblogtitle')) . "',
				" . intval($this->get_value('nonmandatory', 'lastcomment')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'lastcommenter')) . "',
				" . intval($this->get_value('nonmandatory', 'lastblogtextid')) . ",
				" . intval($this->get_value('nonmandatory', 'entries')) . ",
				" . intval($this->get_value('nonmandatory', 'deleted')) . ",
				" . intval($this->get_value('nonmandatory', 'moderation')) . ",
				" . intval($this->get_value('nonmandatory', 'draft')) . ",
				" . intval($this->get_value('nonmandatory', 'pending')) . ",
				" . intval($this->get_value('nonmandatory', 'ratingnum')) . ",
				" . intval($this->get_value('nonmandatory', 'ratingtotal')) . ",
				'" . floatval($this->get_value('nonmandatory', 'rating')) . "',
				'" . $this->enum_check($this->get_value('mandatory', 'subscribeown'), array('none','usercp','email'), 'none') . "',
				'" . $this->enum_check($this->get_value('mandatory', 'subscribeothers'), array('none','usercp','email'), 'none') . "',
				" . intval($this->get_value('nonmandatory', 'uncatentries')) . ",
				" . intval($this->get_value('nonmandatory', 'options_member')) . ",
				" . intval($this->get_value('nonmandatory', 'options_guest')) . ",
				" . intval($this->get_value('nonmandatory', 'options_buddy')) . ",
				" . intval($this->get_value('nonmandatory', 'options_ignore')) . ",
				" . intval($this->get_value('nonmandatory', 'isblogmoderator')) . ",
				" . intval($this->get_value('nonmandatory', 'comments_moderation')) . ",
				" . intval($this->get_value('nonmandatory', 'comments_deleted')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'categorycache')) . "',
				'" . addslashes($this->get_value('nonmandatory', 'tagcloud')) . "',
				'" . addslashes($this->get_value('nonmandatory', 'sidebar')) . "',
				'" . addslashes($this->get_value('nonmandatory', 'custompages')) . "',
				" . intval($this->get_value('nonmandatory', 'customblocks')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'memberids')) . "',
				'" . addslashes($this->get_value('nonmandatory', 'memberblogids')) . "',
				" . intval($this->get_value('mandatory', 'importbloguserid')) . ")
		");

		if ($Db_object->affected_rows())
		{
			return true; // There is no auto_inc so no return
		}
		else
		{
			return false;
		}
	}

	/**
	* Imports the current objects values as a blog_attachment and returns the insert_id
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed		The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed		The prefix to the table name i.e. 'vb3_'
	*
	* @return	int insert_id
	*/
	public function import_blog_attachment(&$Db_object, &$databasetype, &$tableprefix)
	{
		return $this->import_vb4_attachment($Db_object, $databasetype, $tableprefix, true, 'blog');
	}

	/**
	* Imports the current objects values as a blog_text and returns the insert_id
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed		The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed		The prefix to the table name i.e. 'vb3_'
	*
	* @return	int insert_id
	*/
	public function import_blog_text(&$Db_object, &$databasetype, &$tableprefix)
	{
		// Check the dupe
		if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['pmtext'] === false))
		{
			$there = $Db_object->query_first("
				SELECT blogtextid
				FROM " . $tableprefix . "blog_text
				WHERE importblogtextid = " . intval(trim($this->get_value('mandatory', 'importblogtextid'))) . "
			");

			if (is_numeric($there[0]))
			{
				return false;
			}
		}

		$Db_object->query("
			INSERT INTO {$tableprefix}blog_text
				(blogid, userid, dateline, pagetext, title, state, allowsmilie, username, ipaddress, reportthreadid, bloguserid, importblogtextid, htmlstate)
			VALUES
				(" . intval($this->get_value('mandatory', 'blogid')) . ",
				" . intval($this->get_value('mandatory', 'userid')) . ",
				" . intval($this->get_value('mandatory', 'dateline')) . ",
				'" . addslashes($this->get_value('mandatory', 'pagetext')) . "',
				'" . addslashes($this->get_value('nonmandatory', 'title')) . "',
				'" . $this->enum_check($this->get_value('nonmandatory', 'state'), array('moderation','visible','deleted'), 'visible') . "',
				" . intval($this->get_value('nonmandatory', 'allowsmilie')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'username')) . "',
				" . intval(sprintf('%u', ip2long($this->get_value('nonmandatory', 'ipaddress')))) . ",
				" . intval($this->get_value('nonmandatory', 'reportthreadid')) . ",
				" . intval($this->get_value('mandatory', 'bloguserid')) . ",
				" . intval($this->get_value('mandatory', 'importblogtextid')) . ",
				'" . $this->enum_check($this->get_value('nonmandatory', 'htmlstate'), array('off','on','on_nl2br'), 'on_nl2br') . "')
		");

		if ($Db_object->affected_rows())
		{
			return $Db_object->insert_id();
		}
		else
		{
			return false;
		}
	}

	/**
	* Imports the current objects values as a blog and returns the insert_id
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed		The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed		The prefix to the table name i.e. 'vb3_'
	*
	* @return	int insert_id
	*/
	public function import_blog(&$Db_object, &$databasetype, &$tableprefix)
	{
		// Check the dupe
		if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['pmtext'] === false))
		{
			$there = $Db_object->query_first("
				SELECT blogid
				FROM " . $tableprefix . "blog
				WHERE importblogid = " . intval(trim($this->get_value('mandatory', 'importblogid'))) . "
			");

			if (is_numeric($there[0]))
			{
				return false;
			}
		}

		$Db_object->query("
			INSERT INTO {$tableprefix}blog
				(firstblogtextid, userid, dateline, comments_visible,
				comments_moderation, comments_deleted, attach, state,
				views, username, title, trackback_visible,
				trackback_moderation, options, lastcomment, lastblogtextid,
				lastcommenter, ratingnum, ratingtotal, rating,
				pending, categories, taglist, postedby_userid, postedby_username, importblogid)
			VALUES
				(" . intval($this->get_value('mandatory', 'firstblogtextid')) . ",
				" . intval($this->get_value('mandatory', 'userid')) . ",
				" . intval($this->get_value('mandatory', 'dateline')) . ",
				" . intval($this->get_value('nonmandatory', 'comments_visible')) . ",
				" . intval($this->get_value('nonmandatory', 'comments_moderation')) . ",
				" . intval($this->get_value('nonmandatory', 'comments_deleted')) . ",
				" . intval($this->get_value('nonmandatory', 'attach')) . ",
				'" . $this->enum_check($this->get_value('nonmandatory', 'state'), array('moderation','draft','visible','deleted'), 'visible') . "',
				" . intval($this->get_value('nonmandatory', 'views')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'username')) . "',
				'" . addslashes($this->get_value('mandatory', 'title')) . "',
				" . intval($this->get_value('nonmandatory', 'trackback_visible')) . ",
				" . intval($this->get_value('nonmandatory', 'trackback_moderation')) . ",
				" . intval($this->get_value('mandatory', 'options')) . ",
				" . intval($this->get_value('nonmandatory', 'lastcomment')) . ",
				" . intval($this->get_value('nonmandatory', 'lastblogtextid')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'lastcommenter')) . "',
				" . intval($this->get_value('nonmandatory', 'ratingnum')) . ",
				" . intval($this->get_value('nonmandatory', 'ratingtotal')) . ",
				'" . floatval($this->get_value('nonmandatory', 'rating')) . "',
				" . intval($this->get_value('nonmandatory', 'pending')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'categories')) . "',
				'" . addslashes($this->get_value('nonmandatory', 'taglist')) . "',
				" . intval($this->get_value('mandatory', 'postedby_userid')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'postedby_username')) . "',
				" . intval($this->get_value('mandatory', 'importblogid')) . ")
		");

		if ($Db_object->affected_rows())
		{
			$insert_id = $Db_object->insert_id();

			$Db_object->query("
				UPDATE " . $tableprefix . "blog_text SET
					blogid = " . $insert_id . "
				WHERE blogtextid = " . intval($this->get_value('mandatory', 'firstblogtextid')) . "
			");

			return $insert_id;
		}
		else
		{
			return false;
		}
	}

	/**
	* Imports the current objects values as a blog_custom_block and returns the insert_id
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	int insert_id
	*/
	public function import_blog_custom_block(&$Db_object, &$databasetype, &$tableprefix)
	{
		// Check the dupe
		if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['pmtext'] === false))
		{
			$there = $Db_object->query_first("
				SELECT customblockid
				FROM " . $tableprefix . "blog_custom_block
				WHERE importcustomblockid = " . intval(trim($this->get_value('mandatory', 'importcustomblockid'))) . "
			");

			if (is_numeric($there[0]))
			{
				return false;
			}
		}

		$Db_object->query("
			INSERT INTO {$tableprefix}blog_custom_block
				(userid, title, pagetext, dateline, allowsmilie, type, location, displayorder, importcustomblockid)
			VALUES
				(" . intval($this->get_value('mandatory', 'userid')) . ",
				'" . addslashes($this->get_value('mandatory', 'title')) . "',
				'" . addslashes($this->get_value('nonmandatory', 'pagetext')) . "',
				" . intval($this->get_value('nonmandatory', 'dateline')) . ",
				" . intval($this->get_value('nonmandatory', 'allowsmilie')) . ",
				'" . $this->enum_check($this->get_value('nonmandatory', 'type'), array('block', 'page'), 'block') . "',
				'" . $this->enum_check($this->get_value('nonmandatory', 'location'), array('none', 'side', 'top'), 'none') . "',
				" . intval($this->get_value('mandatory', 'displayorder')) . ",
				" . intval($this->get_value('mandatory', 'importcustomblockid')) . ")
		");

		if ($Db_object->affected_rows())
		{
			return $Db_object->insert_id();
		}
		else
		{
			return false;
		}
	}

	/**
	* aaa
	*/
	public function clear_imported_blog_custom_blocks(&$Db_object, &$databasetype, &$tableprefix)
	{
		$blogs = $Db_object->query("
			DELETE FROM " . $tableprefix . "blog_custom_block
			WHERE importcustomblockid <> 0
		");

		$Db_object->query("
			ALTER TABLE " . $tableprefix . "blog_custom_block AUTO_INCREMENT = 0
		");

		$Db_object->query("
			ALTER TABLE " . $tableprefix . "blog_custom_block auto_increment = 0
		");

		return true;
	}

	public function clear_imported_blog_group_memberships(&$Db_object, &$databasetype, &$tableprefix)
	{
		$Db_object->query("
			DELETE FROM " . $tableprefix . "blog_groupmembership
			WHERE importbloggroupmembershipid <> 0
		");

		return true;
	}

	/**
	* Imports the current objects values as a blog_groupmembership
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed		The type of database 'mysql', 'postgresql', etc
	* @param	string	mixed		The prefix to the table name i.e. 'vb3_'
	*
	* @return	int insert_id
	*/

	public function import_blog_group_membership(&$Db_object, &$databasetype, &$tableprefix)
	{
		// Check the dupe
		if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['pmtext'] === false))
		{

		}

		$Db_object->query("
			REPLACE INTO {$tableprefix}blog_groupmembership
				(bloguserid, userid, permissions, state, dateline)
			VALUES
				(" . intval($this->get_value('mandatory', 'bloguserid')) . ",
				" . intval($this->get_value('mandatory', 'userid')) . ",
				" . intval($this->get_value('nonmandatory', 'permissions')) . ",
				'" . $this->enum_check($this->get_value('nonmandatory', 'state'), array('active', 'pending', 'ignored'), 'pending') . "',
				" . intval($this->get_value('nonmandatory', 'dateline')) . ")
		");

		if ($Db_object->affected_rows())
		{
			return true;
		}
		else
		{
			return false;
		}
	}
}

?>