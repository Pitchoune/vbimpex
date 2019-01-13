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
* vb2_004 Import User module
*
* @package			ImpEx.vb2
*/
class vb2_004 extends vb2_000
{
	var $_dependent = '003';

	public function __construct(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_users'];
	}

	public function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source, $resume = false)
	{
		if ($this->check_order($sessionobject, $this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_users'))
				{
					$displayobject->update_html($displayobject->table_header());
					$displayobject->update_html($displayobject->make_table_header($this->_modulestring));
					$displayobject->update_html($displayobject->make_description($displayobject->phrases['users_cleared']));
					$displayobject->update_html($displayobject->table_footer());
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error(substr(get_class($this), -3), $displayobject->phrases['user_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title', $displayobject->phrases['import_user']);
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this), -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this), -3), 'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['users_per_page'], 'userperpage', 500));
			$displayobject->update_html($displayobject->make_yesno_code($displayobject->phrases['email_match'], 'email_match', 0));

			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'], $displayobject->phrases['reset']));

			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('userstartat', '0');
			$sessionobject->add_session_var('modulestring', $this->_modulestring);
		}
		else
		{
			// Dependant has not been run
			$displayobject->update_html($displayobject->do_form_header('index', ''));
			$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['dependency_error']));
			$displayobject->update_html($displayobject->make_description('<p>' . $displayobject->phrases['dependant_on'] . '<i><b> ' . $sessionobject->get_module_title($this->_dependent) . '</b> ' . $displayobject->phrases['cant_run'] . '</i>.'));
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],''));
			$sessionobject->set_session_var(substr(get_class($this), -3), 'FALSE');
			$sessionobject->set_session_var('module', '000');
		}
	}

	public function resume(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		// Turn off the modules display
		$displayobject->update_basic('displaymodules', 'FALSE');

		// Get some more usable local vars
		$target_database_type	= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix	= $sessionobject->get_session_var('targettableprefix');
		$source_database_type	= $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix	= $sessionobject->get_session_var('sourcetableprefix');
		$modulestring			= $sessionobject->get_session_var('modulestring');

		// Per page vars
		$user_start_at			= $sessionobject->get_session_var('userstartat');
		$user_per_page			= $sessionobject->get_session_var('userperpage');
		$class_num				= substr(get_class($this), -3);

		// Start the timing
		if (!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num, 'start', $sessionobject->get_session_var('autosubmit'));
		}

		if (intval($user_per_page) == 0)
		{
			$user_per_page = 30;
		}

		// Get an array of user details
		$source_data_array 	= $this->get_source_data($Db_source, $source_database_type, 'user', 'userid', null, $user_start_at, $user_per_page, $displayobject);

		// Groups info
		$user_group_ids_array = $this->get_imported_group_ids($Db_target, $target_database_type, $target_table_prefix);

		// Display count and pass time
		$displayobject->update_html($displayobject->table_header());
		$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['import_users']));

		$displayobject->update_html($displayobject->print_per_page_pass(count($source_data_array['data']), $displayobject->phrases['users_lower'], $user_start_at));

		$user_object = new ImpExData($Db_target, $sessionobject, 'user');

		if ($source_data_array['data'])
		{
			foreach ($source_data_array['data'] AS $user_id => $user_details)
			{
				$try = (phpversion() < '5' ? $user_object : clone($user_object));

				// Auto associate
				if ($sessionobject->get_session_var('email_match'))
				{
					$try->_auto_email_associate = true;
				}

				// Mandatory
				$try->set_value('mandatory', 'usergroupid',				$user_group_ids_array["$user_details[usergroupid]"]);
				$try->set_value('mandatory', 'username',				$user_details['username']);
				$try->set_value('mandatory', 'email',					addslashes($user_details['email']));
				$try->set_value('mandatory', 'importuserid',			$user_details['userid']);

				// Non Mandatory
				$try->_password_md5_already = true;
				$try->set_value('nonmandatory', 'password',				$user_details['password']);
				$try->set_value('nonmandatory', 'passworddate',			time());
				$try->set_value('nonmandatory', 'styleid',				$user_details['styleid']);
				$try->set_value('nonmandatory', 'parentemail',			$user_details['parentemail']);
				$try->set_value('nonmandatory', 'homepage',				$user_details['homepage']);
				$try->set_value('nonmandatory', 'icq',					$user_details['icq']);
				$try->set_value('nonmandatory', 'aim',					$user_details['aim']);
				$try->set_value('nonmandatory', 'yahoo',				$user_details['yahoo']);
				$try->set_value('nonmandatory', 'usertitle',			$user_details['usertitle']);
				$try->set_value('nonmandatory', 'customtitle',			$user_details['customtitle']);
				$try->set_value('nonmandatory', 'joindate',				$user_details['joindate']);
				$try->set_value('nonmandatory', 'daysprune',			$user_details['daysprune']);
				$try->set_value('nonmandatory', 'lastvisit',			$user_details['lastvisit']);
				$try->set_value('nonmandatory', 'lastactivity',			$user_details['lastactivity']);
				$try->set_value('nonmandatory', 'lastpost',				$user_details['lastpost']);
				$try->set_value('nonmandatory', 'posts',				$user_details['posts']);
				$try->set_value('nonmandatory', 'reputation',			$user_details['reputation']);
				$try->set_value('nonmandatory', 'reputationlevelid',	$user_details['reputationlevelid']);
				$try->set_value('nonmandatory', 'timezoneoffset',		$user_details['timezoneoffset']);
				$try->set_value('nonmandatory', 'pmpopup',				$user_details['pmpopup']);

				// Will need default mappings
				$options = 3159;

				if ($user_details['coppauser'])
				{
						$options -= 8;
				}

				$try->set_value('nonmandatory', 'options',				$options);

				list($y, $m, $d) = explode ('-', $user_details['birthday']);
				$try->set_value('nonmandatory', 'birthday',				$m . "-" . $d . "-" . $y);
				$try->set_value('nonmandatory', 'birthday_search',		$user_details['birthday']);
				$try->set_value('nonmandatory', 'maxposts',				$user_details['maxposts']);
				$try->set_value('nonmandatory', 'startofweek',			$user_details['startofweek']);
				$try->set_value('nonmandatory', 'ipaddress',			$user_details['ipaddress']);
				$try->set_value('nonmandatory', 'referrerid',			$done_users["$user_details[referrerid]"]);
				$try->set_value('nonmandatory', 'msn',					$user_details['msn']);
				$try->set_value('nonmandatory', 'emailstamp',			$user_details['emailstamp']);
				$try->set_value('nonmandatory', 'threadedmode',			$user_details['threadedmode']);
				$try->set_value('nonmandatory', 'pmtotal',				$user_details['pmtotal']);
				$try->set_value('nonmandatory', 'pmunread',				$user_details['pmunread']);
				$try->set_value('nonmandatory', 'autosubscribe',		$user_details['autosubscribe']);

				$try->add_default_value('signature', 					addslashes($user_details['signature']));

				// Check if user object is valid
				if ($try->is_valid())
				{
					if ($try->import_user($Db_target, $target_database_type, $target_table_prefix))
					{
						$displayobject->update_html($displayobject->make_description('<span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['user'] . ' -> ' . $try->get_value('mandatory', 'username')));
						$sessionobject->add_session_var($class_num . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1);
					}
					else
					{
						$sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num. '_objects_failed') + 1);
						$sessionobject->add_error($user_id, $displayobject->phrases['user_not_imported'], $displayobject->phrases['user_not_imported_rem']);
						$displayobject->update_html($displayobject->make_description($displayobject->phrases['failed'] . ' :: ' . $displayobject->phrases['user_not_imported']));
					}
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num. '_objects_failed') + 1);
					$displayobject->update_html($displayobject->make_description($displayobject->phrases['invalid_object'] . $try->_failedon));
				}
				unset($try);
			}
		}
		else
		{
			$displayobject->update_html($displayobject->make_description($displayobject->phrases['no_user_import']));
		}

		$displayobject->update_html($displayobject->table_footer());

		if (count($source_data_array['data']) == 0 OR count($source_data_array['data']) < $user_per_page)
		{	
			$this->build_user_statistics($Db_target, $target_database_type, $target_table_prefix);

			$sessionobject->timing($class_num, 'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$displayobject->update_html($displayobject->module_finished($modulestring,
				$sessionobject->return_stats($class_num, '_time_taken'),
				$sessionobject->return_stats($class_num, '_objects_done'),
				$sessionobject->return_stats($class_num, '_objects_failed')
			));

			$sessionobject->set_session_var($class_num, 'FINISHED');
			$sessionobject->set_session_var('module', '000');
			$sessionobject->set_session_var('autosubmit', '0');
			$displayobject->update_html($displayobject->print_redirect_001('index.php'));
		}
		else
		{
			$sessionobject->set_session_var('userstartat', $user_start_at + $user_per_page);
			$displayobject->update_html($displayobject->print_redirect_001('index.php'));
		}
	}
}

?>