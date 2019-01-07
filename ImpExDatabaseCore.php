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

if (!class_exists('ImpExFunction')) { die('Direct class access violation'); }

class ImpExDatabaseCore extends ImpExFunction
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
	*/
	public function __constructor()
	{
	}

	/**
	* Retrieves the values needed to define a ImpExData object
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	string	mixed			The type of object being created
	*
	* @return	array|boolean
	*/
	public function create_data_type($Db_object, $databasetype, $tableprefix, $type, $product = 'vbulletin')
	{
		$returnarray = array();

		$result = $Db_object->query("
			SELECT fieldname, vbmandatory, defaultvalue, dictionary
			FROM " . $tableprefix . "vbfields
			WHERE tablename = '" . $type . "'
				AND product = '" . $product . "'
			ORDER BY vbmandatory
		");

		while ($line = $Db_object->fetch_array($result))
		{
				if ($line['vbmandatory'] == 'Y')
				{
					$returnarray["$type"]['mandatory']["$line[fieldname]"] =  $line['defaultvalue'];
				}

				if ($line['vbmandatory'] == 'N' || $line['vbmandatory'] == 'A')
				{
					$returnarray["$type"]['nonmandatory']["$line[fieldname]"] = $line['defaultvalue'];
				}

				$returnarray["$type"]['dictionary']["$line[fieldname]"] = $line['dictionary'];
		}
		return $returnarray;
	}

	/**
	* Modifies a table to include an importid.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	* @param	string	mixed			The name of the table to change.
	* @param	string	mixed			The name of the field to add to the table
	* @param	string	mixed			The type of the field to add to the table.
	*
	* @return	array|boolean
	*/
	public function add_import_id($Db_object, $databasetype, $tableprefix, $tablename, $importname, $type = 'BIGINT')
	{
		$rows = $Db_object->query("
			DESCRIBE " . $tableprefix . $tablename . " " . $importname . "
		");

		if ($Db_object->num_rows($rows))
		{
			return true;
		}
		else
		{
			$olderror = $Db_object->reporterror;
			$Db_object->reporterror = 0;

			if ($type == 'BIGINT')
			{
				$Db_object->query("
					ALTER TABLE " . $tableprefix . $tablename . "
					ADD COLUMN " . $importname . " BIGINT NOT NULL DEFAULT 0
				");
			}
			else
			{
				$Db_object->query("
					ALTER TABLE " . $tableprefix . $tablename . "
					ADD COLUMN " . $importname . " VARCHAR(255) NOT NULL DEFAULT '0'
				");
			}

			$haserror = $Db_object->errno();
			$Db_object->reporterror = $olderror;

			if (!$haserror)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
	}

	/**
	* Add the import ids into the given table.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	object	mixed			
	* @param	object	mixed			
	*/
	public function add_importids(&$Db_object, &$databasetype, &$tableprefix, &$displayobject, &$sessionobject)
	{
		foreach ($this->_import_ids AS $id => $table_array)
		{
			foreach ($table_array AS $tablename => $column)
			{
				if ($this->add_import_id($Db_object, $databasetype, $tableprefix, $tablename, $column))
				{
					$displayobject->display_now('<br /><b>' . $tablename . '</b> - ' . $column . ' <i>' . $displayobject->phrases['completed'] . '</i>');
				}
				else
				{
					$sessionobject->add_error(substr(get_class($this), -3), $displayobject->phrases['table_alter_fail'], $displayobject->phrases['table_alter_fail_rem']);
				}
			}
		}	
	}

	/**
	* Set as users importuserid, used when linking import users during associate.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	* @param	string	mixed			The user_id from the soruce board being imported.
	* @param	string	mixed			The vB userid to associate with.
	*
	* @return	boolean
	*/
	public function associate_user($Db_object, $databasetype, $tableprefix, $importuserid, $userid)
	{
		// NOTE: Handling for passing in an array ?
		$does_user_exist = $Db_object->query_first("
			SELECT userid, usergroupid
			FROM " . $tableprefix . "user
			WHERE userid = " . intval($userid) . "
		");

		if ($does_user_exist['userid'])
		{
			if ($does_user_exist['usergroupid'] == 6)
			{
				// Admin user, not allowing it
				return false;
			}

			$Db_object->query("
				UPDATE " . $tableprefix . "user SET
					importuserid = " . $importuserid . "
				WHERE userid = " . intval($userid) . "
			");

			return ($Db_object->affected_rows() > 0);
		}
		else
		{
			return false;
		}
	}

	/**
	* Adds an index to a table
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	* @param	string	mixed			The table name.
	*
	* @return	array
	*/
	public function add_index($Db_object, $databasetype, $tableprefix, $tablename)
	{
		// Check that there is not a empty value
		if (empty($tablename))
		{
			return false;
		}

		$keys = $Db_object->query("
			SHOW KEYS
			FROM " . $tableprefix . $tablename . "
		");

		while ($key = $Db_object->fetch_assoc($keys))
		{
			if ($key['Key_name'] == "import" . $tablename . "_index")
			{
				return true;
			}
		}

		return $Db_object->query("
			ALTER TABLE " . $tableprefix . $tablename . "
			ADD INDEX `import" . $tablename . "_index` (`import" . $tablename . "id`)
		");
	}

	/**
	* Check if specific product is installed.
	* 
	* @param	string		Database informations of the target database.
	* @param	string		Type of the target database.
	* @param	string		Table prefix of the target database.
	* @param	string		Product to verify.
	*/
	public function check_product_installed($Db_target, $target_db_type, $target_table_prefix, $product)
	{
		$tables = false;

		switch (strtolower($product))
		{
			case 'blog':
			{
				$tables = $this->_import_blog_ids;
				break;
			}

			default:
			{
				return $tables;
			}
		}

		foreach ($tables AS $table_array)
		{
			foreach ($table_array AS $tablename => $importid)
			{
				// If one is missing return false
				if (!$this->check_table($Db_target, $target_db_type, $target_table_prefix, $tablename))
				{
					return false;
				}
			}
		}

		// All there
		return true;
	}

	/**
	* Enumerates into an array and return its value if present.
	*
	* @param	string		Field to verify its presence.
	* @param	array		Array with data.
	* @param	string		Default value.
	*
	* @return	string		Field if valid.
	*/
	public function enum_check($get_field, $array, $default)
	{
		$get_field = strtolower($get_field);
		return (in_array($get_field, $array) == true ? $get_field : $default);
	}

	/**
	* Modifies the profilefield AND usertextfield table for a custom user entry
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	* @param	string	mixed			The title of the custom field.
	* @param	string	mixed			The description of the custom field.
	*
	* @return	array|boolean
	*/
	public function add_custom_field($Db_object, $databasetype, $tableprefix, $profiletitle, $profiledescription)
	{
		$rows = $Db_object->query("
			SELECT text
			FROM " . $tableprefix . "phrase
			WHERE varname LIKE '%_" . $profiletitle . "
		");

		if ($Db_object->num_rows($rows) > 0)
		{
			return true;
		}
		else
		{
			$displayorder = $Db_object->query_first("
				SELECT displayorder
				FROM " . $tableprefix . "profilefield
				ORDER BY displayorder DESC
				LIMIT 1
			");

			$neworder = intval($displayorder['displayorder']) + 1;

			$Db_object->query("
				INSERT INTO " . $tableprefix . "profilefield
					(displayorder)
				VALUES
					(" . intval($neworder) . ")");

			if ($Db_object->affected_rows())
			{
				$fieldid = $Db_object->insert_id();

				$Db_object->reporterror = 0;
				$Db_object->query("
					ALTER TABLE " . $tableprefix . "userfield
					ADD field" . intval($fieldid) . " mediumtext
				");

				$Db_object->query("
					INSERT INTO " . $tableprefix . "phrase
						(varname, fieldname, text, product)
					VALUES
						('field" . intval($fieldid) . "_title', 'cprofilefield', '" . $profiletitle . "', 'vbulletin')
				");

				$Db_object->query("
					INSERT INTO " . $tableprefix . "phrase
						(varname, fieldname, text, product)
					VALUES
						('field" . intval($fieldid) . "_desc', 'cprofilefield', '" . $profiletitle . "', 'vbulletin')
				");
				return true;
			}
			else
			{
				return false;
			}
		}
	}

	/**
	* Associate users to target vB installation.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	*
	* @return	array|boolean
	*/
	public function import_vb3_user($Db_object, $databasetype, $tableprefix)
	{
		// Check the dupe
		if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['user'] === false))
		{
			$there = $Db_object->query_first("
				SELECT importuserid
				FROM " . $tableprefix . "user
				WHERE importuserid = " . intval(trim($this->get_value('mandatory', 'importuserid'))) . "
			");

			if (is_numeric($there[0]))
			{
				return false;
			}
		}

		// TODO: Still need to check and see if all the current usernames being imported are unique
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
				WHERE email = '" . $Db_object->escape_string($this->get_value('mandatory', 'email')) . "'
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
					return 0;
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
				if ($this->associate_user($Db_object, $databasetype, $tableprefix, $this->get_value('mandatory', 'importuserid'), $userid_match["userid"]))
				{
					// We matched the userid address and associated propperly
					$result['automerge'] = true;
					$result['userid'] = $userid_match["userid"];
					return $result;
				}
				else
				{
					// Hmmm found the userid but didn't associate !!
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
			WHERE username LIKE '". $Db_object->escape_string($this->get_value('mandatory', 'username')) . "'
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
				autosubscribe, profilepicrevision, lastpostid,
				sigpicrevision, ipoints, infractions,
				warnings, infractiongroupids, infractiongroupid,
				adminoptions)
			VALUES
				('" . $Db_object->escape_string($this->get_value('mandatory', 'username')) . "',
				'" . $Db_object->escape_string($this->get_value('mandatory', 'email')) . "',
				" . intval($this->get_value('mandatory', 'usergroupid')) . ",
				" . intval($this->get_value('mandatory', 'importuserid')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'password')) . "',
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'salt')) . "',
				'" . $Db_onject->escape_string($this->get_value('nonmandatory', 'passworddate')) . "',
				" . intval($this->get_value('nonmandatory', 'options')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'homepage')) . "',
				" . intval($this->get_value('nonmandatory', 'posts')) . ",
				" . intval($this->get_value('nonmandatory', 'joindate')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'icq')) . "',
				'" . $Db_onject->escape_string($this->get_value('nonmandatory', 'daysprune')) . "',
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'aim')) . "',
				'" . $Db_onject->escape_string($this->get_value('nonmandatory', 'membergroupids')) . "',
				" . intval($this->get_value('nonmandatory', 'displaygroupid')) . ",
				" . intval($this->get_value('nonmandatory', 'styleid')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'parentemail')) . "',
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'yahoo')) . "',
				" . intval($this->get_value('nonmandatory', 'showvbcode')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'usertitle')) . "',
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'customtitle')) . "',
				" . intval($this->get_value('nonmandatory', 'lastvisit')) . ",
				" . intval($this->get_value('nonmandatory', 'lastactivity')) . ",
				" . intval($this->get_value('nonmandatory', 'lastpost')) . ",
				" . intval($this->get_value('nonmandatory', 'reputation')) . ",
				" . intval($this->get_value('nonmandatory', 'reputationlevelid')) . ",
				'" . $Db_onject->escape_string($this->get_value('nonmandatory', 'timezoneoffset')) . "',
				" . intval($this->get_value('nonmandatory', 'pmpopup')) . ",
				" . intval($this->get_value('nonmandatory', 'avatarid')) . ",
				" . intval($this->get_value('nonmandatory', 'avatarrevision')) . ",
				'" . $Db_onject->escape_string($this->get_value('nonmandatory', 'birthday')) . "',
				'" . $Db_onject->escape_string($this->get_value('nonmandatory', 'birthday_search')) . "',
				" . intval($this->get_value('nonmandatory', 'maxposts')) . ",
				" . intval($this->get_value('nonmandatory', 'startofweek')) . ",
				'" . $Db_onject->escape_string($this->get_value('nonmandatory', 'ipaddress')) . "',
				" . intval($this->get_value('nonmandatory', 'referrerid')) . ",
				" . intval($this->get_value('nonmandatory', 'languageid')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'msn')) . "',
				" . intval($this->get_value('nonmandatory', 'emailstamp')) . ",
				" . intval($this->get_value('nonmandatory', 'threadedmode')) . ",
				" . intval($this->get_value('nonmandatory', 'pmtotal')) . ",
				" . intval($this->get_value('nonmandatory', 'pmunread')) . ",
				" . intval($this->get_value('nonmandatory', 'autosubscribe')) . ",
				" . intval($this->get_value('nonmandatory', 'profilepicrevision')) . ",
				" . intval($this->get_value('nonmandatory', 'lastpostid')) . ",
				" . intval($this->get_value('nonmandatory', 'sigpicrevision')) . ",
				" . intval($this->get_value('nonmandatory', 'ipoints')) . ",
				" . intval($this->get_value('nonmandatory', 'infractions')) . ",
				" . intval($this->get_value('nonmandatory', 'warnings')) . ",
				" . intval($this->get_value('nonmandatory', 'infractiongroupids')) . ",
				" . intval($this->get_value('nonmandatory', 'infractiongroupid')) . ",
				" . intval($this->get_value('nonmandatory', 'adminoptions')) . ")
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

			if (array_key_exists('signature', $this->_default_values))
			{
				if (!$Db_object->query("
					UPDATE " . $tableprefix . "usertextfield SET
						signature = '" . $Db_object->escape_string($this->_default_values['signature']) . "'
					WHERE userid = " . intval($userid) . "
				"))
				{
					$this->_failedon = "usertextfield SET signature";
					return false;
				}
			}

			if ($this->get_value('nonmandatory', 'usernote') != NULL)
			{
				$Db_object->query("
					INSERT INTO	" . $tableprefix . "usernote
						(userid, posterid, username, dateline, message, title, allowsmilies, importusernoteid)
					VALUES
						(" . intval($userid) . ", 0, '', " . time() . ", '" . $Db_object->escape_string($this->get_value('nonmandatory', 'usernote')) . "', 'Imported Note', 0, 1)
					");
			}
		}
		else
		{
			return false;
		}

		return $userid;
	}

	/**
	* Import a poll from one vB3 board to another
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	* @param	int	mixed				The vb_poll_id.
	* @param	int	mixed				The import_poll_id.
	*
	* @return	boolean
	*/
	public function import_poll_to_vb3_thread($Db_object, $databasetype, $tableprefix, $vb_poll_id, $import_poll_id)
	{
		if (!$vb_poll_id OR !$import_poll_id)
		{
			return false;
		}

		if ($vb_poll_id == $import_poll_id)
		{
			return true;
		}

		$Db_object->query("
			UPDATE " . $tableprefix . "thread SET
				pollid = " . intval($vb_poll_id)  . "
			WHERE pollid = " . intval($import_poll_id) . "
				AND importthreadid > 0
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
	* Imports the current objects values as a Smilie.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	*
	* @return	boolean
	*/
	public function import_smilie($Db_object, $databasetype, $tableprefix, $prepend_path = true)
	{
		if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['smilie'] === false))
		{
			$there = $Db_object->query_first("
				SELECT importsmilieid
				FROM " . $tableprefix . "smilie
				WHERE importsmilieid = " . intval(trim($this->get_value('mandatory', 'importsmilieid'))) . "
			");

			if (is_numeric($there[0]))
			{
				return false;
			}
		}

		$smilie_path = ($prepend_path == true ? 'images/smilies/' : '');

		$update = $Db_object->query_first("
			SELECT smilieid
			FROM " . $tableprefix . "smilie
			WHERE smilietext = '". $Db_object->escape_string($this->get_value('mandatory', 'smilietext')) . "'
		");

		if (!$update)
		{
			$Db_object->query("
				INSERT INTO	" . $tableprefix . "smilie
				(
					title, smilietext, smiliepath,
					imagecategoryid, displayorder, importsmilieid
				)
				VALUES
				(
					'" . $Db_object->escape_string($this->get_value('nonmandatory', 'title')) . "',
					'" . $Db_object->escape_string($this->get_value('mandatory', 'smilietext')) . "',
					'" . $smilie_path . $Db_object->escape_string($this->get_value('nonmandatory', 'smiliepath')) . "',
					" . intval($this->get_value('nonmandatory', 'imagecategoryid')) . ",
					" . intval($this->get_value('nonmandatory', 'displayorder')) . ",
					" . intval($this->get_value('mandatory', 'importsmilieid')) . "
				)
			");
		}
		else
		{
			// Don't change the smilie title if it is the same as the smilietext
			if ($this->get_value('nonmandatory', 'title') == $this->get_value('mandatory', 'smilietext'))
			{
				$title = 'title';
			}
			else
			{
				$title = "'" . $Db_object->escape_string($this->get_value('nonmandatory', 'title')) . "'";
			}

			$Db_object->query("
				UPDATE " . $tableprefix . "smilie SET
					title = $title,
					smiliepath = '" . $smilie_path . $Db_object->escape_string($this->get_value('nonmandatory', 'smiliepath')) . "'
				WHERE smilietext = '" . $Db_object->escape_string($this->get_value('mandatory', 'smilietext')) . "'
			");
		}

		return ($Db_object->affected_rows() > 0);
	}

	/**
	* Imports a poll
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	*
	* @return	boolean
	*/
	public function import_poll($Db_object, $databasetype, $tableprefix)
	{
		if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['poll'] === false))
		{
			$there = $Db_object->query_first("
				SELECT importpollid
				FROM " . $tableprefix . "poll
				WHERE importpollid = " . intval(trim($this->get_value('mandatory', 'importpollid'))) . "
			");

			if (is_numeric($there[0]))
			{
				return false;
			}
		}

		$Db_object->query("
			INSERT INTO " . $tableprefix . "poll
				(importpollid, question, dateline, options, votes, active, numberoptions, timeout, multiple, voters, public, lastvote)
			VALUES
				(" . intval($this->get_value('mandatory', 'importpollid')) . ",
				'" . $Db_object->escape_string($this->get_value('mandatory', 'question')) . "',
				" . intval($this->get_value('mandatory', 'dateline')) . ",
				'" . $Db_object->escape_string($this->get_value('mandatory', 'options')) . "',
				" . intval($this->get_value('mandatory', 'votes')) . ",
				" . intval($this->get_value('nonmandatory', 'active')) . ",
				" . intval($this->get_value('nonmandatory', 'numberoptions')) . ",
				" . intval($this->get_value('nonmandatory', 'timeout')) . ",
				" . intval($this->get_value('nonmandatory', 'multiple'))  . ",
				" . intval($this->get_value('nonmandatory', 'voters')) . ",
				" . intval($this->get_value('nonmandatory', 'public')) . ",
				" . intval($this->get_value('nonmandatory', 'lastvote')) . ")
		");

		return $Db_object->insert_id();
	}

	/**
	* Imports a phrase.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	*
	* @return	boolean
	*/
	public function import_phrase($Db_object, $databasetype, $tableprefix)
	{
		if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['phrase'] === false))
		{
			$there = $Db_object->query_first("
				SELECT importphraseid
				FROM " . $tableprefix . "phrase
				WHERE importphraseid = " . intval(trim($this->get_value('mandatory', 'importphraseid'))) . "
			");

			if (is_numeric($there[0]))
			{
				return false;
			}
		}

		// Check for duplicate key :: name_lang_type
		$there = $Db_object->query_first("
			SELECT phraseid
			FROM " . $tableprefix . "phrase
			WHERE varname = '" . $Db_object->escape_string($this->get_value('mandatory', 'varname')) . "'");

		if ($there['phraseid'])
		{
			$this->_failedon = 'Duplicate Key';
			return false;
		}
		unset($there);

		$Db_object->query("
			INSERT INTO " . $tableprefix . "phrase
				(importphraseid, varname, fieldname, text, languageid, product, username, dateline, version)
			VALUES
				(" . intval($this->get_value('mandatory', 'importphraseid')) . ",
				'" . $Db_object->escape_string($this->get_value('mandatory', 'varname')) . "',
				'" . $Db_object->escape_string($this->get_value('mandatory', 'fieldname')) . "',
				'" . $Db_object->escape_string($this->get_value('mandatory', 'text')) . "',
				" . intval($this->get_value('nonmandatory', 'languageid')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'product')) . "',
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'username')) . "',
				" . intval($this->get_value('nonmandatory', 'dateline')) . ",
				" . intval($this->get_value('nonmandatory', 'version')) . ")
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
	* Imports a subscription
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	*
	* @return	boolean
	*/
	public function import_subscription($Db_object, $databasetype, $tableprefix)
	{
		if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['subscription'] === false))
		{
			$there = $Db_object->query_first("
				SELECT importsubscriptionid
				FROM " . $tableprefix . "subscription
				WHERE importsubscriptionid = " . intval(trim($this->get_value('mandatory', 'importsubscriptionid'))) . "
			");

			if (is_numeric($there[0]))
			{
				return false;
			}
		}

		$Db_object->query("
			INSERT INTO " . $tableprefix . "subscription
				(importsubscriptionid, cost, membergroupids, active, options, varname, adminoptions, displayorder, forums, nusergroupid)
			VALUES
				(" . intval($this->get_value('mandatory', 'importsubscriptionid')) . ",
				'" . $Db_object->escape_string($this->get_value('mandatory', 'cost')) . "',
				'" . $Db_object->escape_string($this->get_value('mandatory', 'membergroupids')) . "',
				" . intval($this->get_value('mandatory', 'active')) . ",
				" . intval($this->get_value('mandatory', 'options')) . ",
				'" . $Db_object->escape_string($this->get_value('mandatory', 'varname')) . "',
				" . intval($this->get_value('mandatory', 'adminoptions')) . ",
				" . intval($this->get_value('nonmandatory', 'displayorder')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'forums')) . "',
				" . intval($this->get_value('nonmandatory', 'nusergroupid')) . ")
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
	* Imports a subscription log.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	*
	* @return	boolean
	*/
	public function import_subscriptionlog($Db_object, $databasetype, $tableprefix)
	{
		if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['subscriptionlog'] === false))
		{
			$there = $Db_object->query_first("
				SELECT importsubscriptionlogid
				FROM " . $tableprefix . "subscriptionlog
				WHERE importsubscriptionlogid = " . intval(trim($this->get_value('mandatory', 'importsubscriptionlogid'))) . "
			");

			if (is_numeric($there[0]))
			{
				return false;
			}
		}

		$Db_object->query("
			INSERT INTO " . $tableprefix . "subscriptionlog
				(importsubscriptionlogid, subscriptionid, userid, pusergroupid, status, regdate, expirydate)
			VALUES
				(" . intval($this->get_value('mandatory', 'importsubscriptionlogid')) . ",
				" . intval($this->get_value('mandatory', 'subscriptionid')) . ",
				" . intval($this->get_value('mandatory', 'userid')) . ",
				" . intval($this->get_value('mandatory', 'pusergroupid')) . ",
				" . intval($this->get_value('mandatory', 'status')) . ",
				" . intval($this->get_value('mandatory', 'regdate')) . ",
				" . intval($this->get_value('mandatory', 'expirydate')) . ")
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
	* Imports the users avatar from a local file or URL including saving the new avatar and optionally assigning it to a user.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	string	int				The userid
	* @param	string	int				The categoryid for avatars
	* @param	string	int				The source file name
	* @param	string	int				The target file name (i.e. the file to be created)
	* @param	object	displayobject	The display object.
	*
	* @return	insert_id
	*/
	public function copy_avatar(&$Db_object, &$databasetype, $tableprefix, $sourcefile, $targetfile, &$displayobject)
	{
		// If we have already imported this avatar, we just need to assign it.
		$avatar_qry = $Db_object->query("
			SELECT avatarid
			FROM " . $tableprefix . "avatar WHERE
			importavatarid = " . intval(trim($this->get_value('mandatory', 'importavatarid'))) . "
		");

		if ($avatar_info = $Db_object->fetch_array($avatar_qry))
		{
			if ($avatar_info['avatarid'])
			{
				return $avatar_info['avatarid'];
			}
		}
		break;

		// first we need to save the file.
		$file_contents = $this->vb_file_get_contents($sourcefile);

		if (!$file_contents)
		{
			return $displayobject->construct_phrase($displayobject->phrases['file_missing_empty_hidden'], $sourcefile) . '<br />'; // 'File ' . $sourcefile . ' is either missing, empty, or hidden.<br />\n';
		}

		if (!$this->vb_file_save_contents($targetfile, $file_contents))
		{
			return $displayobject->phrases['save_file_failed'] . '<br />';//'The file create/save command failed. Please check the target folder location and permissions.<br />\n';
		}

		// If we already have a record we'll update it.
		$current_file_qry = $Db_object->query("
			SELECT avatarid
			FROM " . $tableprefix . "avatar
			WHERE avatarpath = '" . $Db_object->escape_string($this->get_value('nonmandatory', 'avatarpath')) . "'
		");

		if ($current_file_qry)
		{
			$current_data = $Db_object->fetch_array($details_list);

			if ($current_data AND intval($current_data['avatarid']))
			{
				$Db_object->query("
					UPDATE " . $tableprefix . "avatar SET
						title = '" . $Db_object->escape_string($this->get_value('nonmandatory', 'title')) . "',
						minimumposts = 0,
						imagecategoryid = " . intval($this->get_value('nonmandatory', 'imagecategoryid'))  . ",
						importavatarid = " . intval($this->get_value('mandatory', 'importavatarid'))  . "
					WHERE avatarid = " . intval($current_data['avatarid']) . "
				");

				return $current_data['avatarid'];
			}
		}

		$Db_object->query("
			INSERT INTO " . $tableprefix . "avatar
				(title, minimumposts, avatarpath, imagecategoryid, displayorder, importavatarid)
			VALUES
				('" . $Db_object->escape_string($this->get_value('nonmandatory', 'title')) . "',
				0,
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'avatarpath')) . "',
				" . intval($this->get_value('nonmandatory','imagecategoryid'))  . ",
				1,
				" . intval($this->get_value('mandatory','importavatarid'))  . ")
		");

		$avatarid = $Db_object->insert_id();
		return $avatarid;
	}

	/**
	* Updates a thread pollid
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	* @param	string	mixed			The vB poll id.
	* @param	string	mixed			The import thread id of the thread that you want to attach the poll to.
	*
	* @return	boolean
	*/
	public function import_poll_to_thread($Db_object, $databasetype, $tableprefix, $vb_poll_id, $import_thread_id, $vb_thread_id = false)
	{
		if (!is_numeric($import_thread_id))
		{
			return false;
		}

		if (!$vb_thread_id)
		{
			$thread_exists = $Db_object->query("
				SELECT threadid
				FROM " . $tableprefix . "thread
				WHERE importthreadid = '" . intval($import_thread_id) . "'
			");

			if ($Db_object->num_rows($thread_exists))
			{
				$Db_object->query("
					UPDATE " . $tableprefix . "thread SET
						pollid = " . intval($vb_poll_id) . "
					WHERE importthreadid = " . intval($import_thread_id) . "
				");
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			if (empty($import_thread_id))
			{
				return false;
			}

			$Db_object->query("
				UPDATE " . $tableprefix . "thread SET
					pollid = " . intval($vb_poll_id) . "
				WHERE threadid = " . intval($import_thread_id) . "
			");

			// Its not the $import_thread_id its the vB one
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

	/**
	* Updates a thread pollid
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	string	mixed			poll_voters_array = $var = array ( 'vb_user_id' => 'vote_option'); etc
	* @param	string	mixed			The vB poll id
	*
	* @return	boolean
	*/
	public function import_poll_voters($Db_object, $databasetype, $tableprefix, $poll_voters_array ,$vb_poll_id)
	{
		// if $vote_option == 0 then it wasn't possible to get hold of the pollvote.voteoption
		if (!empty($poll_voters_array))
		{
			foreach ($poll_voters_array AS $vb_user_id => $vote_option)
			{
				if (empty($vb_user_id))
				{
					continue;
				}

				if ($vote_option == 0 OR empty($vote_option))
				{
					$Db_object->query("
						INSERT INTO " . $tableprefix . "pollvote
							(pollid, userid)
						VALUES
							(" . intval($vb_poll_id) . ", " . intval($vb_user_id) . ")
					");
				}
				else
				{
					$Db_object->query("
						INSERT INTO " . $tableprefix . "pollvote
							(pollid, userid, voteoption)
						VALUES
							(" . intval($vb_poll_id) . ", " . intval($vb_user_id) . ", " . intval($vote_option) . ")
					");
				}
			}
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	* Imports an attachment.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	boolean					Import post id?
	*
	* @return	boolean
	*/
	public function import_attachment($Db_object, $databasetype, $tableprefix, $import_post_id = TRUE)
	{
		// Check the dupe
		if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['attachment'] === false))
		{
			$there = $Db_object->query_first("
				SELECT attachmentid
				FROM " . $tableprefix . "attachment
				WHERE importattachmentid = " . intval(trim($this->get_value('mandatory', 'importattachmentid'))) . "
			");

			if (is_numeric($there[0]))
			{
				return false;
			}
		}

		if ($import_post_id)
		{
			if ($this->get_value('nonmandatory', 'postid'))
			{
				// Get the real post id
				$post_id = $Db_object->query_first("
					SELECT postid, threadid, userid
					FROM " . $tableprefix . "post
					WHERE importpostid = " . intval($this->get_value('nonmandatory', 'postid')));

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
			$Db_object->query_first("
				SELECT userid, postid, threadid
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

		// Update the thread attach
		$Db_object->query("
			UPDATE " . $tableprefix . "thread SET
				attach = attach + 1
			WHERE threadid = " . intval($post_id['threadid']) . "
		");

		// Ok, so now where is it going ......
		$attachpath =  $this->get_options_setting($Db_object, $databasetype, $tableprefix, 'attachpath');
		$attachfile = $this->get_options_setting($Db_object, $databasetype, $tableprefix, 'attachfile');

		if (!$this->get_value('nonmandatory', 'extension'))
		{
			$ext = $this->get_value('mandatory', 'filename');
			$this->set_value('nonmandatory', 'extension', strtolower(substr($ext, strrpos($ext, '.')+1)));
		}

		$Db_object->query("
			INSERT INTO " . $tableprefix . "attachment
				(importattachmentid, filename, dateline, visible, counter, postid, filehash, userid, extension)
			VALUES
				(" . intval($this->get_value('mandatory', 'importattachmentid')) . ",
				'" . $Db_object->escape_string($this->get_value('mandatory', 'filename')) . "',
				" . intval($this->get_value('nonmandatory', 'dateline')) . ",
				" . intval($this->get_value('nonmandatory', 'visible')) . ",
				" . intval($this->get_value('nonmandatory', 'counter')) . ",
				" . intval($post_id['postid']) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'filehash')) . ",
				" . intval($post_id['userid']) . ",
				'" . $db_object->escape_string($this->get_value('nonmandatory', 'extension')) . "')
		");

		$attachment_id = $Db_object->insert_id();

		switch (intval($attachfile))
		{
			case '0':	// Straight into the dB
			{
				$Db_object->query("
					UPDATE " . $tableprefix . "attachment SET
						filedata = '" . $Db_object->escape_string($this->get_value('mandatory', 'filedata')) . "',
						filesize = " . intval($this->get_value('nonmandatory', 'filesize'))  . "
					WHERE attachmentid = " . intval($attachment_id) ."
				");

				return $attachment_id;
			}

			case '1':	// file system OLD naming schema
			{
				$full_path = $this->fetch_attachment_path($post_id['userid'], $attachpath, false, $attachment_id);

				if ($this->vbmkdir(substr($full_path, 0, strrpos($full_path, '/'))))
				{
					if ($fp = fopen($full_path, 'wb'))
					{
						fwrite($fp, $this->get_value('mandatory', 'filedata'));
						fclose($fp);
						$filesize = filesize($full_path);

						if ($filesize)
						{
							$Db_object->query("
								UPDATE " . $tableprefix . "attachment SET
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

				if ($this->vbmkdir(substr($full_path, 0, strrpos($full_path, '/'))))
				{
					if ($fp = fopen($full_path, 'wb'))
					{
						fwrite($fp, $this->get_value('mandatory', 'filedata'));
						fclose($fp);
						$filesize = filesize($full_path);

						if ($filesize)
						{
							$Db_object->query("
								UPDATE " . $tableprefix . "attachment SET
									filesize = " . $this->get_value('nonmandatory', 'filesize')  . "
								WHERE attachmentid = " . intval($attachment_id) . "
							");

							return $attachment_id;
						}
					}
				}

				return false;
			}
			default :
			{
				// Shouldn't ever get here
				return false;
			}
		}
	}

	/**
	* Imports a vB4 blog attachment.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	* @param	string	mixed			Blog id to import.
	*
	* @return	array					Blog informations.
	*/
	private function import_vb4_attachment_entry(&$Db_object, &$databasetype, &$tableprefix, $import_blog_id)
	{
		if ($import_blog_id)
		{
			if ($this->get_value('nonmandatory', 'postid'))
			{
				// Get the real blog id
				$blog_id = $Db_object->query_first("
					SELECT blogid AS contentid, userid
					FROM " . $tableprefix . "blog
					WHERE importblogid = " . intval($this->get_value('nonmandatory', 'postid')) . "
				");
			}
			else
			{
				return false;
			}
		}
		else
		{
			$blog_id = $Db_object->query_first("
				SELECT blogid AS contentid, userid
				FROM " . $tableprefix . "blog
				WHERE blogid = " . intval($this->get_value('nonmandatory', 'postid')) . "
			");
		}

		return $blog_id;
	}

	/**
	* Imports a vB4 article attachment.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	* @param	string	mixed			Article id to import.
	*
	* @return	array					Article informations.
	*/
	private function import_vb4_attachment_article(&$Db_object, &$databasetype, &$tableprefix, $import_content_id)
	{
		if ($import_content_id)
		{
			if ($this->get_value('nonmandatory', 'postid'))
			{
				// Get the real article id
				$content = $Db_object->query_first("
					SELECT nodeid AS contentid, userid
					FROM " . $tableprefix . "cms_node
					WHERE importcmsnodeid = " . intval($this->get_value('nonmandatory', 'postid')) . "
				");
			}
			else
			{
				return false;
			}
		}
		else
		{
			$content = $Db_object->query_first("
				SELECT nodeid AS contentid, userid
				FROM " . $tableprefix . "cms_node
				WHERE nodeid = " . intval($this->get_value('nonmandatory', 'postid')) . "
			");
		}
		return $content;
	}

	/**
	* Imports a vB4 post attachment.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	* @param	string	mixed			Post id to import.
	*
	* @return	array					Post informations.
	*/
	private function import_vb4_attachment_post(&$Db_object, &$databasetype, &$tableprefix, $import_post_id)
	{
		if ($import_post_id)
		{
			if ($this->get_value('nonmandatory', 'postid'))
			{
				// Get the real post id
				$post_id = $Db_object->query_first("
					SELECT postid AS contentid, userid
					FROM " . $tableprefix . "post
					WHERE importpostid = " . intval($this->get_value('nonmandatory', 'postid')) . "
				");
			}
			else
			{
				return false;
			}
		}
		else
		{
			$post_id = $Db_object->query_first("
				SELECT postid AS contentid, userid
				FROM " . $tableprefix . "post
				WHERE postid = " . intval($this->get_value('nonmandatory', 'postid')) . "
			");
		}
		return $post_id;
	}

	/**
	* Imports attachments.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	* @param	string	mixed			Article id to import.
	*
	* @return	boolean
	*/
	public function import_vb4_attachment(&$Db_object, &$databasetype, &$tableprefix, $import_content_id = true, $parenttype = 'post')
	{
		/*
		 Flow :
			1) Get the post if we don't have it
			2) Update the attach count on post table
			3) Find the target location of the data (file system or database), default to database
			4) Write the data to the store and get the auto_inc id
			5) Update attachment
			6) Return attachmentid
		*/

		// Update the post attach
		switch($parenttype)
		{
			case 'post':
			{
				if (!($content = $this->import_vb4_attachment_post($Db_object, $databasetype, $tableprefix, $import_content_id)))
				{
					return false;
				}

				if (!($contenttypeid = $this->get_contenttypeid($Db_object, $databasetype, $tableprefix, 'vbulletin', 'Post')))
				{
					return false;
				}

				$Db_object->query("
					UPDATE " . $tableprefix . "post SET
						attach = attach + 1
					WHERE postid = " . intval($content['contentid']) . "
				");

				break;
			}

			case 'blog':
			{
				if (!($content = $this->import_vb4_attachment_entry($Db_object, $databasetype, $tableprefix, $import_content_id)))
				{
					return false;
				}

				if (!($contenttypeid = $this->get_contenttypeid($Db_object, $databasetype, $tableprefix, 'vbblog', 'BlogEntry')))
				{
					return false;
				}

				$Db_object->query("
					UPDATE " . $tableprefix . "blog SET
						attach = attach + 1
					WHERE blogid = " . intval($content['contentid']) . "
				");

				break;
			}

			case 'cms':
			{
				if (!($content = $this->import_vb4_attachment_article($Db_object, $databasetype, $tableprefix, $import_content_id)))
				{
					return false;
				}

				if (!($contenttypeid = $this->get_contenttypeid($Db_object, $databasetype, $tableprefix, 'vbcms', 'Article')))
				{
					return false;
				}

				break;
			}

			default:
			{
				return false;
			}
		}

		// Ok, so now where is it going ......
		$attachpath =  $this->get_options_setting($Db_object, $databasetype, $tableprefix, 'attachpath');
		$attachfile = $this->get_options_setting($Db_object, $databasetype, $tableprefix, 'attachfile');

		$extension = $this->get_value('mandatory', 'filename');
		$extension = substr($extension, strpos($extension, '.') + 1);

		// Put something into the filedata table and get the auto_inc #
		// Check if filedata exists first
		if (!($filehash = $this->get_value('nonmandatory', 'filehash')))
		{
			$filehash = md5($this->get_value('mandatory', 'filedata'));
		}

		if (!($filesize = $this->get_value('nonmandatory', 'filesize')))
		{
			$filesize = strlen($filedata);
		}

		$result = $Db_object->query_first("
			SELECT filedataid
			FROM " . $tableprefix . "filedata
			WHERE filehash = '" . $Db_object->escape_string($filehash) . "'
				AND userid = " . intval($content['userid']) . "
		");

		$filedataid = $result ? $result['filedataid'] : 0;

		if ($filedataid)
		{
			$Db_object->query("
				UPDATE " . $tableprefix . "filedata SET
					refcount = refcount + 1
				WHERE filedataid = " . intval($filedataid) . "
			");

			$insert = false;
		}
		else
		{
			$Db_object->query("
				INSERT INTO " . $tableprefix . "filedata
					(importfiledataid, userid, dateline, thumbnail_dateline, filesize, filehash, extension, height, width, refcount)
				VALUES
					(1,
					" . intval($content['userid']) . ",
					" . @time() . ",
					" . @time() . ",
					" . intval($filesize) . ",
					'" . $Db_object->escape_string($filehash) . "',
					'" . $Db_object->escape_string($extension) . "',
					" . intval($this->get_value('nonmandatory', 'height')) . ",
					" . intval($this->get_value('nonmandatory', 'width')) . ",
					1)
			");

			$insert = true;
			$filedataid = $Db_object->insert_id();
		}

		if ($insert)
		{
			switch (intval($attachfile))
			{
				case '0':	// Straight into the dB
				{
					$Db_object->query("
						UPDATE " . $tableprefix . "filedata SET
							filedata = '" . $Db_object->escape_string($this->get_value('mandatory', 'filedata')) . "'
						WHERE filedataid = " . intval($filedataid) . "
					");
					break;
				}

				case '1':	// file system OLD naming schema
				{
					$full_path = $this->fetch_attachment_path($content['userid'], $attachpath, false, $filedataid);

					if ($this->vbmkdir(substr($full_path, 0, strrpos($full_path, '/'))))
					{
						if ($fp = fopen($full_path, 'wb'))
						{
							fwrite($fp, $this->get_value('mandatory', 'filedata'));
							fclose($fp);
							$filesize = filesize($full_path);

							if (!$filesize)
							{
								return false;
							}
						}
					}
				}

				case '2':	// file system NEW naming schema
				{
					$full_path = $this->fetch_attachment_path($content['userid'], $attachpath, true, $filedataid);

					if ($this->vbmkdir(substr($full_path, 0, strrpos($full_path, '/'))))
					{
						if ($fp = fopen($full_path, 'wb'))
						{
							fwrite($fp, $this->get_value('mandatory', 'filedata'));
							fclose($fp);
							$filesize = filesize($full_path);

							if (!$filesize)
							{
								return false;
							}
						}
					}
				}

				default :
				{
					// Shouldn't ever get here
					return false;
				}
			}
		}

		/*
			posthash - TODO
			contentid - TODO

			import id for filedata and clean out

			=contenttypeid=
			1-Post			2-Thread		3-Forum		4-Announcement		5-SocialGroupMessage	6-SocialGroupDiscussion
			7-SocialGroup	8-Album			9-Picture	10-PictureComment	11-VisitorMessage		12-User
			13-Event		14-Calendar
		*/

		$caption = $this->get_value('nonmandatory', 'caption') ? $this->get_value('nonmandatory', 'caption') : $this->get_value('mandatory', 'filename');

		if ($this->get_value('nonmandatory', 'visible') == 'visible' OR $this->get_value('nonmandatory', 'visible') == 'moderation')
		{
			$state = $this->get_value('nonmandatory', 'visible');
		}
		else
		{
			$state = 'visible';
		}

		$Db_object->query("
			INSERT INTO " . $tableprefix . "attachment
				(importattachmentid,
				filename,
				userid,
				dateline,
				counter,
				reportthreadid,
				caption,
				state,
				contentid,
				filedataid,
				contenttypeid,
				settings,
				displayorder)
			VALUES
				(" . intval($this->get_value('mandatory', 'importattachmentid')) . ",
				'" . $Db_object->escape_string($this->get_value('mandatory', 'filename')) . "',
				" . intval($content['userid']) . "',
				'" . intval($this->get_value('nonmandatory', 'dateline'))  . "',
				'" . intval($this->get_value('nonmandatory', 'counter'))  . "',
				0,
				'" . $Db_object->escape_string($caption) . "',
				'" . $Db_object->escape_string($state) . "',
				" . intval($content['contentid'])  . ",
				" . intval($filedataid) . ",
				" . intval($contenttypeid) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'settings')) . "',
				" . intval($this->get_value('nonmandatory', 'displayorder')) . ")
		");

		$attachmentid = $Db_object->insert_id();
		$importattachmentid = intval($this->get_value('mandatory', 'importattachmentid'));

		$this->update_content_attach($Db_object, $databasetype, $tableprefix, $parenttype, $contenttypeid, $attachmentid, $importattachmentid, intval($content['contentid']));

		return $attachmentid;
	}

	/**
	* aaa
	*/
	public function replace_attach_text($text, $attachmentid, $importattachmentid)
	{
		$search = array(
			'#\[attach(=right|=left|=config)?\](' . $importattachmentid . ')\[/attach\]#i'
		);

		$replace = array(
			'[ATTACH\\1]' . $attachmentid . '[/ATTACH]',
		);

		$text = preg_replace($search, $replace, $text);

		return $text;
	}

	/**
	* aaa
	*/
	public function update_content_attach(&$Db_object, &$databasetype, &$tableprefix, $parenttype, $contenttypeid, $attachmentid, $importattachmentid, $contentid)
	{
		if (!$contenttypeid OR !$attachmentid OR !$importattachmentid OR !$contentid)
		{
			return;
		}
		
		switch ($parenttype)
		{
			case 'post':
			{
				$post = $this->fetch_content_attach_post($Db_object, $databasetype, $tableprefix, $contentid);
				$pagetext = $this->replace_attach_text($post['pagetext'], $attachmentid, $importattachmentid);
				$this->write_content_attach_post($Db_object, $databasetype, $tableprefix, $contentid, $pagetext);
				break;
			}

			case 'cms':
			{
				$article = $this->fetch_content_attach_cms($Db_object, $databasetype, $tableprefix, $contentid, $contenttypeid);
				$pagetext = $this->replace_attach_text($article['pagetext'], $attachmentid, $importattachmentid);
				$previewtext = $this->replace_attach_text($article['pagetext'], $attachmentid, $importattachmentid);
				$this->write_content_attach_cms($Db_object, $databasetype, $tableprefix, $article['contentid'], $pagetext, $previewtext);
				break;
			}

			case 'blog':
			{
				$blog = $this->fetch_content_attach_blog($Db_object, $databasetype, $tableprefix, $contentid);
				$pagetext = $this->replace_attach_text($blog['pagetext'], $attachmentid, $importattachmentid);
				$this->write_content_attach_blog($Db_object, $databasetype, $tableprefix, $blog['blogtextid'], $pagetext);
				break;
			}
		}
	}

	/**
	* aaa
	*/
	private function write_content_attach_post(&$Db_object, &$databasetype, &$tableprefix, $postid, $pagetext)
	{
		$Db_object->query("
			UPDATE " . $tableprefix . "post SET
				pagetext = '" . $Db_object->escape_string($pagetext) . "'
			WHERE postid = " . intval($postid) . "
		");
	}

	/**
	* aaa
	*/
	private function fetch_content_attach_post(&$Db_object, &$databasetype, &$tableprefix, $postid)
	{
		$post = $Db_object->query_first("
			SELECT pagetext
			FROM " . $tableprefix . "post
			WHERE postid = " . intval($postid) . "
		");

		return $post;
	}

	/**
	* aaa
	*/
	private function write_content_attach_cms(&$Db_object, &$databasetype, &$tableprefix, $contentid, $pagetext, $previewtext)
	{
		$Db_object->query("
			UPDATE " . $tableprefix . "cms_article SET
				pagetext = '" . $Db_object->escape_string($pagetext) . "',
				previewtext = '" . $Db_object->escape_string($previewtext) . "'
			WHERE contentid = " . intval($contentid) . "
		");
	}

	/**
	* aaa
	*/
	private function fetch_content_attach_cms(&$Db_object, &$databasetype, &$tableprefix, $contentid, $contenttypeid)
	{
		$article = $Db_object->query_first("
			SELECT a.contentid, a.pagetext, a.previewtext
			FROM " . $tableprefix . "cms_node AS n
				INNER JOIN " . $tableprefix . "cms_article AS a ON (a.contentid = n.contentid)
			WHERE nodeid = " . intval($contentid) . "
		");

		return $article;
	}

	/**
	* aaa
	*/
	private function write_content_attach_blog(&$Db_object, &$databasetype, &$tableprefix, $blogtextid, $pagetext)
	{
		$Db_object->query("
			UPDATE " . $tableprefix . "blog_text SET
				pagetext = '" . $Db_object->escape_string($pagetext) . "'
			WHERE blogtextid = " . intval($blogtextid) . "
		");
	}

	/**
	* aaa
	*/
	private function fetch_content_attach_blog(&$Db_object, &$databasetype, &$tableprefix, $blogid)
	{
		$blog = $Db_object->query_first("
			SELECT bt.pagetext, bt.blogtextid
			FROM " . $tableprefix . "blog AS b
				INNER JOIN " . $tableprefix . "blog_text AS bt ON (b.firstblogtextid = bt.blogtextid)
			WHERE b.blogid = " . intval($blogid) . "
		");

		return $blog;
	}

	/**
	* Imports a usergroup
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	insert_id
	*/
	public function import_user_group($Db_object, $databasetype, $tableprefix)
	{
		// Check the dupe
		if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['usergroup'] === false))
		{
			$there = $Db_object->query_first("
				SELECT importusergroupid
				FROM " . $tableprefix . "usergroup
				WHERE importusergroupid = " . intval(trim($this->get_value('mandatory', 'importusergroupid'))) . "
			");

			if (is_numeric($there[0]))
			{
				return false;
			}
		}

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
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'title')) . "',
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'description')) . "',
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'usertitle')) . "',
				" . intval($this->get_value('nonmandatory', 'passwordexpires')) . ",
				" . intval($this->get_value('nonmandatory', 'passwordhistory')) . ",
				" . intval($this->get_value('nonmandatory', 'pmquota')) . ",
				" . intval($this->get_value('nonmandatory', 'pmsendmax')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'opentag')) . "',
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'closetag')) . "',
				" . intval($this->get_value('nonmandatory', 'canoverride')) . ",
				" . intval($this->get_value('nonmandatory', 'ispublicgroup')) . ",
				" . intval($this->get_value('nonmandatory', 'forumpermissions')) . ",
				" . intval($this->get_value('nonmandatory', 'pmpermissions')) . ",
				" . intval($this->get_value('nonmandatory', 'calendarpermissions')) . ",
				" . intval($this->get_value('nonmandatory', 'wolpermissions')). ",
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
	* Imports the current objects values as a PMtext and returns the insert_id
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	int	insert_id
	*/
	public function import_pm_text($Db_object, $databasetype, $tableprefix)
	{
		// Check the dupe
		if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['pmtext'] === false))
		{
			$there = $Db_object->query_first("
				SELECT pmtextid
				FROM " . $tableprefix . "pmtext
				WHERE importpmid = " . intval(trim($this->get_value('mandatory', 'importpmid'))) . "
			");

			if (is_numeric($there[0]))
			{
				return false;
			}
		}

		$Db_object->query("
			INSERT INTO " . $tableprefix . "pmtext
				(importpmid,
				fromuserid,
				title,
				message,
				touserarray,
				fromusername,
				iconid,
				dateline,
				showsignature,
				allowsmilie)
			VALUES
				(" . intval($this->get_value('mandatory', 'importpmid')) . ",
				" . intval($this->get_value('mandatory', 'fromuserid')) . ",
				'" . $Db_object->escape_string($this->get_value('mandatory', 'title')) . "',
				'" . $Db_object->escape_string($this->get_value('mandatory', 'message')) . "',
				" . intval($this->get_value('mandatory', 'touserarray')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'fromusername')) . "',
				" . intval($this->get_value('nonmandatory', 'iconid')) . ",
				" . intval($this->get_value('nonmandatory', 'dateline')) . ",
				" . intval($this->get_value('nonmandatory', 'showsignature')) . ",
				" . intval($this->get_value('nonmandatory', 'allowsmilie')) . ")
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
	* Imports the current objects values as a PM
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	public function import_pm($Db_object, $databasetype, $tableprefix)
	{
		// Check the dupe
		/*
		if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['pm'] === false))
		{
			$there = $Db_object->query_first("
				SELECT pmid
				FROM " . $tableprefix . "pm
				WHERE importpmid = " . intval(trim($this->get_value('mandatory', 'importpmid'))) . "
			");

			if (is_numeric($there[0]))
			{
				return false;
			}
		}
		*/

		if (!$this->get_value('mandatory', 'importpmid') OR $this->get_value('mandatory', 'importpmid') == '!##NULL##!')
		{
			$importpmid = 1;
		}
		else
		{
			$importpmid = $this->get_value('mandatory', 'importpmid');
		}

		if (!$this->get_value('mandatory', 'pmtextid'))
		{
			$importpmtextid = 0;
		}
		else
		{
			$importpmtextid = $this->get_value('mandatory', 'pmtextid');
		}

		if (!$this->get_value('mandatory', 'userid'))
		{
			$this->set_value('mandatory', 'userid', '0');
		}

		if (!$this->get_value('nonmandatory', 'folderid'))
		{
			$this->set_value('nonmandatory', 'folderid', '0');
		}

		if (!$this->get_value('nonmandatory', 'messageread'))
		{
			$this->set_value('nonmandatory', 'messageread', '0');
		}

		$Db_object->query("
			INSERT INTO " . $tableprefix . "pm
				(pmtextid, userid, folderid, messageread, importpmid)
			VALUES
				(" . intval($importpmtextid) . ",
				" . intval($this->get_value('mandatory', 'userid')) . ",
				" . intval($this->get_value('nonmandatory', 'folderid')) . ",
				" . intval($this->get_value('nonmandatory', 'messageread')) . ",
				" . intval($importpmid) . ")
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
	public function import_vb3_avatar($Db_object, $databasetype, $tableprefix)
	{
		// Check the dupe
		if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['avatar'] === false))
		{
			$there = $Db_object->query_first("
				SELECT importavatarid
				FROM " . $tableprefix . "avatar
				WHERE importavatarid = " . intval(trim($this->get_value('mandatory', 'importavatarid'))) . "
			");

			if (is_numeric($there[0]))
			{
				return false;
			}
		}

		$Db_object->query("
			INSERT INTO " . $tableprefix . "avatar
				(importavatarid, title, minimumposts, avatarpath, imagecategoryid, displayorder)
			VALUES
				(" . intval($this->get_value('mandatory', 'importavatarid')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'title')) . "',
				" . intval($this->get_value('nonmandatory', 'minimumposts')) . ",
				" . intval($this->get_value('nonmandatory', 'avatarpath')) . ",
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
		// Check the dupe
		if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['customavatar'] === false))
		{
			$there = $Db_object->query_first("
				SELECT importcustomavatarid
				FROM " . $tableprefix . "customavatar
				WHERE importcustomavatarid = " . intval(trim($this->get_value('mandatory', 'importcustomavatarid'))) . "
			");

			if (is_numeric($there[0]))
			{
				return false;
			}
		}

		if (!$width = $this->get_value('nonmandatory', 'width'))
		{
			$width = 80;
		}

		if (!$height = $this->get_value('nonmandatory', 'height'))
		{
			$height = 80;
		}

		if (!$file_sz = $this->get_value('nonmandatory', 'filesize'))
		{
			if (!$file_sz = @filesize($this->get_value('nonmandatory', 'filedata')))
			{
				$file_sz = 0;
			}
		}

		if ($Db_object->query("
			REPLACE INTO " . $tableprefix . "customavatar
				(importcustomavatarid, userid, filedata, dateline, filename, visible, filesize, width, height)
			VALUES
				(" . intval($this->get_value('mandatory', 'importcustomavatarid')) . ",
				" . intval($this->get_value('nonmandatory', 'userid')) . ",
				" . intval($this->get_value('nonmandatory', 'filedata')) . ",
				" . intval($this->get_value('nonmandatory', 'dateline')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'filename')) . "',
				" . intval($this->get_value('nonmandatory', 'visible')) . ",
				" . intval($file_sz) . ",
				" . intval($width) . ",
				" . intval($height) . ")
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
	* Imports the current objects values as a buddy or ignore value, needs an array of :
	* $user('userid' => 'vbuserid'
	*		'buddylist' => space delimited buddy ids
	*		'ignorelist' => space delimited ignore ids
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	public function import_buddy_ignore($Db_object, $databasetype, $tableprefix, $user)
	{
		if (!$user['userid'])
		{
			return false;
		}

		if ($Db_object->query_first("
			SELECT userid
			FROM " . $tableprefix . "usertextfield
			WHERE userid = " . intval($user['userid']) . "
		"))
		{
			// The user is there
		}
		else
		{
			$Db_object->query("
				INSERT INTO " . $tableprefix . "usertextfield
					(userid)
				VALUES
					(" . intval($user['userid']) . ")
			");

			if ($Db_object->affected_rows())
			{
				// It went in
			}
			else
			{
				return false;
			}
		}

		$sql = array();

		// add to buddy list
		if ($user['buddylist'] != '')
		{
			$sql[] = "buddylist = IF(buddylist IS NULL, LTRIM('" . $Db_object->escape_string($user['buddylist']) . "'),CONCAT(buddylist, ' " . $Db_object->escape_string($user['buddylist']) . "'))";
		}

		// add to ignore list
		if ($user['ignorelist'] != '')
		{
			$sql[] = "ignorelist = IF(ignorelist IS NULL, LTRIM('" . $Db_object->escape_string($user['ignorelist']) . "'), CONCAT(ignorelist, ' " . $Db_object->escape_string($user['ignorelist']) . "'))";
		}

		if (!empty($sql))
		{
			$Db_object->query("
				UPDATE " . $tableprefix . "usertextfield SET
					" . implode(', ', $sql) . "
				WHERE userid = " . intval($user['userid']) . "
			");

			return ($Db_object->affected_rows() > 0);
		}
		else
		{
			return true; // They were adding blank lists to a users, 0+0=0 == true;
		}
	}

	/**
	* Imports the an arrary as a ban list in various formats $key => $value, $int => $data
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

		$sql = '';
		$internal_list = '';

		switch ($type)
		{
			case 'emaillist':
			{
				foreach ($list AS $key => $data)
				{
					$internal_list .= $data . " ";
				}

				// For datastore opposed to setting table if it ever gets used
				$Db_object->query("
					UPDATE " . $tableprefix . "settings SET
						value = CONCAT(value, ' " . $Db_object->escape_string($list) . "')
					WHERE varname = 'banemail'"
				);

				$Db_object->query("
					UPDATE " . $tableprefix . "datastore SET
						data = CONCAT(data, '" . $Db_object->escape_string($internal_list) . "')
					WHERE title = 'banemail'
				");

				$affected_rows = $Db_object->affected_rows();

				if ($affected_rows)
				{
					return $affected_rows;
				}
				else
				{
					return false;
				}
			}
			break;

			case 'iplist':
			{
				$list = implode(' ', $list);

				if ($list)
				{
					$current = $Db_object->query_first("
						SELECT value
						FROM " . $tableprefix . "setting
						WHERE varname = 'banip'
					");

					$new_list = $current['value'] . $list;

					$Db_object->query("
						UPDATE " . $tableprefix . "setting SET
							value = '" . $Db_object->escape_string($new_list) . "'
							WHERE varname = 'banip'
					");

					$affected_rows = $Db_object->affected_rows();

					if ($affected_rows)
					{
						return $affected_rows;
					}
					else
					{
						return false;
					}
				}
			}
			break;

			case 'namebansfull':
			{
				$user_id_list = array();
				foreach ($list as $key => $vb_user_name)
				{
					if (is_string($vb_user_name))
					{
						$banned_userid = $Db_object->query_first("
							SELECT userid
							FROM " . $tableprefix . "user
							WHERE username = '" . $Db_object->escape_string($vb_user_name) . "'
						");

						$user_id_list[] = $banned_userid['userid'];
					}
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
					foreach($list AS $key => $banned_user_id)
					{
						if (is_numeric($banned_user_id))
						{
							$Db_object->query("
								UPDATE " . $tableprefix . "user SET
									usergroupid = " . intval($banned_group_id['usergroupid']) . "
								WHERE userid = " . intval($banned_user_id) . "
							");
						}
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
	}

	/**
	* Imports the current objects values as a Post
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	*
	* @return	integer					The post ID.
	*/
	public function import_post($Db_object, $databasetype, $tableprefix)
	{
		// Check the dupe
		if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['post'] === false))
		{
			$there = $Db_object->query_first("
				SELECT postid
				FROM " . $tableprefix . "post
				WHERE importpostid = " . intval(trim($this->get_value('nonmandatory', 'importpostid'))) . "
					AND importthreadid = " . intval(trim($this->get_value('mandatory', 'importthreadid'))) . "
			");

			if (is_numeric($there[0]))
			{
				return false;
			}
		}

		$Db_object->query("
			INSERT INTO " . $tableprefix . "post
				(threadid,
				userid,
				importthreadid,
				parentid,
				username,
				title,
				dateline,
				pagetext,
				allowsmilie,
				showsignature,
				ipaddress,
				iconid,
				visible,
				attach,
				importpostid)
			VALUES
				(" . intval($this->get_value('mandatory', 'threadid')) . ",
				" . intval($this->get_value('mandatory', 'userid')) . ",
				" . intval($this->get_value('mandatory', 'importthreadid')) . ",
				" . intval($this->get_value('nonmandatory', 'parentid')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'username')) . "',
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'title')) . "',
				" . intval($this->get_value('nonmandatory', 'dateline')) . ",
				'".  $Db_object->escape_string($this->get_value('nonmandatory', 'pagetext')) . "',
				" . intval($this->get_value('nonmandatory', 'allowsmilie')) . ",
				" . intval($this->get_value('nonmandatory', 'showsignature')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'ipaddress')) . "',
				" . intval($this->get_value('nonmandatory', 'iconid')) . ",
				" . intval($this->get_value('nonmandatory', 'visible')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'attach')) . "',
				" . intval($this->get_value('nonmandatory', 'importpostid')) . ")
		");

		$post_id = $Db_object->insert_id();

		return $post_id;
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
		// Check the dupe
		if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['user'] === false))
		{
			$there = $Db_object->query_first("
				SELECT importuserid
				FROM " . $tableprefix . "user
				WHERE importuserid = " . intval(trim($this->get_value('mandatory', 'importuserid'))) . "
			");

			if (is_numeric($there[0]))
			{
				return false;
			}
		}

		// Auto email associate
		if ($this->_auto_email_associate)
		{
			// Do a search for the email address to find the user to match this imported one to :
			if (emailcasesensitive)
			{
				$email_match = $Db_object->query_first("
					SELECT userid
					FROM " . $tableprefix . "user
					WHERE email = '". $Db_object->escape_string($this->get_value('mandatory', 'email')) . "'
				");
			}
			else
			{
				$email_match = $Db_object->query_first("
					SELECT userid
					FROM " . $tableprefix . "user
					WHERE UPPER(email) = '" . strtoupper($Db_object->escape_string($this->get_value('mandatory', 'email'))) . "'
				");
			}

			if ($email_match)
			{

				if ($this->associate_user($Db_object, $databasetype, $tableprefix, $this->get_value('mandatory', 'importuserid'), $email_match["userid"]))
				{
					// We matched the email address and associated propperly
					$result['automerge'] = true;
					$result['userid'] = $email_match['userid'];
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

		// If there is a dupe username pre_pend "imported_" have escape_
		$name = $this->get_value('mandatory', 'username');

		$do_me = array("_" => "\_");
		$name = str_replace(array_keys($do_me), $do_me, $name);

		$double_name = $Db_object->query("
			SELECT username
			FROM " . $tableprefix . "user
			WHERE username = '". $Db_object->escape_string($name) . "'
		");

		if ($Db_object->num_rows($double_name))
		{
			$this->set_value('mandatory', 'username', 'imported_' . $this->get_value('mandatory', 'username'));
		}

		$sql = "
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
				autosubscribe, profilepicrevision)
			VALUES
				('" . $Db_object->escape_string(htmlspecialchars($this->get_value('mandatory', 'username'))) . "',
				'" . $Db_object->escape_string($this->get_value('mandatory', 'email')) . "',
				" . intval($this->get_value('mandatory', 'usergroupid')) . ",
				" . intval($this->get_value('mandatory', 'importuserid')) . ",
				'" . $Db_object->escape_string($newpassword) . "',
				'" . $Db_object->escape_string($salt) . "',
				NOW(),
				" . intval($this->get_value('nonmandatory', 'options')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'homepage')) . "',
				" . intval($this->get_value('nonmandatory', 'posts')) . ",
				" . intval($this->get_value('nonmandatory', 'joindate')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'icq')) . "',
				" . intval($this->get_value('nonmandatory', 'daysprune')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'aim')) . "',
				" . intval($this->get_value('nonmandatory', 'membergroupids')) . ",
				" . intval($this->get_value('nonmandatory', 'displaygroupid')) . ",
				" . intval($this->get_value('nonmandatory', 'styleid')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'parentemail')) . "',
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'yahoo')) . "',
				" . intval($this->get_value('nonmandatory', 'showvbcode')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'usertitle')) . "',
				" . intval($this->get_value('nonmandatory', 'customtitle')) . ",
				" . intval($this->get_value('nonmandatory', 'lastvisit')) . ",
				" . intval($this->get_value('nonmandatory', 'lastactivity')) . ",
				" . intval($this->get_value('nonmandatory', 'lastpost')) . ",
				" . intval($this->get_value('nonmandatory', 'reputation')) . ",
				" . intval($this->get_value('nonmandatory', 'reputationlevelid')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'timezoneoffset')) . "',
				" . intval($this->get_value('nonmandatory', 'pmpopup')) . ",
				" . intval($this->get_value('nonmandatory', 'avatarid')) . ",
				" . intval($this->get_value('nonmandatory', 'avatarrevision')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'birthday')) . "',
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'birthday_search')) . "',
				" . intval($this->get_value('nonmandatory', 'maxposts')) . ",
				" . intval($this->get_value('nonmandatory', 'startofweek')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'ipaddress')) . "',
				" . intval($this->get_value('nonmandatory', 'referrerid')) . ",
				" . intval($this->get_value('nonmandatory', 'languageid')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'msn')) . "',
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'emailstamp')) . "',
				" . intval($this->get_value('nonmandatory', 'threadedmode')) . ",
				" . intval($this->get_value('nonmandatory', 'pmtotal')) . ",
				" . intval($this->get_value('nonmandatory', 'pmunread')) . ",
				" . intval($this->get_value('nonmandatory', 'autosubscribe')) . ",
				" . intval($this->get_value('nonmandatory', 'profilepicrevision')) . ")
		";

		$userdone = $Db_object->query($sql);
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
						$this->import_user_field_value($Db_object, $databasetype, $tableprefix, $key, $value, $userid);
						// TODO: Don't fail the whole user just record an error in the dB here
						#$this->_failedon = "import_user_field_value - $key - $value - $userid";
						#return false;
					}
				}
			}

			if (array_key_exists('signature',$this->_default_values))
			{
				if (!$Db_object->query("
					UPDATE " . $tableprefix . "usertextfield SET
						signature = '" . $Db_object->escape_string($this->_default_values['signature']) . "'
					WHERE userid = " . intval($userid) ."
				"))
				{
					$this->_failedon = "usertextfield SET signature";
					return false;
				}
			}

			if ($this->get_value('nonmandatory', 'usernote') != NULL)
			{
				$Db_object->query("
					INSERT INTO	" . $tableprefix . "usernote
						(userid, posterid, username, dateline, message, title, allowsmilies, importusernoteid)
					VALUES
						(" . intval($userid) . ", 0, '', " . time() . ", '" . $Db_object->escape_string($this->get_value('nonmandatory', 'usernote')) . "', 'Imported Note', 0, 1)
					");
			}
		}
		else
		{
			return false;
		}

		return $userid;
	}

	/**
	* Generates a new user salt string
	*
	* @param	integer	(Optional) the length of the salt string to generate
	*
	* @return	string
	*/
	public function fetch_user_salt($length = 3)
	{
		$salt = '';
		for ($i = 0; $i < $length; $i++)
		{
			$salt .= chr(rand(32, 126));
		}
		return $salt;
	}

	/**
	* Imports the users avatar from a local file or URL.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	string	int		The userid
	* @param	string	int		The location of the avatar file
	*
	* @return	insert_id
	*/
	public function import_avatar($Db_object, $databasetype, $tableprefix, $userid, $file)
	{
		if ($filenum = @fopen($file, 'r'))
		{
			$contents = $this->vb_file_get_contents($file);

			$size = getimagesize($file);

			if ($size)
			{
				$width 	= $size[0];
				$height = $size[1];
			}
			else
			{
				$width 	= '80';
				$height = '80';
			}

			if (!$file_sz = @filesize($file))
			{
				$file_sz = 0;
			}

			$urlbits = parse_url($file);
			$pathbits = pathinfo($urlbits['path']);

			$avatarid = $Db_object->query("
				INSERT INTO " . $tableprefix . "customavatar
					(userid, filedata, dateline, filename, filesize, width, height, importcustomavatarid)
				VALUES
					(" . intval($userid) . ",
					'" . $Db_object->escape_string($contents) . "',
					NOW(),
					'" . $Db_object->escape_string($pathbits['basename']) . "',
					" . intval($file_sz) . ",
					" . intval($width) . ",
					" . intval($height) . ",
					'1')
			");

			if ($Db_object->affected_rows())
			{
				return $avatarid;
			}
		}
		return false;
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
		// Check the dupe
		if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['forum'] === false))
		{
			$there = $Db_object->query_first("
				SELECT importcategoryid
				FROM " . $tableprefix . "forum
				WHERE importcategoryid = " . intval(trim($this->get_value('mandatory', 'importcategoryid'))) . "
			");

			if (is_numeric($there[0]))
			{
				return false;
			}
		}

		// Catch the legacy importers that haven't been updated
		if ($this->get_value('mandatory', 'options') == '!##NULL##!')
		{
			$this->set_value('mandatory', 'options', $this->_default_cat_permissions);
		}

		$result = $Db_object->query("
			INSERT INTO " . $tableprefix . "forum
				(styleid,
				title,
				description,
				options,
				daysprune,
				displayorder,
				parentid,
				importforumid,
				importcategoryid,
				title_clean,
				description_clean)
			VALUES
				(" . intval($this->get_value('mandatory', 'styleid')) . ",
				'" . $Db_object->escape_string($this->get_value('mandatory', 'title')) . "',
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'description')) . "',
				" . intval($this->get_value('mandatory', 'options')) . ",
				'30',
				" . intval($this->get_value('mandatory', 'displayorder')) . ",
				" . intval($this->get_value('mandatory', 'parentid')) . ",
				" . intval($this->get_value('mandatory', 'importforumid')) . ",
				" . intval($this->get_value('mandatory', 'importcategoryid')) . ",
				'" . $Db_object->escape_string($this->get_value('mandatory', 'title')) . "',
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'description')) . "')
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
		// Check the dupe
		if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['forum'] === false))
		{
			$there = $Db_object->query_first("
				SELECT importforumid
				FROM " . $tableprefix . "forum
				WHERE importforumid = " . intval(trim($this->get_value('mandatory', 'importforumid'))) . "
			");

			if (is_numeric($there[0]))
			{
				return false;
			}
		}

		// Catch the legacy importers that haven't been
		// updated
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
				title_clean, description_clean,
				showprivate, lastpostid, defaultsortfield,
				defaultsortorder)
			VALUES
				(" . intval($this->get_value('mandatory', 'styleid')) . ",
				'" . $Db_object->escape_string($this->get_value('mandatory', 'title')) . "',
				" . intval($this->get_value('mandatory', 'options')) . ",
				" . intval($this->get_value('mandatory', 'displayorder')) . ",
				" . intval($this->get_value('mandatory', 'parentid')) . ",
				" . intval($this->get_value('mandatory', 'importforumid')) . ",
				" . intval($this->get_value('mandatory', 'importcategoryid')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'description')) . "',
				" . intval($this->get_value('nonmandatory', 'replycount')) . ",
				" . intval($this->get_value('nonmandatory', 'lastpost')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'lastposter')) . "',
				" . intval($this->get_value('nonmandatory', 'lastthread')) . ",
				" . intval($this->get_value('nonmandatory', 'lastthreadid')) . ",
				" . intval($this->get_value('nonmandatory', 'lasticonid')) . ",
				" . intval($this->get_value('nonmandatory', 'threadcount')) . ",
				" . intval($this->get_value('nonmandatory', 'daysprune')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'newpostemail')) . "',
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'newthreademail')) . "',
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'parentlist')) . "',
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'password')) . "',
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'link')) . "',
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'childlist')) . "',
				'" . $Db_object->escape_string(htmlspecialchars(strip_tags($this->get_value('mandatory', 'title')), false)) . "',
				'" . $Db_object->escape_string(htmlspecialchars(strip_tags($this->get_value('nonmandatory', 'description')), false)) . "',
				" . intval($this->get_value('nonmandatory', 'showprivate')) . ",
				" . intval($this->get_value('nonmandatory', 'lastpostid')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'defaultsortfield')) . "',
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'defaultsortorder')) . "')
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
		// Check the dupe
		if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['forum'] === false))
		{
			$there = $Db_object->query_first("
				SELECT importforumid
				FROM " . $tableprefix . "forum
				WHERE importforumid = " . intval(trim($this->get_value('mandatory', 'importforumid'))) . "
			");

			if (is_numeric($there[0]))
			{
				return false;
			}
		}

		$Db_object->query("
			INSERT INTO " . $tableprefix . "forum
				(styleid, title, options,
				displayorder, parentid, importforumid,
				description, replycount,
				lastpost, lastposter, lastthread,
				lastthreadid, lasticonid, threadcount,
				daysprune, newpostemail, newthreademail,
				parentlist, password, link, childlist)
			VALUES
				('" . $this->get_value('mandatory', 'styleid') . "',
				'" . $Db_object->escape_string($this->get_value('mandatory', 'title')) . "',
				'" . $this->get_value('mandatory', 'options') . "',
				'" . $this->get_value('mandatory', 'displayorder') . "',
				'" . $this->get_value('mandatory', 'parentid') . "',
				'" . $this->get_value('mandatory', 'importforumid') . "',
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'description')) . "',
				'" . $this->get_value('nonmandatory', 'replycount') . "',
				'" . $this->get_value('nonmandatory', 'lastpost') . "',
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'lastposter')) . "',
				'" . $this->get_value('nonmandatory', 'lastthread') . "',
				'" . $this->get_value('nonmandatory', 'lastthreadid') . "',
				'" . $this->get_value('nonmandatory', 'lasticonid') . "',
				'" . $this->get_value('nonmandatory', 'threadcount') . "',
				'" . $this->get_value('nonmandatory', 'daysprune') . "',
				'" . $this->get_value('nonmandatory', 'newpostemail') . "',
				'" . $this->get_value('nonmandatory', 'newthreademail') . "',
				'" . $this->get_value('nonmandatory', 'parentlist') . "',
				'" . $this->get_value('nonmandatory', 'password') . "',
				'" . $this->get_value('nonmandatory', 'link') . "',
				'" . $this->get_value('nonmandatory', 'childlist') . "')
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
	* Imports the current objects values as a Thread
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	public function import_thread($Db_object, $databasetype, $tableprefix)
	{
		// Check the dupe
		if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['thread'] === false))
		{
			$there = $Db_object->query_first("
			SELECT importthreadid
			FROM " . $tableprefix . "thread
			WHERE importthreadid = " . intval(trim($this->get_value('mandatory', 'importthreadid'))) . "
				AND importforumid = " . intval(trim($this->get_value('mandatory', 'importforumid'))) . "
			");

			if (is_numeric($there[0]))
			{
				return false;
			}
		}

		$Db_object->query("
			INSERT INTO " . $tableprefix . "thread
				(forumid, title, importforumid,
				importthreadid, firstpostid, lastpost,
				pollid, open, replycount,
				postusername, postuserid, lastposter,
				dateline, views, iconid,
				notes, visible, sticky,
				votenum, votetotal, attach, similar,
				hiddencount, deletedcount)
			VALUES
				(" . intval($this->get_value('mandatory', 'forumid')) . ",
				'" . $Db_object->escape_string($this->get_value('mandatory', 'title')) . "',
				" . intval($this->get_value('mandatory', 'importforumid')) . ",
				" . intval($this->get_value('mandatory', 'importthreadid')) . ",
				" . intval($this->get_value('nonmandatory', 'firstpostid')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'lastpost')) . "',
				" . intval($this->get_value('nonmandatory', 'pollid')) . ",
				" . intval($this->get_value('nonmandatory', 'open'))  . ",
				" . intval($this->get_value('nonmandatory', 'replycount')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'postusername')) . "',
				" . intval($this->get_value('nonmandatory', 'postuserid')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'lastposter')) . "',
				" . intval($this->get_value('nonmandatory', 'dateline')) . ",
				" . intval($this->get_value('nonmandatory', 'views')) . ",
				" . intval($this->get_value('nonmandatory', 'iconid')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'notes')) . "',
				" . intval($this->get_value('nonmandatory', 'visible')) . ",
				" . intval($this->get_value('nonmandatory', 'sticky')) . ",
				" . intval($this->get_value('nonmandatory', 'votenum')) . ",
				" . intval($this->get_value('nonmandatory', 'votetotal')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'attach')) . "',
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'similar')) . "',
				" . intval($this->get_value('nonmandatory', 'hiddencount')) . ",
				" . intval($this->get_value('deletedcount', 'hiddencount')) . ")
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
	* Imports the current objects values as a Moderator
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	public function import_moderator($Db_object, $databasetype, $tableprefix)
	{
		// Check the dupe
		if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['moderator'] === false))
		{
			$there = $Db_object->query_first("
				SELECT importmoderatorid
				FROM " . $tableprefix . "moderator
				WHERE importmoderatorid = " . intval(trim($this->get_value('mandatory', 'importmoderatorid'))) . "
			");

			if (is_numeric($there[0]))
			{
				return false;
			}
		}

		$Db_object->query("
			REPLACE INTO " . $tableprefix . "moderator
				(userid, forumid, importmoderatorid, permissions, permissions2)
			VALUES
				(" . intval($this->get_value('mandatory', 'userid')) . ",
				" . intval($this->get_value('mandatory', 'forumid')) . ",
				" . intval($this->get_value('mandatory', 'importmoderatorid')) . ",
				" . intval($this->get_value('nonmandatory', 'permissions')) . ",
				" . intval($this->get_value('nonmandatory', 'permissions2')) . ")
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
		// Check the dupe
		if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['customprofilepic'] === false))
		{
			$there = $Db_object->query_first("
				SELECT importcustomprofilepicid
				FROM " . $tableprefix . "customprofilepic
				WHERE importcustomprofilepicid = " . intval(trim($this->get_value('mandatory', 'importcustomprofilepicid'))) . "
			");

			if (is_numeric($there[0]))
			{
				return false;
			}
		}

		$size = @getimagesize($this->get_value('nonmandatory', 'filedata'));

		if ($size)
		{
			$width 	= $size[0];
			$height = $size[1];
		}
		else
		{
			$width 	= '0';
			$height = '0';
		}

		if (!$file_sz = strlen($this->get_value('nonmandatory', 'filedata')))
		{
			$file_sz = 0;
		}

		$sql = $Db_object->query("
			INSERT INTO " . $tableprefix . "customprofilepic
				(importcustomprofilepicid, userid, filedata, dateline, filename, visible, filesize, height, width)
			VALUES
				(" . intval($this->get_value('mandatory', 'importcustomprofilepicid')) . ",
				" . intval($this->get_value('nonmandatory', 'userid')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'filedata')) . "',
				" . intval($this->get_value('nonmandatory', 'dateline')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'filename')) . "',
				" . intval($this->get_value('nonmandatory', 'visible')) . ",
				" . intval($file_sz) . ",
				" . intval($height) . ",
				" . intval($width)  .")
		");

		if ($Db_object->query($sql))
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
		// Having to hard code for 3.6.0 because of database phrasing
		switch(trim(strtolower($title)))
		{
			case 'occupation':
			{
				$fieldid = 4;
				break;
			}

			case 'interests':
			{
				$fieldid = 3;
				break;
			}

			case 'location':
			{
				$fieldid = 2;
				break;
			}

			case 'biography':
			{
				$fieldid = 1;
				break;
			}

			default:
			{
				$id = $Db_object->query_first("
					SELECT varname
					FROM " . $tableprefix . "phrase
					WHERE text LIKE '" . $title . "'
				");

				$fieldid = substr($id['varname'], 5, strpos($id['varname'], '_') - 5);
			}
		}

		if (is_numeric($fieldid))
		{
			if ($this->check_user_field($Db_object, $databasetype, $tableprefix, "field{$fieldid}"))
			{
				$Db_object->query("
					UPDATE " . $tableprefix . "userfield SET
						field" . intval($fieldid) . " = '" . $Db_object->escape_string($value) . "'
					WHERE userid = " . intval($userid) . "
				");
				return true;
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
	* Imports a rank, has to be used incombination with import usergroup to make sense get its usergroupid
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed		The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed		The prefix to the table name i.e. 'vb3_'
	*
	* @return	false/int		The tablerow inc id
	*/
	public function import_rank($Db_object, $databasetype, $tableprefix)
	{
		if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['ranks'] === false))
		{
			$there = $Db_object->query_first("
				SELECT importrankid
				FROM " . $tableprefix . "ranks
				WHERE importrankid = " . intval(trim($this->get_value('mandatory', 'importrankid'))) . "
			");

			if (is_numeric($there[0]))
			{
				return false;
			}
		}

		$Db_object->query("
			INSERT INTO " . $tableprefix . "ranks
				(importrankid, minposts, ranklevel, rankimg, usergroupid, type, stack, display)
			VALUES
				(" . intval($this->get_value('mandatory', 'importrankid')) . ",
				" . intval($this->get_value('nonmandatory', 'minposts')) . ",
				" . intval($this->get_value('nonmandatory', 'ranklevel')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'rankimg')) . "',
				0,
				" . intval($this->get_value('nonmandatory', 'type')) . ",
				" . intval($this->get_value('nonmandatory', 'stack')) . ",
				" . intval($this->get_value('nonmandatory', 'display')) . ")
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
	public function import_smilie_image_group($Db_object, $databasetype, $tableprefix)
	{
		$row_id = $Db_object->query_first("
			SELECT imagecategoryid
			FROM ". $tableprefix . "imagecategory
			WHERE title = 'Imported Smilies'
		");

		if (!$row_id)
		{
			$Db_object->query("
				INSERT INTO " . $tableprefix . "imagecategory
					(title, imagetype, displayorder)
				VALUES
					('" . $Db_object->escape_string($this->get_value('nonmandatory', 'title')) . "',
					" . intval($this->get_value('nonmandatory', 'imagetype')) . ",
					" . intval($this->get_value('nonmandatory', 'displayorder')) . ")
			");
			return $Db_object->insert_id();
		}
		else
		{
			return $row_id[0];
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
		$this->is_valid();

		if (dupe_checking AND !($this->_dupe_checking === false OR $this->_dupe_checking['usergroup'] === false))
		{
			$there = $Db_object->query_first("
				SELECT importusergroupid
				FROM " . $tableprefix . "usergroup
				WHERE importusergroupid = " . intval(trim($this->get_value('mandatory', 'importusergroupid'))) . "
			");

			if (is_numeric($there[0]))
			{
				return false;
			}
		}

		$Db_object->query("
			INSERT INTO " . $tableprefix ."usergroup
				(importusergroupid, title, description,
				usertitle, passwordexpires, passwordhistory,
				pmquota, pmsendmax,
				opentag, closetag, canoverride,
				ispublicgroup, forumpermissions, pmpermissions,
				calendarpermissions, wolpermissions, adminpermissions,
				genericpermissions, genericoptions, attachlimit,
				avatarmaxwidth, avatarmaxheight, avatarmaxsize,
				profilepicmaxwidth, profilepicmaxheight, profilepicmaxsize,
				signaturepermissions, sigpicmaxwidth, sigpicmaxheight,
				sigpicmaxsize, sigmaximages, sigmaxsizebbcode, sigmaxchars,
				sigmaxrawchars, sigmaxlines)
			VALUES
				(" . intval($this->get_value('mandatory', 'importusergroupid')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'title')) . "',
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'description')) . "',
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'usertitle')) . "',
				0,
				" . intval($this->get_value('nonmandatory', 'passwordhistory')) . ",
				" . intval($this->get_value('nonmandatory', 'pmquota')) . ",
				" . intval($this->get_value('nonmandatory', 'pmsendmax')) . ",
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'opentag')) . "',
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'closetag')) . "',
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
				" . intval($this->get_value('nonmandatory', 'profilepicmaxsize')) . ",
				" . intval($this->get_value('nonmandatory', 'signaturepermissions')) . ",
				" . intval($this->get_value('nonmandatory', 'sigpicmaxwidth')) . ",
				" . intval($this->get_value('nonmandatory', 'sigpicmaxheight')) . ",
				" . intval($this->get_value('nonmandatory', 'sigpicmaxsize')) . ",
				" . intval($this->get_value('nonmandatory', 'sigmaximages')) . ",
				" . intval($this->get_value('nonmandatory', 'sigmaxsizebbcode')) . ",
				" . intval($this->get_value('nonmandatory', 'sigmaxchars')) . ",
				" . intval($this->get_value('nonmandatory', 'sigmaxrawchars')) . ",
				" . intval($this->get_value('nonmandatory', 'sigmaxlines')) . ")
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
	* Returns the id => * array
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

		$is_table = $this->check_table($Db_object, $databasetype, $tableprefix, $type, $displayobject);

		// Check that there isn't a empty value
		if (empty($per_page) OR !$is_table)
		{
			return $return_array;
		}

		/*if (!$orderby)
		{
			$sql = "SELECT * FROM " . $tableprefix . $type;
		}
		else
		{
			$sql = "SELECT * FROM " . $tableprefix . $type . " ORDER BY " . $orderby;
		}*/

		if ($per_page != -1)
		{
			$sql .= " LIMIT " . $start . "," . $per_page;
		}

		$details_list = $Db_object->query("
			SELECT * FROM " . $tableprefix . $type . "
			" . ($orderby ? " ORDER BY " . $orderby : '') . "
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

	/**
	* Returns the source data.
	*
	* @param	object	databaseobject	The database object to run the query against.
	* @param	string	mixed			Table database type.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	*/
	public function get_source_data($Db_object, $databasetype, $tablename, $id_field, $fields, $start_at, $per_page, $displayobject)
	{
		// Set up
		$return_array 			= array();
		$return_array['data'] 	= array();
		$return_array['lastid'] = null;
		$return_array['error']	= null;
		$return_array['time']	= null;
		$return_array['count']	= null;
		$id						= 0;
		$sql					= '';
		$time_start				= 0;

		if (phpversion() >= '5')
		{
			$time_start = microtime(true);
		}

		// Check that there is not a empty value
		if (empty($per_page))
		{
			$return_array['error'] = 'per_page empty';
			return $return_array;
		}

		// Specific fields (array and one or more) or a * select
		if (is_array($fields) AND count($fields) > 0)
		{
			foreach ($fields AS $field)
			{
				$fields .= "{$field},";
			}

			// Remove the final comma
			$fields = substr($fields, -1);
		}
		else // It's a * select
		{
			$fields = '*';
		}

		// Table name case
		if (lowercase_table_names)
		{
			$tablename = strtolower($tablename);
		}

		// Table check need to use cache though
		$tableprefix = '';

		if (!$this->check_table($Db_object, $databasetype, $tableprefix, $tablename, $displayobject))
		{
			// Not there or bad table name
			$return_array['error'] = 'table check failed';
			return $return_array;
		}

		// Build the SQL
		$result_set = $Db_object->query("
			SELECT " . $fields  ."
			FROM " . $tablename  ."
			WHERE " . $id_field  ." > " . $start_at  ."
				ORDER BY " . $id_field  ."
				LIMIT " . $per_page  ."
		");

		// Do it and build the array
		while ($row = $Db_object->fetch_array($result_set))
		{
			$id = $row["$id_field"];
			$return_array['data'][$id] = $row;
		}

		if (phpversion() >= '5')
		{
			$return_array['time'] = microtime(true) - $time_start;
		}

		$return_array['lastid'] = $id;

		unset($result_set);

		// Set the count
		$return_array['count'] = count($return_array['data']);

		// Return it
		return $return_array;
	}

	/**
	* Returns the parent postid.
	*
	* @param	object	databaseobject	The database object to run the query against.
	* @param	string	mixed			Table database type.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	*/
	public function get_post_parent_id($Db_object, $databasetype, $tableprefix, $import_post_id)
	{
		$post_id = $Db_object->query_first("
			SELECT postid
			FROM " . $tableprefix . "post
			WHERE importpostid = " . intval($import_post_id) . "
		");

		return $post_id[0];
	}

	/**
	* Returns the PM folderid.
	*
	* @param	object	databaseobject	The database object to run the query against.
	* @param	string	mixed			Table database type.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	*/
	public function get_custom_pm_folder_id($Db_object, $databasetype, $tableprefix, $userid, $folder_name)
	{
		if (!$folder_name)
		{
			return false;
		}

		// Get all the user ids
		$current_pm_folders = $Db_object->query_first("
			SELECT pmfolders
			FROM " . $tableprefix . "usertextfield
			WHERE userid = " . intval($userid) . "
		");

		$current_pm_folders =  unserialize($current_pm_folders['pmfolders']);

		if (is_array($current_pm_folders))
		{
			foreach ($current_pm_folders AS $id => $folder)
			{
				if ($folder_name == $folder)
				{
					return $id;
				}
			}
		}

		return false;
	}

	/**
	* Returns the options setting.
	*
	* @param	object	databaseobject	The database object to run the query against.
	* @param	string	mixed			Table database type.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	*/
	public function get_options_setting($Db_object, $databasetype, $tableprefix, $name)
	{
		$options_array = array();

		$options_return = $Db_object->query_first("
			SELECT data
			FROM " . $tableprefix ."datastore
			WHERE title = 'options'
		");

		$options_array = unserialize($options_return['data']);

		return $options_array[$name];
	}

	/**
	* Returns the post userid.
	*
	* @param	object	databaseobject	The database object to run the query against.
	* @param	string	mixed			Table database type.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	*/
	public function get_vb_post_user_id($Db_object, $databasetype, $tableprefix, $post_id)
	{
		$details_list = array();

		// Check that there is not a empty value
		if (empty($post_id))
		{
			return 0;
		}

		$details_list = $Db_object->query_first("
			SELECT userid
			FROM " . $tableprefix . "post
			WHERE postid = " . intval($post_id) . "
		");

		return $details_list['userid'];
	}

	/**
	* Returns the profilefield list.
	*
	* @param	object	databaseobject	The database object to run the query against.
	* @param	string	mixed			Table database type.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	*/
	public function select_profilefield_list($Db_object, $databasetype, $tableprefix, $title)
	{
		$return_array = array();

		switch($title)
		{
			case 'occupation' :
			{
				$fieldid = 4;
				break;
			}

			case 'interests':
			{
				$fieldid = 3;
				break;
			}

			case 'location':
			{
				$fieldid = 2;
				break;
			}

			case 'biography':
			{
				$fieldid = 1;
				break;
			}

			default:
			{
				return false;
			}
		}

		if ($fieldid)
		{
			$list = $Db_object->query("
				SELECT userid, field" . intval($fieldid) . " AS " . $title . "
				FROM " . $tableprefix . "userfield
			");

			while ($fielddata = $Db_object->fetch_array($list))
			{
				if ($fielddata[$title])
				{
					$return_array[$fielddata['userid']] = strtolower($fielddata[$title]);
				}
			}

			return $return_array;
		}
	}

	/**
	* Returns the vBuserd id associated with an importid
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	string	mixed			The imported user id
	*
	* @return	int
	*/
	public function get_vb_userid($Db_object, $databasetype, $tableprefix, $importuserid)
	{
		$result = $Db_object->query_first("
			SELECT userid
			FROM " . $tableprefix . "user
			WHERE importuserid = " . intval($importuserid) . "
		");

		if ($result)
		{
			return $result['userid'];
		}
		else
		{
			return false;
		}
	}

	/**
	* Returns an array of the style ids key'ed to the import style id's
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		0|1				Wether or not to inval the import style id
	*
	* @return	array	mixed			The vb id of the thread
	*/
	public function get_style_ids($Db_object, $databasetype, $tableprefix, $pad = 0)
	{
		$styleid = array();

		$styles = $Db_object->query("
			SELECT styleid, importstyleid
			FROM " . $tableprefix . "style
		");

		while ($style = $Db_object->fetch_array($styles))
		{
			$impstyleid = ($pad ? $style['importstyleid'] : intval($style['importstyleid']));
			$styleid["$impstyleid"] = $style['styleid'];
		}

		$Db_object->free_result($styles);

		return $styleid;
	}

	/**
	* Returns an array of usergroup => usergroupid
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	array
	*/
	public function get_imported_group_ids_by_name($Db_object, $databasetype, $tableprefix)
	{
		$return_data = array();

		$user_groups = $Db_object->query("
			SELECT usergroupid, title
			FROM " . $tableprefix . "usergroup
			WHERE importusergroupid <> 0
		");

		while ($group = $Db_object->fetch_array($user_groups))
		{
			$return_data["$group[title]"] = $group['usergroupid'];
		}

		$Db_object->free_result($user_groups);

		return $return_data;
	}

	/**
	* Returns the username by searching on the importuserid or the userid
	*
	* @param	object	databaseobject		The database that the function is going to interact with.
	* @param	string	mixed				The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed				The prefix to the table name i.e. 'vb3_'
	* @param	string	mixed				The user id
	* @param	string	importuserid|userid	A switch to indicate if you are searching on the importuserid or the userid
	*
	* @return	int
	*/
	public function get_one_username($Db_object, $databasetype, $tableprefix, $theuserid, $id='importuserid')
	{
		switch ($id)
		{
			case 'importuserid':
			{
				$result = $Db_object->query_first("
					SELECT username
					FROM " . $tableprefix . "user
					WHERE importuserid = " . intval($theuserid) . "
				");
			}
			break;

			case 'userid':
			{
				$result = $Db_object->query_first("
					SELECT username
					FROM " . $tableprefix . "user
					WHERE userid = " . intval($theuserid) . "
				");
			}
			break;

			default:
			{
				return false;
			}
		}

		if ($result)
		{
			return $result['username'];
		}
		else
		{
			return false;
		}
	}

	/**
	* Returns a 2D array of the users [userid][username][importuserid]
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			The importuserid to start at
	* @param	int		mixed			The number of user rows to return
	* @param	mixed	boolean|array	FALSE or the data array
	*
	* @return	int
	*/
	public function get_user_array($Db_object, $databasetype, $tableprefix, $startat = null, $perpage = null)
	{
		$_usersarray = array();
		$limit = '';

		if ($startat != null OR $perpage != null)
		{
			$limit = " LIMIT " . intval($startat) . ", " . intval($perpage) . "";
		}

		$result = $Db_object->query("
			SELECT userid, username, importuserid
			FROM " . $tableprefix . "user
			" . $limit . "
		");

		if (!$Db_object->num_rows($result))
		{
			return false;
		}

		if ($result)
		{
			while ($user = $Db_object->fetch_array($result))
			{
				$tempArray = array(
					'userid' => $user['userid'],
					'username' => $user['username'],
					'importuserid' => $user['importuserid']
				);
				array_push($_usersarray, $tempArray);
			}
			$Db_object->free_result($result);

			return $_usersarray;

		}
		else
		{
			return false;
		}
	}

	/**
	* Returns a string of the banned group id
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	string	mixed			The id/name of the Banned group (needs to be updated for permissions)
	*/
	public function get_banned_group($Db_object, $databasetype, $tableprefix)
	{
		$banned_grouip_id = $Db_object->query_first("
			SELECT usergroupid
			FROM " . $tableprefix . "usergroup
			WHERE title = 'Banned Users'
		");

		if ($banned_grouip_id)
		{
			return $banned_grouip_id['usergroupid'];
		}
		else
		{
			return false;
		}
	}

	/**
	* See https://www.youtube.com/watch?v=lg7MAacSPNM
	*/
	public function zuul($dana)
	{
		$zuul = "There is no importers only zuul";

		return $zuul;
	}

	/**
	* Returns an array of the 'importedusergroupid'=>'usergroupid'
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	string	mixed			The id/name of the Banned group (needs to be updated for permissions)
	*/
	public function get_imported_group_ids($Db_object, $databasetype, $tableprefix)
	{
		$return_data = array();

		$user_groups = $Db_object->query("
			SELECT usergroupid, importusergroupid
			FROM " . $tableprefix . "usergroup
			WHERE importusergroupid <> 0
		");

		while ($group = $Db_object->fetch_array($user_groups))
		{
			$return_data["$group[importusergroupid]"] = $group['usergroupid'];
		}

		$Db_object->free_result($user_groups);

		return $return_data;
	}

	/**
	* Returns a vB thread id.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	* @param	string	mixed			The imported thread id.
	* @param	string	mixed			The imported forum id.
	*
	* @return	int		mixed			The vb id of the thread
	*/
	public function get_thread_id($Db_object, $databasetype, $tableprefix, $importthreadid, $forumid)
	{
		$result = $Db_object->query_first("
			SELECT threadid FROM " . $tableprefix . "thread
			WHERE importthreadid= " . intval($importthreadid) . "
				AND importforumid = " . intval($forumid) . "
		");

		return $result['threadid'];
	}

	/**
	* Returns a vB forum and thread ids.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	*/
	public function get_forum_and_thread_ids($Db_object, $databasetype, $tableprefix)
	{
		$return_data = array();

		$result = $Db_object->query("
			SELECT threadid, importthreadid, importforumid
			FROM " . $tableprefix . "thread
		");

		while ($ids = $Db_object->fetch_array($result))
		{
			$return_data["$ids[importforumid]"]["$ids[importthreadid]"] = $ids['threadid'];
		}

		$Db_object->free_result($result);

		return $return_data;
	}

	/**
	* Returns a vB thread id array
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	array	mixed			The array of vb ids of the threads
	*/
	public function get_threads_ids($Db_object, $databasetype, $tableprefix)
	{
		$threadid = array();

		$threads = $Db_object->query("
			SELECT threadid, importthreadid
			FROM " . $tableprefix . "thread
			WHERE importthreadid <> 0
		");

		while ($thread = $Db_object->fetch_array($threads))
		{
			$threadid["$thread[importthreadid]"] = $thread['threadid'];
		}

		$Db_object->free_result($threads);

		return $threadid;
	}

	/**
	* Returns a vB post id array
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	array	mixed			The array of vb ids of the threads
	*/
	public function get_posts_ids($Db_object, $databasetype, $tableprefix)
	{
		$return_array = array();

		$posts = $Db_object->query("
			SELECT postid, importpostid
			FROM " . $tableprefix . "post
			WHERE importpostid <> 0
		");

		while ($post = $Db_object->fetch_array($posts))
		{
			$return_array["$post[importpostid]"] = $post['postid'];
		}

		$Db_object->free_result($posts);

		return $return_array;
	}

	/**
	* Returns an array of the forum ids key'ed to the importforum id's
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	array	mixed			The vb id of the thread
	*/
	public function get_category_ids($Db_object, $databasetype, $tableprefix)
	{
		$categoryid = array();

		$forums = $Db_object->query("
			SELECT forumid, importcategoryid
			FROM " . $tableprefix . "forum
			WHERE importcategoryid <> 0
		");

		while ($forum = $Db_object->fetch_array($forums))
		{
			$categoryid["$forum[importcategoryid]"] = $forum['forumid'];
		}

		$Db_object->free_result($forums);

		return $categoryid;
	}

	/**
	* Returns a vB category id.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	*/
	public function get_category_id_by_name($Db_object, $databasetype, $tableprefix)
	{
		$categoryid = array();

		$forums = $Db_object->query("
			SELECT forumid, title
			FROM " . $tableprefix . "forum
			WHERE importcategoryid <> 0
		");

		while ($forum = $Db_object->fetch_array($forums))
		{
			$categoryid["$forum[title]"] = $forum['forumid'];
		}

		$Db_object->free_result($forums);

		return $categoryid;
	}

	/**
	* Returns a vB forum id.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	*/
	public function get_forum_id_by_name($Db_object, $databasetype, $tableprefix)
	{
		$categoryid = array();

		$forums = $Db_object->query("
			SELECT forumid, importforumid, title
			FROM " . $tableprefix . "forum
			WHERE importforumid <> 0
		");

		while ($forum = $Db_object->fetch_array($forums))
		{
			$categoryid["$forum[title]"]['forumid'] = $forum['forumid'];
			$categoryid["$forum[title]"]['importforumid'] = $forum['importforumid'];
		}

		$Db_object->free_result($forums);

		return $categoryid;
	}

	/**
	* Returns an array of 'import_user_id' => 'vb_user_id'
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	string	mixed			importuser id delimited string
	*/
	// Redundant :: dupe checking now
	public function get_done_user_ids($Db_object, $databasetype, $tableprefix)
	{
		$return_array = array();

		$user_ids = $Db_object->query("
			SELECT userid, importuserid
			FROM " . $tableprefix . "user
			WHERE importuserid <> 0
		");

		while ($user_id = $Db_object->fetch_array($user_ids))
		{
			$return_array["$user_id[importuserid]"] = $user_id['userid'];
		}

		$Db_object->free_result($user_ids);

		return $return_array;
	}

	/**
	* Returns an array of the user ids key'ed to the import user id's $userid[$importuserid] = $user[userid]
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	array	mixed			Data array[importuserid] = userid
	*/
	public function get_user_ids($Db_object, $databasetype, $tableprefix, $do_int_val = false)
	{
		$userid = array();

		$users = $Db_object->query("
			SELECT userid, username, importuserid
			FROM " . $tableprefix . "user
			WHERE importuserid <> 'null'
		");

		while ($user = $Db_object->fetch_array($users))
		{
			if ($do_int_val)
			{
				$importuserid = intval($user['importuserid']);
			}
			else
			{
				$importuserid = $user['importuserid'];
			}

			$userid["$importuserid"] = $user['userid'];
		}

		$Db_object->free_result($users);

		return $userid;
	}

	/**
	* Returns an array of subscription ids.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	*/
	public function get_subscription_ids($Db_object, $databasetype, $tableprefix)
	{
		$return_array = array();

		$subscriptions = $Db_object->query("
			SELECT subscriptionid, importsubscriptionid
			FROM " . $tableprefix . "subscription
			WHERE importsubscriptionid <> 0
		");

		while ($subscription = $Db_object->fetch_array($subscriptions))
		{
			$return_array["$subscription[importsubscriptionid]"] = $subscription['subscriptionid'];
		}

		$Db_object->free_result($subscriptions);

		return $return_array;
	}

	/**
	* Returns an array of the import user ids key'ed to the username
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	array	mixed			Data array[importuserid] = username
	*/
	public function get_username($Db_object, $databasetype, $tableprefix)
	{
		$username = array();

		$users = $Db_object->query("
			SELECT username, userid, importuserid AS importuserid
			FROM " . $tableprefix ."user
			WHERE importuserid <> 0
		");

		while ($user = $Db_object->fetch_array($users))
		{
			$username["$user[importuserid]"] = $user['username'];
		}

		$Db_object->free_result($users);

		return $username;
	}

	/**
	* Returns an array of usernames with their ids.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	*/
	public function get_username_to_ids($Db_object, $databasetype, $tableprefix)
	{
		$username = array();

		$users = $Db_object->query("
			SELECT username, userid
			FROM " . $tableprefix . "user
			WHERE importuserid <> 0
		");

		while ($user = $Db_object->fetch_array($users))
		{
			$username["$user[username]"] = $user['userid'];
		}

		$Db_object->free_result($users);

		return $username;
	}

	/**
	* Returns an array of emails.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	*/
	public function get_email_to_ids($Db_object, $databasetype, $tableprefix)
	{
		$email = array();

		$users = $Db_object->query("
			SELECT email, userid, username
			FROM " . $tableprefix . "user
			WHERE importuserid <> 0
		");

		while ($user = $Db_object->fetch_array($users))
		{
			$email_addy = strtolower($user['email']);

			$email[$email_addy]['userid'] = $user['userid'];
			$email[$email_addy]['username'] = $user['username'];
		}

		$Db_object->free_result($users);

		return $email;
	}

	/**
	* Returns one postid if from an importpostid, slow but used mainly for parentid's while in a loop
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	array	mixed			Data array[importuserid] = username
	*/
	public function get_vb_post_id($Db_object, $databasetype, $tableprefix, $import_post_id)
	{
		$post_id = array();

		if (!$import_post_id)
		{
			return false;
		}

		$post_id = $Db_object->query_first("
			SELECT postid, importpostid
			FROM " . $tableprefix . "post
			WHERE importpostid = " . intval($import_post_id) . "
		");

		return $post_id['postid'];
	}

	/**
	* Returns an array of the forum ids key'ed to the import forum id's
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		0|1				Wether or not to inval the import forum id
	*
	* @return	array	mixed			Data array[impforumid] = forumid
	*/
	public function get_forum_ids($Db_object, $databasetype, $tableprefix, $pad = 0)
	{
		$forumid = array();

		$forums = $Db_object->query("
			SELECT forumid, importforumid
			FROM " . $tableprefix . "forum
			WHERE importforumid > 0
		");

		while ($forum = $Db_object->fetch_array($forums))
		{
			if ($pad)
			{
				$impforumid = intval($forum['importforumid']);
				$forumid["$impforumid"] = $forum['forumid'];
			}
			else
			{
				$forumid["$forum[importforumid]"] = $forum['forumid'];
			}
		}

		$Db_object->free_result($forums);

		return $forumid;
	}

	/**
	* Clears ALL the IP AND email address in the banlists
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	public function clear_ban_list($Db_object, $databasetype, $tableprefix)
	{
		$Db_object->query("
			UPDATE " . $tableprefix . "datastore SET
				data = ''
			WHERE title = 'banemail'
		");

		$Db_object->query("
			UPDATE " . $tableprefix . "setting SET
				value = ''
			WHERE varname = 'banip'
		");

		return true;
	}

	/**
	* Clears ALL attachments.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	*/
	public function clear_imported_attachments($Db_object, $databasetype, $tableprefix, $contentinfo)
	{
		$contenttypeid = $this->get_contenttypeid($Db_object, $databasetype, $tableprefix, $contentinfo['productid'], $contentinfo['class']);

		$Db_object->query("
			DELETE FROM " . $tableprefix  . "attachment
			WHERE importattachmentid <> 0
				AND contenttypeid = " . intval($contenttypeid) . "
		");

		$Db_object->query("
			ALTER TABLE " . $tableprefix  . "attachment AUTO_INCREMENT = 0
		");

		$Db_object->query("
			ALTER TABLE " . $tableprefix  . "attachment auto_increment = 0
		");

		return true;
	}

	/**
	* Clears ALL subscriptions.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	*/
	public function clear_imported_subscriptions($Db_object, $databasetype, $tableprefix)
	{
		$Db_object->query("
			DELETE FROM " . $tableprefix  . "subscription
			WHERE importsubscriptionid <> 0
		");

		$Db_object->query("
			ALTER TABLE " . $tableprefix  . "subscription AUTO_INCREMENT = 0
		");

		$Db_object->query("
			ALTER TABLE " . $tableprefix  . "subscription auto_increment = 0
		");

		$Db_object->query("
			DELETE FROM " . $tableprefix  . "subscriptionlog
			WHERE importsubscriptionlogid <> 0
		");

		$Db_object->query("
			ALTER TABLE " . $tableprefix  . "subscriptionlog AUTO_INCREMENT = 0
		");

		$Db_object->query("
			ALTER TABLE " . $tableprefix  . "subscriptionlog auto_increment = 0
		");

		return true;
	}

	/**
	* Clears the currently imported avatars.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	public function clear_imported_avatars($Db_object, $databasetype, $tableprefix)
	{
		$Db_object->query("
			DELETE FROM " . $tableprefix  . "avatar
			WHERE importavatarid <> 0
		");

		$Db_object->query("
			DELETE FROM " . $tableprefix  . "customavatar
			WHERE importcustomavatarid <> 0
		");

		$Db_object->query("
			ALTER TABLE " . $tableprefix  . "avatar AUTO_INCREMENT = 0
		");

		$Db_object->query("
			ALTER TABLE " . $tableprefix  . "avatar auto_increment = 0
		");

		$Db_object->query("
			ALTER TABLE " . $tableprefix  . "customavatar AUTO_INCREMENT = 0
		");

		$Db_object->query("
			ALTER TABLE " . $tableprefix  . "customavatar auto_increment = 0
		");

		return true;
	}

	/**
	* Clears the currently imported forums
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	public function clear_imported_forums($Db_object, $databasetype, $tableprefix)
	{
		// delete imported categories and forums
		$Db_object->query("
			DELETE FROM " . $tableprefix  . "forum
			WHERE importforumid <> 0
				OR importcategoryid <> 0
		");

		// reset the auto increment
		$Db_object->query("
			ALTER TABLE " . $tableprefix  . "forum AUTO_INCREMENT = 0
		");

		$Db_object->query("
			ALTER TABLE " . $tableprefix  . "forum auto_increment = 0
		");

		return true;
	}

	/**
	* Clears the currently imported threads
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	public function clear_imported_threads($Db_object, $databasetype, $tableprefix)
	{
		$Db_object->query("
			DELETE FROM " . $tableprefix  . "thread WHERE importthreadid <> 0
		");

		$Db_object->query("
			ALTER TABLE " . $tableprefix  . "thread AUTO_INCREMENT = 0
		");

		$Db_object->query("
			ALTER TABLE " . $tableprefix  . "thread auto_increment = 0
		");

		return true;
	}

	/**
	* Clears the currently banned users
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	public function clear_banned_users($Db_object, $databasetype, $tableprefix)
	{
		$user_id = $Db_object->query_first("
			SELECT usergroupid
			FROM " . $tableprefix . "usergroup
			WHERE title = 'Banned Users'
		");

		if ($user_id)
		{
			$Db_object->query("
				DELETE FROM " . $tableprefix  . "user
				WHERE usergroupid <> " . intval($user_id['usergroupid']) . "
			");
		}

		return true;
	}

	/**
	* Clears the currently imported users
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	public function clear_imported_users($Db_object, $databasetype, $tableprefix)
	{
		$users = $Db_object->query("
			SELECT userid
			FROM " . $tableprefix  . "user
			WHERE importuserid <> 0
		");

		if ($Db_object->num_rows($users))
		{
			$removeid = array('0');

			while ($user = $Db_object->fetch_array($users))
			{
				$removeid[] = $user['userid'];
			}

			$Db_object->free_result($users);

			$ids = implode(',', $removeid);

			// user
			$Db_object->query("
				DELETE FROM " . $tableprefix  . "user
				WHERE userid IN(" . $ids . ")
			");

			$Db_object->query("
				ALTER TABLE " . $tableprefix  . "user AUTO_INCREMENT = 0
			");

			$Db_object->query("
				ALTER TABLE " . $tableprefix  . "user auto_increment = 0
			");

			// customavatar
			$Db_object->query("
				DELETE FROM " . $tableprefix  . "customavatar
				WHERE userid IN(" . $ids . ")
			");

			$Db_object->query("
				ALTER TABLE " . $tableprefix  . "customavatar AUTO_INCREMENT = 0
			");

			$Db_object->query("
				ALTER TABLE " . $tableprefix  . "customavatar auto_increment = 0
			");

			// customprofilepic
			$Db_object->query("
				DELETE FROM " . $tableprefix  . "customprofilepic
				WHERE userid IN(" . $ids . ")
			");

			$Db_object->query("
				ALTER TABLE " . $tableprefix  . "customprofilepic AUTO_INCREMENT = 0
			");

			$Db_object->query("
				ALTER TABLE " . $tableprefix  . "customprofilepic auto_increment = 0
			");

			// userfield
			$Db_object->query("
				DELETE FROM " . $tableprefix  . "userfield
				WHERE userid IN(" . $ids . ")
			");

			$Db_object->query("
				ALTER TABLE " . $tableprefix  . "userfield AUTO_INCREMENT = 0
			");

			$Db_object->query("
				ALTER TABLE " . $tableprefix  . "userfield auto_increment = 0
			");

			// usertextfield
			$Db_object->query("
				DELETE FROM " . $tableprefix  . "usertextfield
				WHERE userid IN(" . $ids . ")
			");

			$Db_object->query("
				ALTER TABLE " . $tableprefix  . "usertextfield AUTO_INCREMENT = 0
			");

			$Db_object->query("
				ALTER TABLE " . $tableprefix  . "usertextfield auto_increment = 0
			");

			// usernote
			$Db_object->query("
				DELETE FROM " . $tableprefix  . "usernote
				WHERE userid IN(" . $ids . ")
			");

			$Db_object->query("
				ALTER TABLE " . $tableprefix  . "usernote AUTO_INCREMENT = 0
			");

			$Db_object->query("
				ALTER TABLE " . $tableprefix  . "usernote auto_increment = 0
			");
		}

		 return true;
	}

	/**
	* Clears the currently imported posts
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	public function clear_imported_posts($Db_object, $databasetype, $tableprefix)
	{
		$Db_object->query("
			DELETE FROM " . $tableprefix  . "post
			WHERE importthreadid <> 0
		");

		$Db_object->query("
			DELETE FROM " . $tableprefix  . "post
			WHERE importpostid <> 0
		");

		$Db_object->query("
			ALTER TABLE " . $tableprefix  . "post AUTO_INCREMENT = 0
		");

		$Db_object->query("
			ALTER TABLE " . $tableprefix  . "post auto_increment = 0
		");

		return true;
	}

	/**
	* Clears the currently imported polls
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	public function clear_imported_polls($Db_object, $databasetype, $tableprefix)
	{
		// poll ids
		$polls = $Db_object->query("
			SELECT pollid
			FROM " . $tableprefix  . "poll
			WHERE importpollid <> 0
		");

		if ($Db_object->num_rows($polls))
		{
			$removeid = array('0');

			while ($poll = $Db_object->fetch_array($polls))
			{
				$removeid[] = $poll['pollid'];
			}

			$poll_ids = implode(',', $removeid);

			// Remove them
			$Db_object->query("
				UPDATE " . $tableprefix  . "thread SET
					pollid = 0
				WHERE importthreadid <> 0
			");

			$Db_object->query("
				DELETE from " . $tableprefix  . "poll
				WHERE pollid IN(" . $poll_ids . ")
			");

			$Db_object->query("
				DELETE from " . $tableprefix  . "pollvote
				WHERE pollid IN(" . $poll_ids . ")
			");

			$Db_object->query("
				ALTER TABLE " . $tableprefix  . "poll AUTO_INCREMENT = 0
			");

			$Db_object->query("
				ALTER TABLE " . $tableprefix  . "poll auto_increment = 0
			");

			$Db_object->query("
				ALTER TABLE " . $tableprefix  . "pollvote AUTO_INCREMENT = 0
			");

			$Db_object->query("
				ALTER TABLE " . $tableprefix  . "pollvote auto_increment = 0
			");
		}

		return true;
	}

	/**
	* Clears the currently imported buddy list(s) from the currently imported users
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	public function clear_imported_buddy_list($Db_object, $databasetype, $tableprefix)
	{
		$imported_users = $Db_object->query("
			SELECT userid
			FROM " . $tableprefix  . "user
			WHERE importuserid <> 0
		");

		if ($Db_object->num_rows($imported_users))
		{
			$userids = array('0');

			while ($userid = $Db_object->fetch_array($imported_users))
			{
				$userids[] = $userid['userid'];
			}

			$Db_object->query("
				UPDATE " . $tableprefix . "usertextfield SET
					buddylist = ''
				WHERE userid IN (" . implode(',', $userids) . ")
			");
		}

		return true;
	}

	/**
	* Clears the currently imported ignore list(s) from the currently imported users
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	public function clear_imported_ignore_list($Db_object, $databasetype, $tableprefix)
	{
		$imported_users = $Db_object->query("
			SELECT userid
			FROM " . $tableprefix  . "user
			WHERE importuserid <> 0
		");

		if ($Db_object->num_rows($imported_users))
		{
			$userids = array('0');

			while ($userid = $Db_object->fetch_array($imported_users))
			{
				$userids[] = $userid['userid'];
			}

			$Db_object->query("
				UPDATE " . $tableprefix . "usertextfield SET
					ignorelist = ''
				WHERE userid IN (" . implode(',', $userids) . ")
			");
		}

		return true;
	}

	/**
	* Clears the currently imported pm's & pmtext's from the currently imported users
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	public function clear_imported_private_messages($Db_object, $databasetype, $tableprefix)
	{
		// user ids
		$users = $Db_object->query("
			SELECT userid
			FROM " . $tableprefix  . "user
			WHERE importuserid <> 0
		");

		if ($Db_object->num_rows($users))
		{
			$removeid = array('0');

			while ($user = $Db_object->fetch_array($users))
			{
				$removeid[] = $user['userid'];
			}

			$user_ids = implode(',', $removeid);

			// pm_texts
			$pm_text_ids = $Db_object->query("
				SELECT pmtextid
				FROM " . $tableprefix  . "pm
				WHERE userid IN(" . $user_ids . ")
			");

			$removeid = array('0');

			while ($pm_text = $Db_object->fetch_array($pm_text_ids))
			{
				$removeid[] = $pm_text['pmtextid'];
			}

			$_pm_text_ids = implode(',', $removeid);

			// Remove them
			$Db_object->query("
				DELETE FROM " . $tableprefix  . "pmtext
				WHERE fromuserid IN(" . $_pm_text_ids . ")
			");

			$Db_object->query("
				DELETE FROM " . $tableprefix  . "pm
				WHERE userid IN(" . $user_ids . ")
			");

			// Just to make sure.
			$keys = $Db_object->query("
				DESCRIBE " . $tableprefix . "pm
			");

			while ($key = $Db_object->fetch_array($keys))
			{
				if ($key['Field'] == "importpmid")
				{
					$Db_object->query("
						DELETE FROM " . $tableprefix  . "pm
						WHERE importpmid <> 0
					");
				}
			}

			$keys = $Db_object->query("DESCRIBE `" . $tableprefix . "pmtext`");

			while ($key = $Db_object->fetch_array($keys))
			{
				if ($key['Field'] == "importpmid")
				{
					$Db_object->query("
						DELETE FROM " . $tableprefix  . "pmtext
						WHERE importpmid <> 0
					");
				}
			}
		}

		return true;
	}

	/**
	* Clears the currently imported moderators
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	*
	* @return	boolean
	*/
	public function clear_imported_moderators($Db_object, $databasetype, $tableprefix)
	{
		$imported_users = $Db_object->query("
			SELECT userid
			FROM " . $tableprefix  . "user
			WHERE importuserid <> 0
		");

		if ($Db_object->num_rows($imported_users))
		{
			$removeid = array('0');

			while ($userid = $Db_object->fetch_array($imported_users))
			{
				$removeid[] = $userid['userid'];
			}

			$Db_object->query("
				DELETE FROM " . $tableprefix  . "moderator
				WHERE userid IN (" . implode(',', $removeid) . ")
			");
		}

		return true;
	}

	/**
	* Clears the currently imported smilies
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	public function clear_imported_smilies($Db_object, $databasetype, $tableprefix)
	{
		$Db_object->query("
			DELETE FROM " . $tableprefix  . "smilie
			WHERE importsmilieid <> 0
		");

		return true;
	}

	/**
	* Clears the currently imported smilies
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	public function clear_imported_user_groups($Db_object, $databasetype, $tableprefix)
	{
		$Db_object->query("
			DELETE FROM " . $tableprefix  . "usergroup
			WHERE importusergroupid <> 0
		");

		$Db_object->query("
			ALTER TABLE " . $tableprefix  . "usergroup AUTO_INCREMENT = 0
		");

		$Db_object->query("
			ALTER TABLE " . $tableprefix  . "usergroup auto_increment = 0
		");

		return true;
	}

	/**
	* Clears the currently imported smilies
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	public function clear_imported_custom_pics($Db_object, $databasetype, $tableprefix)
	{
		$Db_object->query("
			DELETE FROM " . $tableprefix  . "customprofilepic
			WHERE importcustomprofilepicid <> 0
		");

		$Db_object->query("
			ALTER TABLE " . $tableprefix  . "customprofilepic AUTO_INCREMENT = 0
		");

		$Db_object->query("
			ALTER TABLE " . $tableprefix  . "customprofilepic auto_increment = 0
		");

		return true;
	}

	/**
	* Clears the currently imported ranks
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	public function clear_imported_ranks($Db_object, $databasetype, $tableprefix)
	{
		$Db_object->query("
			DELETE FROM " . $tableprefix  . "ranks
			WHERE importrankid <> 0
		");

		$Db_object->query("
			ALTER TABLE " . $tableprefix  . "ranks AUTO_INCREMENT = 0
		");

		$Db_object->query("
			ALTER TABLE " . $tableprefix  . "ranks auto_increment = 0
		");

		return true;
	}

	/**
	* Clears the currently imported usergroups
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	public function clear_imported_usergroups($Db_object, $databasetype, $tableprefix)
	{
		$Db_object->query("
			DELETE FROM " . $tableprefix  . "usergroup
			WHERE importusergroupid <> 0
		");

		$Db_object->query("
			ALTER TABLE " . $tableprefix  . "usergroup AUTO_INCREMENT = 0
		");

		$Db_object->query("
			ALTER TABLE " . $tableprefix  . "usergroup auto_increment = 0
		");

		return true;
	}

	/**
	* Clears ALL imported phrases.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	*
	* @return	boolean
	*/
	public function clear_imported_phrases($Db_object, $databasetype, $tableprefix)
	{
		$Db_object->query("
			DELETE FROM " . $tableprefix  . "phrase
			WHERE importphraseid <> 0
		");

		$Db_object->query("
			ALTER TABLE " . $tableprefix  . "phrase AUTO_INCREMENT = 0
		");

		$Db_object->query("
			ALTER TABLE " . $tableprefix  . "phrase auto_increment = 0
		");

		return true;
	}

	/**
	* Clears ALL imported non-admin users.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	*
	* @return	boolean
	*/
	public function clear_non_admin_users($Db_object, $databasetype, $tableprefix)
	{
		$users = $Db_object->query("
			SELECT userid, username
			FROM " . $tableprefix . "user AS user
				LEFT JOIN " . $tableprefix . "usergroup AS usergroup USING (usergroupid)
			WHERE !(usergroup.adminpermissions & 3) # this is the 'cancontrolpanel' option
		");

		if ($Db_object->num_rows($users))
		{
			$removeid = array('0');

			while ($user = $Db_object->fetch_array($users))
			{
				$Db_object->query("
					UPDATE " . $tableprefix . "post SET
						username = '" . $Db_object->escape_string($user['username']) . "',
						userid = 0
					WHERE userid = " . intval($user['userid']) . "
				");

				$Db_object->query("
					UPDATE " . $tableprefix . "usernote SET
						username = '" . $Db_object->escape_string($user['username']) . "',
						posterid = 0
					WHERE posterid = " . intval($user['userid']) . "
				");

				$removeid[] = $user['userid'];
			}

			$ids = implode(',', $removeid);

			// user-related
			$Db_object->query("
				DELETE FROM " . $tableprefix . "usernote
				WHERE userid IN (" . $ids . ")
			");

			$Db_object->query("
				DELETE FROM " . $tableprefix . "user
				WHERE userid IN (" . $ids . ")
			");

			$Db_object->query("
				DELETE FROM " . $tableprefix . "userfield
				WHERE userid IN (" . $ids . ")
			");

			$Db_object->query("
				DELETE FROM " . $tableprefix . "usertextfield
				WHERE userid IN (" . $ids . ")
			");

			$Db_object->query("
				DELETE FROM " . $tableprefix . "access
				WHERE userid IN (" . $ids . ")
			");

			$Db_object->query("
				DELETE FROM " . $tableprefix . "event
				WHERE userid IN (" . $ids . ")
			");

			$Db_object->query("
				DELETE FROM " . $tableprefix . "customavatar
				WHERE userid IN (" . $ids . ")
			");

			$Db_object->query("
				DELETE FROM " . $tableprefix . "customprofilepic
				WHERE userid IN (" . $ids . ")
			");

			$Db_object->query("
				DELETE FROM " . $tableprefix . "moderator
				WHERE userid IN ($" . $ids . ")
			");

			$Db_object->query("
				DELETE FROM " . $tableprefix . "subscribeforum
				WHERE userid IN (" . $ids . ")
			");

			$Db_object->query("
				DELETE FROM " . $tableprefix . "subscribethread
				WHERE userid IN (" . $ids . ")
			");

			$Db_object->query("
				DELETE FROM " . $tableprefix . "subscriptionlog
				WHERE userid IN (" . $ids . ")
			");

			$Db_object->query("
				DELETE FROM " . $tableprefix . "session
				WHERE userid IN (" . $ids . ")
			");

			$Db_object->query("
				DELETE FROM " . $tableprefix . "userban
				WHERE userid IN (" . $ids . ")
			");

			$Db_object->query("
				DELETE FROM " . $tableprefix . "administrator
				WHERE userid IN (" . $ids . ")
			");

			// user
			$Db_object->query("
				DELETE FROM " . $tableprefix  . "user
				WHERE userid IN(" . $ids . ")
			");

			$Db_object->query("
				DELETE FROM " . $tableprefix  . "customavatar
				WHERE userid IN(" . $ids . ")
			");

			$Db_object->query("
				DELETE FROM " . $tableprefix  . "customprofilepic
				WHERE userid IN(" . $ids . ")
			");

			$Db_object->query("
				DELETE FROM " . $tableprefix  . "userfield
				WHERE userid IN(" . $ids . ")
			");

			$Db_object->query("
				DELETE FROM " . $tableprefix  . "usertextfield
				WHERE userid IN(" . $ids . ")
			");
		}

		 return true;
	}

	/**
	* Adds a PM folder for ALL imported users.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	*/
	public function add_pm_folder_for_all_users($Db_object, $databasetype, $tableprefix, $new_folder_name)
	{
		if (!$new_folder_name)
		{
			return false;
		}

		// Get all the user ids ......
		$user_ids = $Db_object->query("
			SELECT userid
			FROM " . $tableprefix . "user
			WHERE importuserid <> 0
		");

		// Uggg one at a time....
		while ($userid = $Db_object->fetch_array($user_ids))
		{
			$current_folders = array();

			// Get the current users folders
			$db_folders = $Db_object->query_first("
				SELECT pmfolders
				FROM " . $tableprefix . "usertextfield
				WHERE userid=" . intval($userid['userid']) . "
			");

			// Append the new one
			if ($db_folders['pmfolders'])
			{
				$current_folders = unserialize($db_folders['pmfolders']);
				$current_folders[] = $new_folder_name;
			}
			else
			{
				$current_folders['1'] = $new_folder_name;
			}

			// Write it back to the usertextfield
			$Db_object->query("
				UPDATE " . $tableprefix . "usertextfield SET
					pmfolders = '" . serialize($current_folders) . "'
				WHERE userid = " . intval($userid['userid']) . "
			");

			unset($current_folders);
		}

		return true;
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
			FROM " . $tableprefix."forum
			WHERE parentid = 0
				AND importforumid <> 0
		");

		while ($do = $Db_object->fetch_array($do_list))
		{
			$catid = $do['importcategoryid'];
			$fid = $do['forumid'];

			if ($importid[$catid] AND $fid)
			{
				$Db_object->query("
					UPDATE " . $tableprefix . "forum SET
						parentid = " . intval($importid[$catid]) . "
					WHERE forumid = " . intval($fid) . ")
				");
			}
		}

		return true;
	}

	/**
	* Updates PM count for all users.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	*
	* @return	boolean
	*/
	public function update_user_pm_count($Db_object, $databasetype, $tableprefix)
	{
		$users = $Db_object->query("
			SELECT userid, username
			FROM " . $tableprefix . "user
		");

		while ($user = $Db_object->fetch_array($users))
		{
			$pmcount = $Db_object->query("
				SELECT COUNT(*)
				FROM " . $tableprefix . "pm
				WHERE userid = " . intval($user['userid']) . "
			");

			$pms = $Db_object->fetch_array($pmcount);

			if (intval($pms[key($pms)]) != 0)
			{
				$Db_object->query("
					UPDATE " . $tableprefix ."user SET
						pmtotal=" . intval($pms[key($pms)]) . "
					WHERE userid = " . intval($user['userid']) . "
				");
			}
		}

		return true;
	}

	/**
	* Updates vB3 poll ids after a vB3 import
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	array
	*/
	public function update_poll_ids($Db_object, $databasetype, $tableprefix)
	{
		$result = $Db_object->query("
			SELECT pollid, threadid, importthreadid
			FROM " . $tableprefix . "thread
			WHERE open=10
				AND pollid <> 0
				AND importthreadid <> 0
		");

		while ($thread = $Db_object->fetch_array($result))
		{
			$new_thread_id = $Db_object->query_first("
				SELECT threadid
				FROM " . $tableprefix . "thread
				WHERE importthreadid = " . intval($thread['pollid']) . "
			");

			if ($new_thread_id['threadid'])
			{
				// Got it
				$Db_object->query("
					UPDATE " . $tableprefix . "thread SET
						pollid = " . intval($new_thread_id['threadid']) . "
					WHERE threadid=" . intval($thread['threadid']) . "
				");
			}
		}

		return true;
	}

	/**
	* Updates forum permissions
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	string	mixed			The forumid
	*
	* @return	boolean
	*/
	public function build_user_statistics($Db_object, $databasetype, $tableprefix)
	{
		// get total members
		$members = $Db_object->query_first("
			SELECT COUNT(*) AS users, MAX(userid) AS max
			FROM " . $tableprefix . "user
		");

		// get newest member
		$newuser = $Db_object->query_first("
			SELECT userid, username
			FROM " . $tableprefix . "user
			WHERE userid = " . intval($members['max']) . "
		");

		// make a little array with the data
		$values = array(
			'numbermembers' => intval($members['users']),
			'newusername' => intval($newuser['username']),
			'newuserid' => intval($newuser['userid'])
		);

		// update the special template
		$Db_object->query("
			REPLACE INTO " . $tableprefix . "datastore
				(title, data)
			VALUES
				('userstats', '" . $Db_object->escape_string(serialize($values)) . "')
		");

		return true;
	}

	/**
	* Rebuilds a forums child list string
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	string	mixed			The forumid
	*
	* @return	string
	*/
	public function construct_child_list($Db_object, $databasetype, $tableprefix, $forumid)
	{
		if ($forumid == -1)
		{
			return '-1';
		}

		$childlist = $forumid;

		$children = $Db_object->query("
			SELECT forumid
			FROM " . $tableprefix . "forum
			WHERE parentid = " . intval($forumid) . "
		");

		while ($child = $Db_object->fetch_array($children))
		{
			$childlist .= ',' . $child['forumid'];
		}

		$childlist .= ',-1';

		return $childlist;
	}

	/**
	* Rebuilds all the forums child lists
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	string	mixed			The forumid
	*
	* @return	none
	*/
	public function build_forum_child_lists($Db_object, $databasetype, $tableprefix, $forumid = -1)
	{
		$forums = $Db_object->query("
			SELECT forumid
			FROM " . $tableprefix . "forum
			WHERE childlist = ''
		");

		while ($forum = $Db_object->fetch_array($forums))
		{
			$childlist = $this->construct_child_list($Db_object, $databasetype, $tableprefix, $forum['forumid']);

			$Db_object->query("
				UPDATE " . $tableprefix . "forum SET
					childlist = '$childlist'
				WHERE forumid = " . intval($forum['forumid']) . "
			");
		}

		return true;
	}

	/**
	* Updates the parentids of the posts in the database if they are 0
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	*
	* @return	boolean
	*/
	public function update_post_parent_ids($Db_object, $databasetype, $tableprefix)
	{
		if (skipparentids)
		{
			return true;
		}

		$thread_ids = $Db_object->query("
			SELECT DISTINCT threadid
			FROM " . $tableprefix . "post
			WHERE importthreadid <> 0
		");

		if ($Db_object->num_rows($thread_ids))
		{
			while ($thread_id = $Db_object->fetch_array($thread_ids))
			{
				$parentpost = $Db_object->query_first("
					SELECT postid
					FROM " . $tableprefix . "post
					WHERE threadid = " . intval($thread_id['threadid']) . "
					ORDER BY dateline
					LIMIT 1
				");

				$Db_object->query("
					UPDATE " . $tableprefix . "post SET
						parentid = " . intval($parentpost['postid']) . "
					WHERE threadid = " . intval($thread_id['threadid']) . "
						AND postid <> " . intval($parentpost['postid']) . "
						AND parentid = 0
				");
			}
		}

		return true;
	}

	/**
	* Updates forum permissions
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	* @param	string	mixed			The forumid
	*
	* @return	boolean
	*/
	public function set_forum_private($Db_object, $databasetype, $tableprefix, $forum_id)
	{
		$usergroupids = $Db_object->query("
			SELECT usergroupid
			FROM " . $tableprefix . "usergroup
			WHERE title IN ('Super Moderators', 'Administrators', 'Moderators')
		");

		if ($Db_object->num_rows($usergroupids))
		{
			$extended_insert = array();

			while ($usergroupid = $Db_object->fetch_array($usergroupids))
			{
				$exists = $Db_object->query_first("
					SELECT forumpermissionid
					FROM " . $tableprefix . "forumpermission
					WHERE forumid = " . intval($forum_id) . "
						AND usergroupid = ". intval($usergroupid['usergroupid']) . "
				");

				if ($exists['forumpermissionid'] > 0)
				{
					// Its already there
				}
				else
				{
					$extended_insert[] = "(" . intval($forum_id) . ", " . intval($usergroupid['usergroupid']) . ", 0)";
				}
			}

			if (!empty($extended_insert))
			{
				$Db_object->query("
					INSERT INTO " . $tableprefix . "forumpermission
						(forumid, usergroupid, forumpermissions)
					VALUES
						" . implode(', ', $extended_insert) . "
				");
			}
		}

		return true;
	}

	/**
	* Checks the user field.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	* @param	string	mixed			The field.
	*
	* @return	boolean
	*/
	public function check_user_field($Db_object, $databasetype, $tableprefix, $field)
	{
		if (!$field)
		{
			return false;
		}

		$Db_object->reporterror = false;

		$result = $Db_object->query_first("
			SELECT " . $field . "
			FROM " . $tableprefix . "userfield
		");

		$Db_object->reporterror = true;

		if (!$Db_object->errno)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	* Checks the database tables.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	* @param	string	mixed			The board type that we are importing from.
	*
	* @return	boolean
	*/
	public function check_database($Db_object, $databasetype, $tableprefix, $sourceexists, &$displayobject)
	{
		// Need to use $this->source_table_cache
		$found_tables = array();

		if (!$sourceexists)
		{
			return array(
				'code'	=>	false,
				'text'	=>	$displayobject->phrases['sourceexists_true']
			);
		}

		$return_string = '';

		if (count($this->_valid_tables) == 0)
		{
			die($displayobject->phrases['validtable_overridden']);
		}

		foreach ($this->_valid_tables AS $key => $value)
		{
			if (lowercase_table_names)
			{
				$valid_tables["$key"] = $tableprefix . strtolower($value);
			}
			else
			{
				$valid_tables["$key"] = $tableprefix . $value;
			}
		}

		($databasetype == 'odbc' ? $databasetype = 'mssql' : true);

		switch ($databasetype)
		{
			// MySQL database
			case 'mysql':
			case 'mysqli':
			{
				$code = false;
				$prefix_poss = array();

				$tables = $Db_object->query("
					SHOW TABLES
				");

				$return_string .= $displayobject->update_html($displayobject->table_header());
				$return_string .= $displayobject->update_html($displayobject->make_table_header($displayobject->phrases['testing_source_against'] . $this->system .' ::' . $this->_version . ''));

				$return_string .= $displayobject->update_html($displayobject->make_description('<b>' . $displayobject->phrases['valid_tables_found'] . '</b>'));

				while ($table = $Db_object->fetch_row($tables))
				{
					if (in_array($table[0], $valid_tables))
					{
						// TODO: return code of phrase
						$return_string .= $displayobject->update_html($displayobject->make_description('<span class="isucc">' . $table[0] . ' ' . $displayobject->phrases['found'] . '.</span>'));

						// List the found ones
						$found_tables[] = $table[0];
						$this->source_table_cache[] = $table[0];
						$code = true;
					}
					else
					{
						foreach ($this->_valid_tables AS $valid_table)
						{
							if ($pos = strpos($table[0], $valid_table))
							{
								$poss_key = substr($table[0], 0, $pos);
								$prefix_poss[$poss_key]++;
							}
						}
					}
				}

				$not_found = array_diff($valid_tables, $found_tables);

				if (count($not_found))
				{
					$return_string .= $displayobject->update_html($displayobject->make_description($displayobject->phrases['customtable_prefix']));

					foreach ($not_found AS $table_name)
					{
						$return_string .= $displayobject->update_html($displayobject->make_description('<span class="ifail">' . $table_name . ' ' . $displayobject->phrases['not_found'] . '.</span>'));
					}
				}

				if ($prefix_poss)
				{
					krsort($prefix_poss, SORT_NUMERIC);

					if (end($prefix_poss) > count($found_tables))
					{
						$return_string .= $displayobject->update_html($displayobject->make_description('<span><b>' . $displayobject->phrases['all_red_tables'] . '</b></span><br /><br />'));
						// Possible table prefix
						// Sort to get the most common found one
						$return_string .= $displayobject->update_html($displayobject->make_description(key($prefix_poss)));
					}
				}

				$return_string .= $displayobject->update_html($displayobject->table_footer(2, '', '', false));

				return array(
					'code'	=>	$code,
					'text'	=>	$return_string
				);

			}

			// MS-SQL database
			case 'mssql':
			{
				$tables = $Db_object->query("
					SELECT TABLE_NAME
					FROM INFORMATION_SCHEMA.TABLES
					WHERE TABLE_TYPE = 'BASE TABLE'
				");

				while ($table = $Db_object->fetch_array($tables))
				{
					if (in_array($table['TABLE_NAME'], $valid_tables))
					{
						$return_string .= '<br /><span class="isucc">' . $table[key($table)] . ' ' . $displayobject->phrases['found'] . '.</span>';

						// List the found ones
						$found_tables[] = $table[key($table)];
					}
				}

				$not_found = array_diff($valid_tables, $found_tables);

				foreach ($not_found AS $table_name)
				{
					$return_string .= '<br /><span class="ifail">' . $table_name . ' ' . $displayobject->phrases['not_found'] . '.</span>';
				}

				return array(
					'code'	=>	true,
					'text'	=>	$return_string
				);
			}

			// other
			default:
			{
				return false;
			}
		}
	}

	/**
	* Checks a specific table.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	*/
	public function check_table($Db_object, $databasetype, $tableprefix, $table_name, &$displayobject, $req_fields = false)
	{
		$tables = $Db_object->query("
			SHOW TABLES
		");

		while ($table = $Db_object->fetch_array($tables))
		{
			if ($table[key($table)] == $tableprefix . $table_name)
			{
				// Check if there are required fields
				if ($req_fields AND is_array($req_fields))
				{
					$src_fields = $Db_object->query("
						DESCRIBE " . $tableprefix . $table_name . "
					");

					$key_array = array();

					while ($src_field = $Db_object->fetch_array($src_fields))
					{
						if ($req_fields[$src_field['Field']])
						{
							unset($req_fields[$src_field['Field']]);
						}
					}

					// if any that were required wern't unset, they aren't there
					if (count($req_fields) > 0)
					{
						$string = '';
						$string .= '<br />' . $displayobject->phrases['halted_missing_fields_db'];
						$string .= '<br />';
						$string .= '<br />';
						$string .= '<list>';

						foreach ($req_fields as $missing => $o)
						{
							$string .= "<li>" . $tableprefix . $table_name . ".<b>" . $missing . "</b></li>";
						}

						$string .= "</list>";
						$string .= '<br />' . $displayobject->phrases['repair_source_db'];
						$string .= "</body>";
						$string .= "</html>";

						echo $string;
						exit();
					}
					else
					{
						// all found
						return true;
					}
				}
				else
				{
					return true;
				}
			}
		}
		return false;
	}

	/**
	* Checks for a smilie text
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc.
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'.
	* @param	string	mixed			array( title => '', smilietext => '', smiliepath => '')
	*
	* @return	boolean
	*/
	public function does_smilie_exists($Db_object, $databasetype, $tableprefix, $smilie)
	{
		return $Db_object->query_first("
			SELECT smilieid
			FROM " . $tableprefix . "smilie
			WHERE smilietext = '" . $Db_object->escape_string($smilie['smilietext']) . "'
		");
	}

	/**
	* Returns the attachment_id => attachment array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	int		mixed			Start point
	* @param	int		mixed			End point
	* @param	string	mixed			Productid of product that owns the attachment
	* @param	string	mixed			Class of the content that owns the attachment
	*
	* @return	array
	*/
	public function get_vb4_attachment_details(&$Db_object, &$databasetype, &$tableprefix, $start_at, $per_page, $productid, $class)
	{
		$return_array = array();

		// Check that there is not an empty value
		if (empty($per_page))
		{
			return $return_array;
		}

		// Get contenttypeid
		$contenttypeid = $this->get_contenttypeid($Db_object, $databasetype, $tableprefix, $productid, $class);

		$details_list = $Db_object->query("
			SELECT
				a.*,
				fd.filesize, fd.filedata, fd.width, fd.height, fd.filehash, fd.userid
			FROM " . $tableprefix . "attachment AS a
			INNER JOIN " . $tableprefix . "filedata AS fd ON (a.filedataid = fd.filedataid)
			WHERE
				a.contenttypeid = " . intval($contenttypeid) . "
			ORDER BY a.attachmentid
			LIMIT " . intval($start_at) . ", " . intval($per_page) . "
		");

		while ($detail = $Db_object->fetch_array($details_list))
		{
			$return_array["$detail[attachmentid]"] = $detail;
		}

		return $return_array;
	}

	/**
	* Returns the attachment_id => attachment array
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
 	* @param	string	mixed			Productid of product that owns the attachment
	* @param	string	mixed			Class of the content that owns the attachment
	*
	* @return	int		Contenttypeid
	*/
	public function get_contenttypeid(&$Db_object, &$databasetype, &$tableprefix, $productid, $class)
	{
		$contenttype = $Db_object->query_first("
			SELECT c.contenttypeid
			FROM " . $tableprefix . "contenttype AS c
				INNER JOIN " . $tableprefix . "package AS p ON (c.packageid = p.packageid)
			WHERE c.class = '" . $Db_object->escape_string($class) . "'
				AND p.productid = '" . $Db_object->escape_string($productid) . "'
		");

		return intval($contenttype['contenttypeid']);
	}

	/**
	* Returns the packageid
	*
	* @param	object	databaseobject	The database object to run the query against
	* @param	string	mixed			Table database type
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
 	* @param	string	mixed			Productid of product 
	* @param	string	mixed			Class of the content
	*
	* @return	int		Packageid
	*/
	public function get_packageid(&$Db_object, &$databasetype, &$tableprefix, $productid, $class)
	{
		$package = $Db_object->query_first("
			SELECT packageid
			FROM " . $tableprefix . "package
			WHERE productid = '" . $Db_object->escape_string($productid) . "'
				AND class = '" . $Db_object->escape_string($class) . "'
		");

		return $package['packageid'];
	}
}

class ImpExCache
{
	var $Db 		= null;
	var $db_type 	= null;
	var $prefix 	= null;

	var $postid_array 			= array();
	var $userid_array 			= array();
	var $username_array 		= array();
	var $threadid_array 		= array();
	var $usernametoid_array		= array();
	var $blogid_array			= array();
	var $blogcatid_array		= array();
	var $cmscatid_array			= array();
	var $threadandforumid_array = array();
	var	$cmsgrid_array			= array();
	var $cmslayout_array		= array();
	var $cmsnode_array			= array();

	/**
	* Constructor
	*/
	public function __construct($Db, $db_type, $prefix)
	{
		$this->Db 		=& $Db;
		$this->db_type 	=& $db_type;
		$this->prefix 	=& $prefix;
	}

	/**
	* Returns the desired id.
	*/
	public function get_id($type, $importid, $forum=null)
	{
		if (!$importid)
		{
			return 0;
		}

		$type = strtolower($type);

		switch ($type)
		{
			case 'user':
			{
				// Already guest
				if ($importid == 0)
				{
					return 0;
				}

				if (!$this->userid_array[$importid])
				{
					$data = $this->Db->query_first("
						SELECT userid
						FROM ". $this->prefix ."user
						WHERE importuserid = " . intval($importid) . "
					");

					$this->userid_array[$importid] = $data['userid'];

					// Guest
					if (!$this->userid_array[$importid])
					{
						return 0;
					}
				}

				return $this->userid_array[$importid];

				break;
			}

			case 'username':
			{
				// Already guest
				if ($importid == 0)
				{
					return "Guest";
				}

				if (!$this->username_array[$importid])
				{
					$data = $this->Db->query_first("
						SELECT username
						FROM ". $this->prefix ."user
						WHERE importuserid = " . intval($importid) . "
					");

					$this->username_array[$importid] = $data['username'];
				}

				return $this->username_array[$importid];

				break;
			}

			case 'usernametoid':
			{
				// Already guest
				if (strtolower($importid) == 'guest')
				{
					return "0";
				}

				if (!$this->usernametoid_array["$importid"])
				{
					$data = $this->Db->query_first("
						SELECT userid
						FROM ". $this->prefix . "user
						WHERE username = '" . $Db_object->escape_string($importid) . "'
					");

					$this->usernametoid_array[$importid] = $data['userid'];
				}

				return $this->usernametoid_array[$importid];

				break;
			}

			case 'thread':
			{
				if (!$this->threadid_array[$importid])
				{
					$data = $this->Db->query_first("
						SELECT threadid
						FROM ". $this->prefix ."thread
						WHERE importthreadid = " . intval($importid) . "
					");
					$this->threadid_array[$importid] = $data['threadid'];
				}

				return $this->threadid_array[$importid];

				break;
			}

			case 'threadandforum':
			{
				if (!$this->threadandforumid_array[$forum][$importid])
				{
					$data = $this->Db->query_first("
						SELECT threadid
						FROM ". $this->prefix ."thread
						WHERE importthreadid = " . intval($importid) . "
							AND importforumid = " . intval($forum) . "
					");

					$this->threadandforumid_array[$forum][$importid] = $data['threadid'];
				}

				return $this->threadandforumid_array[$forum][$importid];

				break;
			}

			case 'post':
			{
				if (!$this->postid_array[$importid])
				{
					$data = $this->Db->query_first("
						SELECT postid
						FROM ". $this->prefix ."post
						WHERE importpostid = " . intval($importid) . "
					");

					$this->postid_array[$importid] = $data['postid'];
				}

				return $this->postid_array[$importid];

				break;
			}

			case 'blog':
			{
				if (!$this->blogid_array[$importid])
				{
					$this->Db->reporterror = 0;

					$data = $this->Db->query_first("
						SELECT blogid
						FROM " . $this->prefix . "blog
						WHERE importblogid = " . intval($importid) . "
					");

					$this->Db->reporterror = 1;
					$this->blogid_array[$importid]= $data['blogid'];
				}

				return $this->blogid_array[$importid];

				break;
			}

			case 'blogcategory':
			{
				if (!$this->blogcatid_array[$importid])
				{
					$data = $this->Db->query_first("
						SELECT blogcategoryid
						FROM ". $this->prefix ."blog_category
						WHERE importblogcategoryid = " . intval($importid) . "
					");

					$this->blogcatid_array[$importid]= $data['blogcategoryid'];
				}

				return $this->blogcatid_array[$importid];

				break;
			}

			case 'cmscategory':
			{
				if (!$this->cmscatid_array[$importid])
				{
					$data = $this->Db->query_first("
						SELECT categoryid
						FROM ". $this->prefix ."cms_category
						WHERE importcmscategoryid = " . intval($importid) . "
					");

					$this->cmscatid_array[$importid] = $data['categoryid'];
				}

				return $this->cmscatid_array[$importid];

				break;
			}

			case 'grid':
			{
				if (!$this->cmsgrid_array[$importid])
				{
					$data = $this->Db->query_first("
						SELECT gridid
						FROM " . $this->prefix . "cms_grid
						WHERE importcmsgridid = " . intval($importid) . "
					");

					$this->cmsgrid_array[$importid] = $data['gridid'];
				}
				return $this->cmsgrid_array[$importid];
				
				break;
			}

			case 'layout':
			{
				if (!$this->cmslayout_array[$importid])
				{
					$data = $this->Db->query_first("
						SELECT layoutid
						FROM " . $this->prefix . "cms_layout
						WHERE importcmslayoutid = " . intval($importid)  ."
					");

					$this->cmslayout_array[$importid] = $data['layoutid'];
				}
				return $this->cmslayout_array[$importid];

				break;
			}

			case 'cmsnode':
			{
				if (!$this->cmsnode_array[$importid])
				{
					$data = $this->Db->query_first("
						SELECT nodeid
						FROM " . $this->prefix . "cms_node
						WHERE importcmsnodeid = " . intval($importid) . "
					");

					$this->cmsnode_array[$importid] = $data['nodeid'];
				}
				return $this->cmsnode_array[$importid];

				break;
			}

			default:
			{
				return "0";
			}
		}
	}

	/**
	* Imports the users avatar from a local file or URL including saving the new avatar and optionally assigning it to a user.
	*
	* @param	object	databaseobject	The database that the function is going to interact with.
	* @param	string	mixed			The type of database 'mysql', 'mysqli', etc
	* @param	string	mixed			The prefix to the table name i.e. 'vb3_'
	* @param	string	int		The userid
	* @param	string	int		The categoryid for avatars
	* @param	string	int		The source file name
	* @param	string	int		The target file name (i.e. the file to be created)
	*
	* @return	insert_id
	*/
	public function copy_avatar(&$Db_object, &$databasetype, $tableprefix, $sourcefile, &$displayobject)
	{
		// If we have already imported this avatar, we just need to assign it.
		$avatar_qry = $Db_object->query("
			SELECT avatarid
			FROM " . TABLE_PREFIX . "avatar
			WHERE importavatarid = " . intval($this->get_value('nonmandatory', 'iiconid')) . "
		");

		if ($avatar_info = $Db_object->fetch_array($avatar_qry))
		{
			if ($avatar_info['avatarid'])
			{
				return $avatar_info['avatarid'];
			}
		}

		// first we need to save the file.
		if (file_exists($targetfile))
		{
			return construct_phrase($displayobject->phrases['file_already_exists_select_target'], $targetfile) . '<br />';
		}

		$file_contents = $this->vb_file_get_contents($sourcefile);

		if (!$file_contents)
		{
			return construct_phrase($displayobject->phrases['file_missing_empty_hidden'], $sourcefile) . '<br />';
		}

		if (!vb_file_save_contents($filename, $contents))
		{
			return $displayobject->phrases['save_file_failed'] . '<br />';
		}

		$Db_object->query_write("
			INSERT INTO " . TABLE_PREFIX . "avatar
				(title,
				minimumposts,
				avatarpath,
				imagecategoryid,
				displayorder,
				importavatarid)
			VALUES
				('" . $Db_object->escape_string($this->get_value('nonmandatory', 'title')) . "',
				0,
				'" . $Db_object->escape_string($this->get_value('nonmandatory', 'avatarpath')) . "',
				" . intval($this->get_value('mandatory', 'imagecategoryid'))  . ",
				1,
				" . intval($this->get_value('mandatory', 'importavatarid'))  . ")
		");

		$avatarid = $Db_object->insert_id();
		return $avatarid;
	}

	/**
	* Returns the ID of the updated avatar.
	*/
	public function assignAvatar(&$Db_object, &$databasetype, &$tableprefix, $userid, $avatarid)
	{
		if (!intval($userid) OR !intval($avatarid))
		{
			return false;
		}

		// We have an avatarid. Now we just need to assign to the user
		$Db_object->query("
			UPDATE " . $tableprefix . "user SET
				avatarid = " . intval($avatarid) . "
			WHERE userid = " . intval($userid) . "
		");

		return $Db_object->insert_id();
	}
}

?>