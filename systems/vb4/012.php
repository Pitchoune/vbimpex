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
* vb4 Import Moderators
*
* @package 		ImpEx.vb4
*/
class vb4_012 extends vb4_000
{
	var $_dependent 	= '007';

	public function __construct(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_moderators'];
	}

	public function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source, $resume = false)
	{
		if ($this->check_order($sessionobject, $this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_moderators'))
				{
					$displayobject->update_html($displayobject->table_header());
					$displayobject->update_html($displayobject->make_table_header($this->_modulestring));
					$displayobject->update_html($displayobject->make_description($displayobject->phrases['moderators_cleared']));
					$displayobject->update_html($displayobject->table_footer());
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error(substr(get_class($this), -3), $displayobject->phrases['moderator_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title', $displayobject->phrases['import_moderators']);
			$displayobject->update_html($displayobject->do_form_header('index', substr(get_class($this), -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this), -3), 'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['moderators_per_page'], 'moderatorperpage', 50));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'], $displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this), -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this), -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('moderatorstartat', '0');
			$sessionobject->add_session_var('modulestring', $this->_modulestring);
		}
		else
		{
			// Dependant has not been run
			$displayobject->update_html($displayobject->do_form_header('index', ''));
			$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['dependency_error']));
			$displayobject->update_html($displayobject->make_description('<p>' . $displayobject->phrases['dependant_on'] . '<i><b> ' . $sessionobject->get_module_title($this->_dependent) . '</b>' . $displayobject->phrases['cant_run'] . '</i>.'));
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'], ''));
			$sessionobject->set_session_var(substr(get_class($this), -3), 'FALSE');
			$sessionobject->set_session_var('module', '000');
		}
	}

	function resume(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		// Turn off the modules display
		$displayobject->update_basic('displaymodules', 'FALSE');

		// Get some more usable local vars
		$target_database_type 	= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix  	= $sessionobject->get_session_var('targettableprefix');
		$source_database_type 	= $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix  	= $sessionobject->get_session_var('sourcetableprefix');
		$modulestring         	= $sessionobject->get_session_var('modulestring');

		// Get some usable variables
		$moderator_start_at		= $sessionobject->get_session_var('moderatorstartat');
		$moderator_per_page		= $sessionobject->get_session_var('moderatorperpage');

		$class_num				= substr(get_class($this), -3);
		$moderator_object		= new ImpExData($Db_target, $sessionobject, 'moderator');
		$idcache				= new ImpExCache($Db_target, $target_database_type, $target_table_prefix);

		// Start the timing
		if (!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num, 'start', $sessionobject->get_session_var('autosubmit'));
		}

		if (intval($moderator_per_page) == 0)
		{
			$moderator_per_page = 200;
		}

		$moderator_array		= $this->get_details($Db_source, $source_database_type, $source_table_prefix, $displayobject, $moderator_start_at, $moderator_per_page, 'moderator', 'moderatorid');
		$forumids_array			= $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix);

		$last_pass				= $sessionobject->get_session_var('last_pass');

		// Give the user some info
		$displayobject->update_html($displayobject->table_header());
		$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['import_moderators']));

		$displayobject->update_html($displayobject->print_per_page_pass(count($moderator_array), $displayobject->phrases['moderators_lower'], $moderator_start_at));

		if ($moderator_array)
		{
			foreach ($moderator_array AS $mod_id => $details)
			{
				$try = (phpversion() < '5' ? $moderator_object : clone($moderator_object));

				// Mandatory
				# cache
				$try->set_value('mandatory', 'userid',					$idcache->get_id('user', $details['userid']));
				$try->set_value('mandatory', 'forumid',					$details['forumid'] == -1 ? -1 : $forumids_array["$details[forumid]"]);
				$try->set_value('mandatory', 'importmoderatorid',		$mod_id);

				// Non Mandatory
				$try->set_value('nonmandatory', 'permissions',			$details['permissions']);
				$try->set_value('nonmandatory', 'permissions2',			$details['permissions2']);

				if ($try->is_valid())
				{
					if ($try->import_moderator($Db_target, $target_database_type, $target_table_prefix))
					{
						$displayobject->update_html($displayobject->make_description('<span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['moderator'] . ' -> ' . $idcache->get_id('username', $details['userid'])));
						$sessionobject->add_session_var($class_num . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1);
					}
					else
					{
						$sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num . '_objects_failed') + 1);
						$sessionobject->add_error($mod_id, $displayobject->phrases['moderator_not_imported'], $displayobject->phrases['moderator_not_imported_rem']);
						$displayobject->update_html($displayobject->make_description($displayobject->phrases['failed'] . ' :: ' . $displayobject->phrases['moderator_not_imported']));
					}
				}
				else
				{
					$displayobject->update_html($displayobject->make_description($displayobject->phrases['invalid_object'] . $try->_failedon));
					$sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num . '_objects_failed') + 1);
				}
				unset($try);
			}
		}
		else
		{
			$displayobject->update_html($displayobject->make_description($displayobject->phrases['no_moderator_to_import']));
		}

		$displayobject->update_html($displayobject->table_footer());

		if (count($moderator_array) == 0 OR count($moderator_array) < $moderator_per_page)
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
			$sessionobject->set_session_var('moderatorstartat', $moderator_start_at + $moderator_per_page);
			$displayobject->update_html($displayobject->print_redirect_001('index.php'));
		}
	}
}

?>