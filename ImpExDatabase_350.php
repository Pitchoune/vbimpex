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

if (!class_exists('ImpExDatabaseCore')) { die('Direct class access violation'); }

class ImpExDatabase extends ImpExDatabaseCore
{
	/**
	* Class version
	*
	* This will allow the checking for inter-operability of class version in different
	* versions of ImpEx
	*
	* @var	  string
	*/
	var $_version = '0.0.1';

	/**
	* Constructor
	*
	* Empty
	*/
	public function __constructor()
	{
	}

	/**
	* aaa
	*/
	public function import_vb3_user($Db_object, $databasetype, $tableprefix)
	{
		// TODO: Still need to check and see if all the current usersnames being imported are unique
		if (strtolower($this->get_value('mandatory', 'username')) == 'admin')
		{
			$this->set_value('mandatory', 'username', 'admin_old');
		}

		// Auto email associate
		if ($this->_auto_email_associate)
		{
			// Do a search for the email address to find the user to match this imported one to :
			$email_match = $Db_object->query_first("
				SELECT userid
				FROM " . $tableprefix . "user
				WHERE email = '". addslashes($this->get_value('mandatory', 'email')) . "'
			");

			if ($email_match)
			{
				if ($this->associate_user($Db_object, $databasetype, $tableprefix, $this->get_value('mandatory', 'importuserid'), $email_match["userid"]))
				{
					// We matched the email address and associated propperly
					$result['automerge'] = true;
					$result['userid'] = $email_match["userid"];
					return $result;
				}
				else
				{
					// Hmmm found the email but didn't associate !!
				}
			}
			else
			{
				// There is no email to match with, so return nothing and let the user import normally.
			}
		}

		// Auto userid associate
		if ($this->_auto_userid_associate)
		{
			// Do a search for the email address to find the user to match this imported one to :
			$userid_match = $Db_object->query_first("
				SELECT userid
				FROM " . $tableprefix . "userid
				WHERE userid = " . intval($this->get_value('mandatory', 'importuserid')) . "
			");

			if ($userid_match)
			{
				if($this->associate_user($Db_object, $databasetype, $tableprefix, $this->get_value('mandatory', 'importuserid'), $userid_match["userid"]))
				{
					// We matched the email address and associated propperly
					$result['automerge'] = true;
					$result['userid'] = $userid_match["userid"];

					return $result;
				}
				else
				{
					// Hmmm found the email but didn't associate !!
					return 0;
				}
			}
			else
			{
				// There is no email to match with, so return nothing and let the user import normally.
			}
		}

		// If there is a dupe username pre_pend "imported_"
		$double_name = $Db_object->query("
			SELECT username
			FROM " . $tableprefix . "user
			WHERE username = '" . addslashes($this->get_value('mandatory', 'username')) . "'
		");

		if ($Db_object->num_rows($double_name))
		{
			$this->set_value('mandatory', 'username', 'imported_' . $this->get_value('mandatory', 'username'));
		}

		$userdone = $Db_object->query("
			INSERT INTO " . $tableprefix . "user
				(username, email, usergroupid,
				importuserid, password, salt,
				passworddate, options, homepage,
				posts, joindate, icq,
				daysprune, aim, membergroupids,
				displaygroupid, styleid, parentemail,
				yahoo, showvbcode, usertitle,
				customtitle, lastvisit, lastactivity,
				lastpost, reputation, reputationlevelid,
				timezoneoffset, pmpopup, avatarid,
				avatarrevision, birthday, birthday_search, maxposts,
				startofweek, ipaddress, referrerid,
				languageid, msn, emailstamp,
				threadedmode, pmtotal, pmunread,
				autosubscribe, profilepicrevision)
			VALUES
				('" . addslashes($this->get_value('mandatory', 'username')) . "',
				'" . addslashes($this->get_value('mandatory', 'email')) . "',
				" . intval($this->get_value('mandatory', 'usergroupid')) . ",
				" . intval($this->get_value('mandatory', 'importuserid')) . "',
				'" . addslashes($this->get_value('nonmandatory', 'password')) . "',
				'" . addslashes($this->get_value('nonmandatory', 'salt')) . "',
				'" . addslashes($this->get_value('nonmandatory', 'passworddate')) . "',
				" . intval($this->get_value('nonmandatory', 'options')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'homepage')) . "',
				" . intval($this->get_value('nonmandatory', 'posts')) . ",
				" . intval($this->get_value('nonmandatory', 'joindate')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'icq')) . "',
				" . intval($this->get_value('nonmandatory', 'daysprune')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'aim')) . "',
				'" . addslashes($this->get_value('nonmandatory', 'membergroupids')) . "',
				" . intval($this->get_value('nonmandatory', 'displaygroupid')) . ",
				" . intval($this->get_value('nonmandatory', 'styleid')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'parentemail')) . "',
				'" . addslashes($this->get_value('nonmandatory', 'yahoo')) . "',
				" . intval($this->get_value('nonmandatory', 'showvbcode')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'usertitle')) . "',
				'" . addslashes($this->get_value('nonmandatory', 'customtitle')) . "',
				" . intval($this->get_value('nonmandatory', 'lastvisit')) . ",
				" . intval($this->get_value('nonmandatory', 'lastactivity')) . ",
				" . intval($this->get_value('nonmandatory', 'lastpost')) . ",
				" . intval($this->get_value('nonmandatory', 'reputation')) . ",
				" . intval($this->get_value('nonmandatory', 'reputationlevelid')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'timezoneoffset')) . "',
				" . intval($this->get_value('nonmandatory', 'pmpopup')) . ",
				" . intval($this->get_value('nonmandatory', 'avatarid')) . ",
				" . intval($this->get_value('nonmandatory', 'avatarrevision')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'birthday')) . "',
				'" . addslashes($this->get_value('nonmandatory', 'birthday_search')) . "',
				" . intval($this->get_value('nonmandatory', 'maxposts')) . ",
				" . intval($this->get_value('nonmandatory', 'startofweek')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'ipaddress')) . "',
				" . intval($this->get_value('nonmandatory', 'referrerid')) . ",
				" . intval($this->get_value('nonmandatory', 'languageid')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'msn')) . "',
				" . intval($this->get_value('nonmandatory', 'emailstamp')) . ",
				" . intval($this->get_value('nonmandatory', 'threadedmode')) . ",
				" . intval($this->get_value('nonmandatory', 'pmtotal')) . ",
				" . intval($this->get_value('nonmandatory', 'pmunread')) . ",
				" . intval($this->get_value('nonmandatory', 'autosubscribe')) . ",
				" . intval($this->get_value('nonmandatory', 'profilepicrevision')) . ")
		");

		$userid = $Db_object->insert_id();

		if ($userdone)
		{
			$exists = $Db_object->query_first("
				SELECT userid
				FROM " . $tableprefix . "usertextfield
				WHERE userid = " . intval($userid) . "
			");

			if (!$exists)
			{
				if (!$Db_object->query("
					INSERT INTO " . $tableprefix . "usertextfield
						(userid)
					VALUES
						(" . intval($userid) . ")
					"))
				{
					$this->_failedon = "usertextfield fill";
					return false;
				}

				if (!$Db_object->query("
					INSERT INTO " . $tableprefix . "userfield
						(userid)
					VALUES
						(" . intval($userid) . ")
				"))
				{
					$this->_failedon = "userfield fill";
					return false;
				}
			}

			if ($this->_has_default_values)
			{
				foreach ($this->get_default_values() AS $key => $value)
				{
					if ($key != 'signature')
					{
						if (!$this->import_user_field_value($Db_object, $databasetype, $tableprefix, $key, $value, $userid))
						{
							$this->_failedon = "import_user_field_value - $key - $value - $userid";
							return false;
						}
					}
				}
			}

			if (array_key_exists('signature',$this->_default_values))
			{
				if (!$Db_object->query("
					UPDATE " . $tableprefix . "usertextfield SET
						signature = '" . addslashes($this->_default_values['signature']) . "'
					WHERE userid = '" . intval($userid) . "'
				"))
				{
					$this->_failedon = "usertextfield SET signature";
					return false;
				}
			}
		}
		else
		{
			return false;
		}

		return $userid;
	}

	/**
	* aaa
	*/
	public function zuul($dana)
	{
		return "There are nospoon importers only zuul";
	}

	/**
	* aaa
	*/
	public function import_forum($Db_object, $databasetype, $tableprefix)
	{
		// Catch the legacy importers that haven't been updated
		if (!$this->get_value('mandatory', 'options'))
		{
			$this->set_value('mandatory', 'options', $this->_default_forum_permissions);
		}

		$result = $Db_object->query("
			INSERT INTO " . $tableprefix . "forum
				(styleid, title, options,
				displayorder, parentid, importforumid,
				importcategoryid, description, replycount,
				lastpost, lastposter, lastthread,
				lastthreadid, lasticonid, threadcount,
				daysprune, newpostemail, newthreademail,
				parentlist, password, link, childlist,
				title_clean, description_clean)
			VALUES
				(
				" . intval($this->get_value('mandatory', 'styleid')) . ",
				'" . addslashes($this->get_value('mandatory', 'title')) . "',
				" . intval($this->get_value('mandatory', 'options')) . ",
				" . intval($this->get_value('mandatory', 'displayorder')) . ",
				" . intval($this->get_value('mandatory', 'parentid')) . ",
				" . intval($this->get_value('mandatory', 'importforumid')) . ",
				" . intval($this->get_value('mandatory', 'importcategoryid')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'description')) . "',
				" . intval($this->get_value('nonmandatory', 'replycount')) . ",
				" . intval($this->get_value('nonmandatory', 'lastpost')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'lastposter')) . "',
				" . intval($this->get_value('nonmandatory', 'lastthread')) . ",
				" . intval($this->get_value('nonmandatory', 'lastthreadid')) . ",
				" . intval($this->get_value('nonmandatory', 'lasticonid')) . ",
				" . intval($this->get_value('nonmandatory', 'threadcount')) . ",
				" . intval($this->get_value('nonmandatory', 'daysprune')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'newpostemail')) . "',
				'" . addslashes($this->get_value('nonmandatory', 'newthreademail')) . "',
				'" . addslashes($this->get_value('nonmandatory', 'parentlist')) . "',
				'" . addslashes($this->get_value('nonmandatory', 'password')) . "',
				'" . addslashes($this->get_value('nonmandatory', 'link')) . "',
				'" . addslashes($this->get_value('nonmandatory', 'childlist')) . "',
				'" . addslashes($this->get_value('mandatory', 'title')) . "',
				'" . addslashes($this->get_value('nonmandatory', 'description')) . "'
			)
		");

		$forumid = $Db_object->insert_id($result);

		if ($result)
		{
			$Db_object->query("
				UPDATE " . $tableprefix . "forum SET
					parentlist='" . intval($forumid) . ",-1'
				WHERE forumid = " . intval($forumid) . "
			");

			if ($Db_object->affected_rows())
			{
				return $forumid;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}

	/**
	* aaa
	*/
	public function import_thread($Db_object, $databasetype, $tableprefix)
	{
		$Db_object->query("
			INSERT INTO " . $tableprefix . "thread
				(forumid, title, importforumid,
				importthreadid, firstpostid, lastpost,
				pollid, open, replycount,
				postusername, postuserid, lastposter,
				dateline, views, iconid,
				notes, visible, sticky,
				votenum, votetotal, attach, similar,
				hiddencount)
			VALUES
				(" . intval($this->get_value('mandatory', 'forumid')) . ",
				'" . addslashes($this->get_value('mandatory', 'title')) . "',
				" . intval($this->get_value('mandatory', 'importforumid')) . ",
				" . intval($this->get_value('mandatory', 'importthreadid')) . ",
				" . intval($this->get_value('nonmandatory', 'firstpostid')) . ",
				" . intval($this->get_value('nonmandatory', 'lastpost')) . ",
				" . intval($this->get_value('nonmandatory', 'pollid')) . ",
				" . intval($this->get_value('nonmandatory', 'open'))  . ",
				" . intval($this->get_value('nonmandatory', 'replycount')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'postusername')) . "',
				" . intval($this->get_value('nonmandatory', 'postuserid')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'lastposter')) . "',
				" . intval($this->get_value('nonmandatory', 'dateline')) . ",
				" . intval($this->get_value('nonmandatory', 'views')) . ",
				" . intval($this->get_value('nonmandatory', 'iconid')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'notes')) . "',
				" . intval($this->get_value('nonmandatory', 'visible')) . ",
				" . intval($this->get_value('nonmandatory', 'sticky')) . ",
				" . intval($this->get_value('nonmandatory', 'votenum')) . ",
				" . intval($this->get_value('nonmandatory', 'votetotal')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'attach')) . "',
				'" . addslashes($this->get_value('nonmandatory', 'similar')) . "',
				" . intval($this->get_value('nonmandatory', 'hiddencount')) . ")
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
	* Imports a usergroup
	*
	* @param	object	  databaseobject	The database that the function is going to interact with.
	* @param	string	  mixed			   The type of database 'mysql', 'postgresql', etc
	* @param	string	  mixed			   The prefix to the table name i.e. 'vb3_'
	*
	* @return	 insert_id
	*/
	public function import_user_group($Db_object, $databasetype, $tableprefix)
	{
		$Db_object->query("
			INSERT INTO " . $tableprefix . "usergroup
				(importusergroupid, title, description,
				usertitle, passwordexpires, passwordhistory,
				pmquota, pmsendmax,
				opentag, closetag, canoverride,
				ispublicgroup, forumpermissions, pmpermissions,
				calendarpermissions, wolpermissions, adminpermissions,
				genericpermissions, genericoptions, attachlimit,
				avatarmaxwidth, avatarmaxheight, avatarmaxsize,
				profilepicmaxwidth, profilepicmaxheight, profilepicmaxsize)
			VALUES
				(" . intval($this->get_value('mandatory', 'importusergroupid')) . ",
				'" . addslashes($this->get_value('nonmandatory','title')) . "',
				'" . addslashes($this->get_value('nonmandatory','description')) . "',
				'" . addslashes($this->get_value('nonmandatory','usertitle')) . "',
				" . intval($this->get_value('nonmandatory','passwordexpires')) . ",
				" . intval($this->get_value('nonmandatory','passwordhistory')) . ",
				" . intval($this->get_value('nonmandatory','pmquota')) . ",
				" . intval($this->get_value('nonmandatory','pmsendmax')) . ",
				'" . addslashes($this->get_value('nonmandatory','opentag')) . "',
				'" . addslashes($this->get_value('nonmandatory','closetag')) . "',
				" . intval($this->get_value('nonmandatory','canoverride')) . ",
				" . intval($this->get_value('nonmandatory','ispublicgroup')) . ",
				" . intval($this->get_value('nonmandatory','forumpermissions')) . ",
				" . intval($this->get_value('nonmandatory','pmpermissions')) . ",
				" . intval($this->get_value('nonmandatory','calendarpermissions')) . ",
				" . intval($this->get_value('nonmandatory','wolpermissions')) . ",
				" . intval($this->get_value('nonmandatory','adminpermissions')) . ",
				" . intval($this->get_value('nonmandatory','genericpermissions')) . ",
				" . intval($this->get_value('nonmandatory','genericoptions')) . ",
				" . intval($this->get_value('nonmandatory','attachlimit')) . ",
				" . intval($this->get_value('nonmandatory','avatarmaxwidth')) . ",
				" . intval($this->get_value('nonmandatory','avatarmaxheight')) . ",
				" . intval($this->get_value('nonmandatory','avatarmaxsize')) . ",
				" . intval($this->get_value('nonmandatory','profilepicmaxwidth')) . ",
				" . intval($this->get_value('nonmandatory','profilepicmaxheight')) . ",
				" . intval($this->get_value('nonmandatory','profilepicmaxsize')) . ")
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
	public function import_usergroup($Db_object, $databasetype, $tableprefix)
	{
		$Db_object->query("
			INSERT INTO " . $tableprefix . "usergroup
				(importusergroupid, title, description,
				usertitle, passwordexpires, passwordhistory,
				pmquota, pmsendmax,
				opentag, closetag, canoverride,
				ispublicgroup, forumpermissions, pmpermissions,
				calendarpermissions, wolpermissions, adminpermissions,
				genericpermissions, genericoptions, attachlimit,
				avatarmaxwidth, avatarmaxheight, avatarmaxsize,
				profilepicmaxwidth, profilepicmaxheight, profilepicmaxsize)
			VALUES
				(" . intval($this->get_value('mandatory', 'importusergroupid')) . ",
				'" . addslashes($this->get_value('nonmandatory','title')) . "',
				'" . addslashes($this->get_value('nonmandatory','description')) . "',
				'" . addslashes($this->get_value('nonmandatory','usertitle')) . "',
				" . intval($this->get_value('nonmandatory','passwordexpires')) . ",
				" . intval($this->get_value('nonmandatory','passwordhistory')) . ",
				" . intval($this->get_value('nonmandatory','pmquota')) . ",
				" . intval($this->get_value('nonmandatory','pmsendmax')) . ",
				'" . addslashes($this->get_value('nonmandatory','opentag')) . "',
				'" . addslashes($this->get_value('nonmandatory','closetag')) . "',
				" . intval($this->get_value('nonmandatory','canoverride')) . ",
				" . intval($this->get_value('nonmandatory','ispublicgroup')) . ",
				" . intval($this->get_value('nonmandatory','forumpermissions')) . ",
				" . intval($this->get_value('nonmandatory','pmpermissions')) . ",
				" . intval($this->get_value('nonmandatory','calendarpermissions')) . ",
				" . intval($this->get_value('nonmandatory','wolpermissions')) . ",
				" . intval($this->get_value('nonmandatory','adminpermissions')) . ",
				" . intval($this->get_value('nonmandatory','genericpermissions')) . ",
				" . intval($this->get_value('nonmandatory','genericoptions')) . ",
				" . intval($this->get_value('nonmandatory','attachlimit')) . ",
				" . intval($this->get_value('nonmandatory','avatarmaxwidth')) . ",
				" . intval($this->get_value('nonmandatory','avatarmaxheight')) . ",
				" . intval($this->get_value('nonmandatory','avatarmaxsize')) . ",
				" . intval($this->get_value('nonmandatory','profilepicmaxwidth')) . ",
				" . intval($this->get_value('nonmandatory','profilepicmaxheight')) . ",
				" . intval($this->get_value('nonmandatory','profilepicmaxsize')) . ")
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
}

?>