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
* @package 		ImpEx
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
	* @var    string
	*/
	var $_version = '0.0.1';

	/**
	* Constructor
	*
	* Empty
	*
	*/
	public function __constructor()
	{
	}

	/**
	* aaa
	*/
	public function import_attachment($Db_object, $databasetype, $tableprefix, $import_post_id = true)
	{
		if ($import_post_id)
		{
			if ($this->get_value('nonmandatory', 'postid'))
			{
				// Get the real post id
				$post_id = $Db_object->query_first("
					SELECT postid, userid
					FROM " . $tableprefix . "post
					WHERE
					importpostid = " . intval($this->get_value('nonmandatory', 'postid')) . "
				");

				if (empty($post_id['postid']))
				{
					// Its not there to be attached through.
					return false;
				}
			}
			else
			{
				// No post id !!!
				return false;
			}
		}
		else
		{
			$post_id = $Db_object->query_first("
				SELECT userid, postid
				FROM " . $tableprefix . "post
				WHERE postid = " . intval($this->get_value('nonmandatory', 'postid')) . "
			");
		}

		// Update the post attach
		$Db_object->query("
			UPDATE " . $tableprefix . "post SET
				attach = attach + 1
			WHERE postid = " . intval($post_id['postid']) . "
		");

		// Ok, so now where is it going ......
		$attachpath =  $this->get_options_setting($Db_object, $databasetype, $tableprefix, 'attachpath');
		$attachfile = $this->get_options_setting($Db_object, $databasetype, $tableprefix, 'attachfile');

		$Db_object->query("
			INSERT INTO " . $tableprefix . "attachment
				(importattachmentid, filename, filedata, dateline, visible, counter, filesize, postid, filehash, userid)
			VALUES
				(" . intval($this->get_value('mandatory', 'importattachmentid')) . ",
				'" . addslashes($this->get_value('mandatory', 'filename')) . "',
				'',
				" . intval($this->get_value('nonmandatory', 'dateline'))  . ",
				" . intval($this->get_value('nonmandatory', 'visible'))  . ",
				" . intval($this->get_value('nonmandatory', 'counter'))  . ",
				'',
				" . intval($post_id['postid']) . ",
				'" . addslashes($this->get_value('nonmandatory', 'filehash')) . "',
				" . intval($post_id['userid']) . ")
		");

		$attachment_id = $Db_object->insert_id();

		switch (intval($attachfile))
		{
			case '0':	// Straight into the dB
			{
				$Db_object->query("
					UPDATE " . $tableprefix . "attachment SET
						filedata = '" . addslashes($this->get_value('mandatory', 'filedata')) . "',
						filesize = " . intval($this->get_value('nonmandatory', 'filesize'))  . "
					WHERE attachmentid = " . intval($attachment_id) . "
				");

				return $attachment_id;
			}

			case '1':	// file system OLD naming schema
			{
				$full_path = $this->fetch_attachment_path($post_id['userid'], $attachpath, false, $attachment_id);

				if($this->vbmkdir(substr($full_path, 0, strrpos($full_path, '/'))))
				{
					if ($fp = fopen($full_path, 'wb'))
					{
						fwrite($fp, $this->get_value('mandatory', 'filedata'));
						fclose($fp);
						$filesize = filesize($full_path);

						if($filesize)
						{
							$Db_object->query("
								UPDATE " . $tableprefix . "attachment
								SET
								filesize = " . intval($this->get_value('nonmandatory', 'filesize'))  . "
								WHERE attachmentid = " . intval($attachment_id) . "
							");

							return $attachment_id;
						}
					}
				}
				return false;
			}

			case '2':	// file system NEW naming schema
			{
				$full_path = $this->fetch_attachment_path($post_id['userid'], $attachpath, true, $attachment_id);

				if($this->vbmkdir(substr($full_path, 0, strrpos($full_path, '/'))))
				{
					if ($fp = fopen($full_path, 'wb'))
					{
						fwrite($fp, $this->get_value('mandatory', 'filedata'));
						fclose($fp);
						$filesize = filesize($full_path);

						if($filesize)
						{
							$Db_object->query("
								UPDATE " . $tableprefix . "attachment SET
									filesize = " . intval($this->get_value('nonmandatory', 'filesize'))  . "
								WHERE attachmentid = ". intval($attachment_id) . "
							");

							return $attachment_id;
						}
					}
				}
				return false;
			}

			default:
			{
				// Shouldn't ever get here
				return false;
			}
		}
	}

	/**
	* Imports the current object as a vB3 avatar
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	public function import_vb3_avatar($Db_object, $databasetype, $tableprefix)
	{
		$Db_object->query("
			INSERT INTO " . $tableprefix . "avatar
				(importavatarid, title, minimumposts, avatarpath, imagecategoryid, displayorder)
			VALUES
				(" . intval($this->get_value('mandatory', 'importavatarid')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'title')) . "',
				" . intval($this->get_value('nonmandatory', 'minimumposts')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'avatarpath')) . "',
				" . intval($this->get_value('nonmandatory', 'imagecategoryid')) . ",
				" . intval($this->get_value('nonmandatory', 'displayorder')) . ")
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
	* Imports the current object as a vB3 avatar
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	public function import_vb3_customavatar($Db_object, $databasetype, $tableprefix)
	{
		if ($Db_object->query("
			REPLACE INTO " . $tableprefix . "customavatar
				(importcustomavatarid, userid, avatardata, dateline, filename, visible, filesize)
			VALUES
				(" . intval($this->get_value('mandatory', 'importcustomavatarid')) . ",
				" . intval($this->get_value('nonmandatory', 'userid')) . ",
				" . intval($this->get_value('nonmandatory', 'avatardata')) . ",
				" . intval($this->get_value('nonmandatory', 'dateline')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'filename')) . "',
				" . intval($this->get_value('nonmandatory', 'visible')) . ",
				" . intval($this->get_value('nonmandatory', 'filesize')) . ")
		"))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	* Imports the an array as a ban list in various formats $key => $value, $int => $data
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	public function import_ban_list($Db_object, $databasetype, $tableprefix, $list, $type)
	{
		if (empty($list))
		{
			return true;
		}

		$internal_list = '';

		switch ($type)
		{
			case 'emaillist':
			{
				foreach ($list AS $key => $data)
				{
					$internal_list .= $data . " ";
				}

				$Db_object->query("UPDATE " . $tableprefix . "settings SET value=CONCAT(value,' " . $list . "') WHERE varname='banemail'");

				$Db_object->query("UPDATE " . $tableprefix . "datastore SET data = CONCAT(data, '$internal_list') WHERE title = 'banemail'");

				if ($Db_object->affected_rows())
				{
					return true;
				}
			}
			break;

			case 'iplist':
			{
				foreach ($list AS $key => $ip)
				{
					$internal_list .= $ip . " ";
				}

				$Db_object->query("
					UPDATE " . $tableprefix . "setting SET
						value = CONCAT(value, ' $internal_list')
					WHERE varname = 'banip'
				");

				if ($Db_object->affected_rows())
				{
					return true;
				}
			}
			break;

			case 'namebansfull':
			{
				$user_id_list = array();

				foreach ($list AS $key => $vb_user_name)
				{
					$banned_userid = $Db_object->query_first("
						SELECT userid
						FROM " . $tableprefix . "user
						WHERE username = '" . addslashes($vb_user_name) . "'
					");

					$user_id_list[] = $banned_userid['userid'];
				}

				return $this->import_ban_list($Db_object, $databasetype, $tableprefix, $user_id_list, 'userid');
			}
			break;

			case 'userid':
			{
				$banned_group_id = $Db_object->query_first("
					SELECT usergroupid
					FROM " . $tableprefix . "usergroup
					WHERE title = 'Banned Users'
				");

				if ($banned_group_id['usergroupid'] != null)
				{
					foreach ($list AS $key => $banned_user_id)
					{
						$Db_object->query("
							UPDATE " . $tableprefix . "user SET
								membergroupids = CONCAT(membergroupids, ' $banned_group_id[usergroupid]')
							WHERE userid = '" . addslashes($banned_user_id) . "'
						");
					}
				}

				return true;
			}
			break;

			default:
			{
				return false;
			}
		}

		return false;
	}

	/**
	* Imports the current objects values as a User
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	public function import_user($Db_object, $databasetype, $tableprefix)
	{
		// Auto email associate
		if ($this->_auto_email_associate)
		{
			// Do a search for the email address to find the user to match this imported one to :
			$email_match = $Db_object->query_first("
				SELECT userid
				FROM " . $tableprefix . "user
				WHERE email = '" . addslashes($this->get_value('mandatory', 'email')) . "'
			");

			if ($email_match)
			{
				if ($this->associate_user($Db_object, $databasetype, $tableprefix, $this->get_value('mandatory', 'importuserid'), $email_match["userid"]))
				{
					// We matched the email address and associated propperly
					$result['automerge'] = true;
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

		$newpassword = '';
		$salt =	$this->fetch_user_salt();

		if ($this->_password_md5_already)
		{
			 $newpassword = md5($this->get_value('nonmandatory', 'password') . $salt);
		}
		else
		{
			$newpassword = md5(md5($this->get_value('nonmandatory', 'password')) . $salt);
		}

		// Link the admins
		if (strtolower($this->get_value('mandatory', 'username')) == 'admin')
		{
			$this->set_value('mandatory', 'username', 'imported_admin');
		}

		// If there is a dupe username pre_pend "imported_"
		$double_name = $Db_object->query("
			SELECT username
			FROM " . $tableprefix . "user
			WHERE username = '". addslashes($this->get_value('mandatory', 'username')) . "'
		");

		if ($Db_object->num_rows($double_name))
		{
			$this->set_value('mandatory', 'username', 'imported_' . $this->get_value('mandatory', 'username'));
		}

		$userdone = $Db_object->query("
			INSERT INTO	" . $tableprefix . "user
			(
				username, email, usergroupid,
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
				autosubscribe
			)
			VALUES
			(
				'" . addslashes($this->get_value('mandatory', 'username')) . "',
				'" . addslashes($this->get_value('mandatory', 'email')) . "',
				" . intval($this->get_value('mandatory', 'usergroupid')) . ",
				" . intval($this->get_value('mandatory', 'importuserid')) . ",
				'" . $newpassword . "',
				'" . addslashes($salt) . "',
				NOW(),
				" . intval($this->get_value('nonmandatory', 'options')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'homepage')) . "',
				" . intval($this->get_value('nonmandatory', 'posts')) . ",
				" . intval($this->get_value('nonmandatory', 'joindate')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'icq')) . "',
				" . intval($this->get_value('nonmandatory', 'daysprune')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'aim')) . "',
				" . intval($this->get_value('nonmandatory', 'membergroupids')) . ",
				" . intval($this->get_value('nonmandatory', 'displaygroupid')) . ",
				" . intval($this->get_value('nonmandatory', 'styleid')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'parentemail')) . "',
				'" . addslashes($this->get_value('nonmandatory', 'yahoo')) . "',
				" . intval($this->get_value('nonmandatory', 'showvbcode')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'usertitle')) . "',
				" . intval($this->get_value('nonmandatory', 'customtitle')) . ",
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
				'" . addslashes($this->get_value('nonmandatory', 'emailstamp')) . "',
				" . intval($this->get_value('nonmandatory', 'threadedmode')) . ",
				" . intval($this->get_value('nonmandatory', 'pmtotal')) . ",
				" . intval($this->get_value('nonmandatory', 'pmunread')) . ",
				" . intval($this->get_value('nonmandatory', 'autosubscribe')) . "
			)
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

			if ($this->_has_custom_types)
			{
				foreach ($this->get_custom_values() AS $key => $value)
				{
					if (!$this->import_user_field_value($Db_object, $databasetype, $tableprefix, $key, $value, $userid))
					{
						$this->_failedon = "import_user_field_value - $key - $value - $userid";
						return false;
					}
				}
			}

			if ($this->get_value('nonmandatory', 'avatar') != NULL)
			{
				$this->import_avatar($Db_object, $databasetype, $tableprefix,$userid,$this->get_value('nonmandatory', 'avatar'));
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

			if (array_key_exists('signature', $this->_default_values))
			{
				if (!$Db_object->query("
					UPDATE " . $tableprefix . "usertextfield SET
						signature = '" . $this->_default_values['signature'] . "'
					WHERE userid = '" . $userid . "'
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

	// Overridden to maintain salt and password details
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
				WHERE email = '" . addslashes($this->get_value('mandatory', 'email')) . "'
			");

			if ($email_match)
			{
				if ($this->associate_user($Db_object, $databasetype, $tableprefix, $this->get_value('mandatory', 'importuserid'), $email_match["userid"]))
				{
					// We matched the email address and associated propperly
					$result['automerge'] = true;
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

		// If there is a dupe username pre_pend "imported_"
		$double_name = $Db_object->query("
			SELECT username
			FROM " . $tableprefix . "user
			WHERE username = '". addslashes($this->get_value('mandatory', 'username')) . "'
		");

		if ($Db_object->num_rows($double_name))
		{
			$this->set_value('mandatory', 'username', 'imported_' . $this->get_value('mandatory', 'username'));
		}

		$userdone = $Db_object->query("
			INSERT INTO	" . $tableprefix . "user
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
				autosubscribe)
			VALUES
				('" . addslashes($this->get_value('mandatory', 'username')) . "',
				'" . addslashes($this->get_value('mandatory', 'email')) . "',
				" . intval($this->get_value('mandatory', 'usergroupid')) . ",
				" . intval($this->get_value('mandatory', 'importuserid')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'password')) . "',
				'" . addslashes($this->get_value('nonmandatory', 'salt')) . "',
				" . intval($this->get_value('nonmandatory', 'passworddate')) . ",
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
				" . intval($this->get_value('nonmandatory', 'autosubscribe')) . ")
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
				foreach ($this->get_default_values() as  $key => $value)
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
						signature='" . addslashes($this->_default_values['signature']) . "'
					WHERE userid='" . intval($userid) . "'
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
	* Imports the users avatar from a local file or URL.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	string	int				The userid
	* @param	string	int				The location of the avatar file
	*
	* @return	insert_id
	*/
	public function import_avatar($Db_object, $databasetype, $tableprefix, $userid, $file)
	{
		if ($filenum = @fopen($file, 'r'))
		{
			$contents = $this->vb_file_get_contents($file);

			if (!$file_sz = @filesize($file))
			{
				$file_sz = 0;
			}

			$urlbits = parse_url($file);
			$pathbits = pathinfo($urlbits['path']);

			$Db_object->query("
				INSERT INTO " . $tableprefix . "customavatar
					(userid, avatardata, dateline, filename, filesize)
				VALUES
					(" . intval($userid) . ",
					'" . addslashes($contents) . "',
					NOW(),
					'" . addslashes($pathbits['basename'])."',
					" . intval($file_sz) . ")
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
		else
		{
			return false;
		}
	}

	/**
	* Imports a usergroup
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	*
	* @return	insert_id
	*/
	public function import_user_group($Db_object, $databasetype, $tableprefix)
	{
		$cols = $Db_object->query("
			DESCRIBE " . $tableprefix . "usergroup
		");

		$there = false;

		while ($col = $Db_object->fetch_array($cols))
		{
			if ($col['Field'] == 'pmforwardmax')
			{
				$there = true;
			}
		}

		if (!$there)
		{
			$Db_object->query("
				ALTER TABLE " . $tableprefix . "usergroup ADD pmforwardmax SMALLINT( 5 ) UNSIGNED DEFAULT '5' NOT NULL
			");
		}

		$Db_object->query("
			INSERT INTO " . $tableprefix . "usergroup
				(importusergroupid, title, description,
				usertitle, passwordexpires, passwordhistory,
				pmquota, pmsendmax, pmforwardmax,
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
				" . intval($this->get_value('nonmandatory','pmforwardmax')) . ",
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
	* Imports the current objects values as a Forum
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	public function import_category($Db_object, $databasetype, $tableprefix)
	{
		if ($this->get_value('mandatory', 'options') == '!##NULL##!')
		{
			$this->set_value('mandatory', 'options', $this->_default_cat_permissions);
		}

		$result = $Db_object->query("
			INSERT INTO " . $tableprefix . "forum
				(styleid, title, description,
				options, daysprune, displayorder,
				parentid, importforumid, importcategoryid)
			VALUES
				(" . intval($this->get_value('mandatory', 'styleid')) . ",
				'" . addslashes($this->get_value('mandatory', 'title')) . "',
				'" . addslashes($this->get_value('nonmandatory', 'description')) . "',
				" . intval($this->get_value('mandatory', 'options')) . ",
				'30',
				" . intval($this->get_value('mandatory', 'displayorder')) . ",
				'-1',
				" . intval($this->get_value('mandatory', 'importforumid')) . ",
				" . intval($this->get_value('mandatory', 'importcategoryid')) . ")
		");

		$categoryid = $Db_object->insert_id($result);

		if ($result)
		{
			$Db_object->query("
				UPDATE " . $tableprefix . "forum SET
					parentlist = '" . intval($categoryid) . ",-1'
				WHERE forumid = " . intval($categoryid) . "
			");

			if ($Db_object->affected_rows())
			{
				return $categoryid;
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
	* Imports the current objects values as a Forum
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	public function import_forum($Db_object, $databasetype, $tableprefix)
	{
		$result = $Db_object->query("
			INSERT INTO " . $tableprefix . "forum
				(styleid, title, options,
				displayorder, parentid, importforumid,
				importcategoryid, description, replycount,
				lastpost, lastposter, lastthread,
				lastthreadid, lasticonid, threadcount,
				daysprune, newpostemail, newthreademail,
				parentlist, password, link, childlist)
			VALUES
				(" . intval($this->get_value('mandatory', 'styleid')) . ",
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
				'" . addslashes($this->get_value('nonmandatory', 'childlist')) . "')
		");

		$forumid = $Db_object->insert_id($result);

		if ($result)
		{
			$Db_object->query("
				UPDATE " . $tableprefix . "forum SET
					parentlist = '" . intval($forumid) . ",-1'
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
	public function import_vb2_forum($Db_object, $databasetype, $tableprefix)
	{
		$result = $Db_object->query("
			INSERT INTO " . $tableprefix . "forum
				(styleid, title, options,
				displayorder, parentid, importforumid,
				description, replycount,
				lastpost, lastposter, lastthread,
				lastthreadid, lasticonid, threadcount,
				daysprune, newpostemail, newthreademail,
				parentlist, password, link, childlist)
			VALUES
				(" . intval($this->get_value('mandatory', 'styleid')) . ",
				'" . addslashes($this->get_value('mandatory', 'title')) . "',
				" . intval($this->get_value('nonmandatory', 'options')) . ",
				" . intval($this->get_value('mandatory', 'displayorder')) . ",
				" . intval($this->get_value('mandatory', 'parentid')) . ",
				" . intval($this->get_value('mandatory', 'importforumid')) . ",
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
				'" . addslashes($this->get_value('nonmandatory', 'childlist')) . "')
		");

		$forumid = $Db_object->insert_id($result);

		return $forumid;
	}

	/**
	* Imports the current objects values as a Custom profile pic
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	public function import_custom_profile_pic($Db_object, $databasetype, $tableprefix)
	{
		if ($Db_object->query("
			INSERT INTO " . $tableprefix . "customprofilepic
				(importcustomprofilepicid, userid, profilepicdata, dateline, filename, visible, filesize)
			VALUES
				(" . intval($this->get_value('mandatory', 'importcustomprofilepicid')) . ",
				" . intval($this->get_value('nonmandatory', 'userid')) . ",
				" . addslashes($this->get_value('nonmandatory', 'profilepicdata')) . ",
				" . intval($this->get_value('nonmandatory', 'dateline')) . ",
				" . addslashes($this->get_value('nonmandatory', 'filename')) . ",
				" . intval($this->get_value('nonmandatory', 'visible')) . ",
				" . intval($this->get_value('nonmandatory', 'filesize')) . ")
		"))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	* Imports a customer userfield value
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	string	mixed			The key i.e. 'surname'
	* @param	string	mixed			The value i.e. 'Hutchings'
	*
	* @return	boolean
	*/
	public function import_user_field_value($Db_object, $databasetype, $tableprefix, $title, $value, $userid)
	{
		$fieldid = $Db_object->query_first("
			SELECT profilefieldid
			FROM " . $tableprefix . "profilefield
			WHERE title = '" . addslashes($title) . "'
		");

		// TODO: This will break with a 0 on field id, need to handle it a lot better and have a return
		if ($fieldid['profilefieldid'] AND $fieldid['profilefieldid'] > 0)
		{
			$Db_object->query("
				UPDATE " . $tableprefix . "userfield SET
					field" . intval($fieldid['profilefieldid']) . " = '" . addslashes($value) . "'
				WHERE userid = '" . intval($userid) . "'
			");

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	* Imports a rank, has to be used incombination with import usergroup to make sense get its usergroupid
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed		The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed		The prefix to the table name i.e. 'vb3_'
	*
	* @return	false/int		The tablerow inc id
	*/
	public function import_usergroup($Db_object, $databasetype, $tableprefix)
	{
		$cols = $Db_object->query("describe {$tableprefix}usergroup");
		$there = false;

		while ($col = $Db_object->fetch_array($cols))
		{
			if($col['Field'] == 'pmforwardmax')
			{
				$there = true;
			}
		}

		if(!$there)
		{
			$Db_object->query("ALTER TABLE `{$tableprefix}usergroup` ADD `pmforwardmax` SMALLINT( 5 ) UNSIGNED DEFAULT '5' NOT NULL");
		}

		$Db_object->query("
			INSERT INTO " . $tableprefix ."usergroup
				(importusergroupid, title, description,
				usertitle, passwordexpires, passwordhistory,
				pmquota, pmsendmax, pmforwardmax,
				opentag, closetag, canoverride,
				ispublicgroup, forumpermissions, pmpermissions,
				calendarpermissions, wolpermissions, adminpermissions,
				genericpermissions, genericoptions, attachlimit,
				avatarmaxwidth, avatarmaxheight, avatarmaxsize,
				profilepicmaxwidth, profilepicmaxheight, profilepicmaxsize)
			VALUES
				(" . intval($this->get_value('mandatory', 'importusergroupid')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'title')) . "',
				'" . addslashes($this->get_value('nonmandatory', 'description')) . "',
				'" . addslashes($this->get_value('nonmandatory', 'usertitle')) . "',
				" . intval($this->get_value('nonmandatory', 'passwordexpires')) . ",
				" . intval($this->get_value('nonmandatory', 'passwordhistory')) . ",
				" . intval($this->get_value('nonmandatory', 'pmquota')) . ",
				" . intval($this->get_value('nonmandatory', 'pmsendmax')) . ",
				" . intval($this->get_value('nonmandatory', 'pmforwardmax')) . ",
				'" . addslashes($this->get_value('nonmandatory', 'opentag')) . "',
				'" . addslashes($this->get_value('nonmandatory', 'closetag')) . "',
				" . intval($this->get_value('nonmandatory', 'canoverride')) . ",
				" . intval($this->get_value('nonmandatory', 'ispublicgroup')) . ",
				" . intval($this->get_value('nonmandatory', 'forumpermissions')) . ",
				" . intval($this->get_value('nonmandatory', 'pmpermissions')) . ",
				" . intval($this->get_value('nonmandatory', 'calendarpermissions')) . ",
				" . intval($this->get_value('nonmandatory', 'wolpermissions')) . ",
				" . intval($this->get_value('nonmandatory', 'adminpermissions')) . ",
				" . intval($this->get_value('nonmandatory', 'genericpermissions')) . ",
				" . intval($this->get_value('nonmandatory', 'genericoptions')) . ",
				" . intval($this->get_value('nonmandatory', 'attachlimit')) . ",
				" . intval($this->get_value('nonmandatory', 'avatarmaxwidth')) . ",
				" . intval($this->get_value('nonmandatory', 'avatarmaxheight')) . ",
				" . intval($this->get_value('nonmandatory', 'avatarmaxsize')) . ",
				" . intval($this->get_value('nonmandatory', 'profilepicmaxwidth')) . ",
				" . intval($this->get_value('nonmandatory', 'profilepicmaxheight')) . ",
				" . intval($this->get_value('nonmandatory', 'profilepicmaxsize')) . ")
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
	public function zuul($dana)
	{
		return "There is no 3.0.9 only 4.0.0";
	}

	/**
	* Imports a poll
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	string	mixed			The board type that we are importing from
	*
	* @return	boolean
	*/
	public function import_poll($Db_object, $databasetype, $tableprefix)
	{
		$Db_object->query("
			INSERT INTO " . $tableprefix . "poll
				(importpollid, question, dateline, options, votes, active, numberoptions, timeout, multiple, voters, public)
			VALUES
				(" . intval($this->get_value('mandatory', 'importpollid')) . ",
				'" . addslashes($this->get_value('mandatory', 'question')) . "',
				" . intval($this->get_value('mandatory', 'dateline')) . ",
				'" . addslashes($this->get_value('mandatory', 'options')) . "',
				" . intval($this->get_value('mandatory', 'votes')) . ",
				" . intval($this->get_value('nonmandatory', 'active')) . ",
				" . intval($this->get_value('nonmandatory', 'numberoptions')) . ",
				" . intval($this->get_value('nonmandatory', 'timeout')) . ",
				" . intval($this->get_value('nonmandatory', 'multiple'))  . ",
				" . intval($this->get_value('nonmandatory', 'voters')) . ",
				" . intval($this->get_value('nonmandatory', 'public')) . ")
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
	* Imports the current objects values as a Smilie
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	public function import_smilie($Db_object, $databasetype, $tableprefix, $prepend_path = true)
	{
		$update = $Db_object->query_first("
			SELECT smilieid
			FROM " . $tableprefix . "smilie
			WHERE smilietext = '" . addslashes($this->get_value('mandatory', 'smilietext')) . "'
		");

		if (!$update)
		{
			$Db_object->query("
				INSERT INTO	" . $tableprefix . "smilie
					(title, smilietext, smiliepath, imagecategoryid, displayorder, importsmilieid)
				VALUES
					('" . addslashes($this->get_value('nonmandatory', 'title')) . "',
					'" . addslashes($this->get_value('mandatory', 'smilietext')) . "',
					'" . addslashes($this->get_value('nonmandatory', 'smiliepath')) . "',
					" . intval($this->get_value('nonmandatory', 'imagecategoryid')) . ",
					" . intval($this->get_value('nonmandatory', 'displayorder')) . ",
					" . intval($this->get_value('mandatory', 'importsmilieid')) . ")
			");
		}
		else
		{
			$Db_object->query("
				UPDATE " . $tableprefix . "smilie SET
					title = '" . addslashes($this->get_value('nonmandatory', 'title')) . "',
					smiliepath = '" . addslashes($this->get_value('nonmandatory', 'smiliepath')) . "'
				WHERE smilietext = '" . addslashes($this->get_value('mandatory', 'smilietext')) . "'
			");
		}

		return ($Db_object->affected_rows() > 0)
	}

	/**
	* Import a poll from one vB3 board to another
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int	mixed				The vb_poll_id
	* @param	int	mixed				The import_poll_id
	*
	* @return	boolean
	*/
	public function import_poll_to_vb3_thread($Db_object, $databasetype, $tableprefix, $vb_poll_id, $import_poll_id)
	{
		$Db_object->query("
			UPDATE " . $tableprefix . "thread SET
				pollid = '" . intval($vb_poll_id) . "'
			WHERE pollid = '" . intval($import_poll_id) . "'
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

	/**
	* Updates parent ids of imported forums where parent id = 0
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	array	mixed			importforumid => forumid
	*
	* @return	array
	*/
	public function clean_nested_forums($Db_object, $databasetype, $tableprefix, $importid)
	{
		$do_list = $Db_object->query("
			SELECT forumid, importcategoryid
			FROM " . $tableprefix . "forum
			WHERE parentid = 0
				AND importforumid <> 0
		");

		while ($do = $Db_object->fetch_array($do_list))
		{
			$catid = $do['importcategoryid'];
			$fid = $do['forumid'];

			if ($importid[$catid] AND $fid)
			{
				$Db_object->query("UPDATE " . $tableprefix."forum SET parentid = " . intval($importid[$catid]) . " WHERE forumid = " . intval($fid) . "");
			}
		}
	}
}

?>