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
* vb2_003 Import Usergroup module
*
* @package			ImpEx.vb2
*/
class vb2_003 extends vb2_000
{
	var $_dependent = '001';

	public function __construct(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_usergroups']; 
	}

	public function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source, $resume = false)
	{
		if ($this->check_order($sessionobject, $this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_usergroups'))
				{
					$displayobject->update_html($displayobject->table_header());
					$displayobject->update_html($displayobject->make_table_header($this->_modulestring));
					$displayobject->update_html($displayobject->make_description($displayobject->phrases['usergroups_cleared']));
					$displayobject->update_html($displayobject->table_footer());
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error(substr(get_class($this), -3), $displayobject->phrases['usergroup_restart_failed'], $displayobject->phrases['check_db_permissions']);				}
			}

			// Start up the table
			$displayobject->update_basic('title', $displayobject->phrases['import_usergroup']);
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this), -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this), -3), 'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_description($displayobject->phrases['usergroups_all']));
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['usergroups_per_page'], 'usergroupperpage', 50));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'], ''));

			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('usergroupstartat', '0');
			$sessionobject->add_session_var('modulestring', $this->_modulestring);
		}
		else
		{
			// Dependant has not been run
			$displayobject->update_html($displayobject->do_form_header('index', ''));
			$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['dependency_error']));
			$displayobject->update_html($displayobject->make_description('<p>' . $displayobject->phrases['dependant_on'] . '</b> ' . $sessionobject->get_module_title($this->_dependent) . '</b> ' . $displayobject->phrases['cant_run'] . '</i> .'));
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'], ''));
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

		// Get some usable vars
		$usergroup_start_at		= $sessionobject->get_session_var('usergroupstartat');
		$usergroup_per_page		= $sessionobject->get_session_var('usergroupperpage');
		$class_num				= substr(get_class($this), -3);

		// Start the timing
		if (!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num, 'start', $sessionobject->get_session_var('autosubmit'));
		}

		// Get all the user groups
		$usergroup_array = $this->get_details($Db_source, $source_database_type, $source_table_prefix, $displayobject, $usergroup_start_at, $usergroup_per_page, 'usergroup', 'usergroupid');

		// Display count and pass time
		$displayobject->update_html($displayobject->table_header());
		$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['importing'] . ' ' . $displayobject->phrases['usergroups']));

		$displayobject->update_html($displayobject->print_per_page_pass(count($usergroup_array), $displayobject->phrases['usergroups_lower'], $usergroup_start_at));

		$usergroup_object = new ImpExData($Db_target, $sessionobject, 'usergroup');

		// Do the user group array
		if ($usergroup_array)
		{
			foreach ($usergroup_array AS $usergroup_id => $usergroup_details)
			{
				$try = (phpversion() < '5' ? $usergroup_object : clone($usergroup_object));

				// Mandatory
				$try->set_value('mandatory', 'importusergroupid',		$usergroup_details['usergroupid']);

				// Non Mandatory
				$try->set_value('nonmandatory', 'title',				'ImpEx - ' . $usergroup_details['title']);
				$try->set_value('nonmandatory', 'description',			$usergroup_details['title'] . ' description');
				$try->set_value('nonmandatory', 'usertitle',			$usergroup_details['usertitle']);
				$try->set_value('nonmandatory', 'passwordexpires',		$usergroup_details['passwordexpires']);
				$try->set_value('nonmandatory', 'passwordhistory',		$usergroup_details['passwordhistory']);
				$try->set_value('nonmandatory', 'pmquota',				$usergroup_details['pmquota']);
				$try->set_value('nonmandatory', 'pmsendmax',			$usergroup_details['pmsendmax']);
				$try->set_value('nonmandatory', 'pmforwardmax',			$usergroup_details['maxforwardpm']);
				$try->set_value('nonmandatory', 'opentag',				$usergroup_details['opentag']);
				$try->set_value('nonmandatory', 'closetag',				$usergroup_details['closetag']);
				$try->set_value('nonmandatory', 'canoverride',			$usergroup_details['canoverride']);
				$try->set_value('nonmandatory', 'ispublicgroup',		$usergroup_details['ispublicgroup']);
				$try->set_value('nonmandatory', 'forumpermissions',		$usergroup_details['forumpermissions']);
				$try->set_value('nonmandatory', 'pmpermissions',		$usergroup_details['pmpermissions']);
				$try->set_value('nonmandatory', 'calendarpermissions',	$usergroup_details['calendarpermissions']);
				$try->set_value('nonmandatory', 'wolpermissions',		$usergroup_details['wolpermissions']);
				$try->set_value('nonmandatory', 'adminpermissions',		$usergroup_details['adminpermissions']);
				$try->set_value('nonmandatory', 'genericpermissions',	$usergroup_details['genericpermissions']);
				$try->set_value('nonmandatory', 'genericoptions',		$usergroup_details['genericoptions']);
				$try->set_value('nonmandatory', 'pmpermissions_bak',	$usergroup_details['pmpermissions_bak']);
				$try->set_value('nonmandatory', 'attachlimit',			$usergroup_details['attachlimit']);
				$try->set_value('nonmandatory', 'avatarmaxwidth',		$usergroup_details['avatarmaxwidth']);
				$try->set_value('nonmandatory', 'avatarmaxheight',		$usergroup_details['avatarmaxheight']);
				$try->set_value('nonmandatory', 'avatarmaxsize',		$usergroup_details['avatarmaxsize']);
				$try->set_value('nonmandatory', 'profilepicmaxwidth',	$usergroup_details['profilepicmaxwidth']);
				$try->set_value('nonmandatory', 'profilepicmaxheight',	$usergroup_details['profilepicmaxheight']);
				$try->set_value('nonmandatory', 'profilepicmaxsize',	$usergroup_details['profilepicmaxsize']);

				if ($try->is_valid())
				{
					if ($try->import_usergroup($Db_target, $target_database_type, $target_table_prefix))
					{
						$displayobject->update_html($displayobject->make_description('<span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['usergroup'] . ' -> ' . $usergroup_details['title']));
						$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1);
					}
					else
					{
						$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1);
						$sessionobject->add_error($usergroup_id, $displayobject->phrases['usergroup_not_imported'], $displayobject->phrases['usergroup_not_imported_rem']);
						$displayobject->update_html($displayobject->make_description($displayobject->phrases['failed'] . ' :: ' . $displayobject->phrases['usergroup_not_imported']));
					}
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num . '_objects_failed') + 1);
					$displayobject->update_html($displayobject->make_description($displayobject->phrases['invalid_object'] . $try->_failedon));
				}
				unset($try);
			}
		}
		else
		{
			$displayobject->update_html($displayobject->make_description($displayobject->phrases['no_usergroup_to_import']));
		}

		$displayobject->update_html($displayobject->table_footer());

		// Check for page end
		if (count($usergroup_array) == 0 OR count($usergroup_array) < $usergroup_per_page)
		{
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
			$sessionobject->set_session_var('usergroupstartat', $usergroup_start_at + $usergroup_per_page);
			$displayobject->update_html($displayobject->print_redirect_001('index.php'));
		}
	}
}

?>