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
* vb2_001 Associate Users
*
* @package			ImpEx.vb2
*/
class vb2_002 extends vb2_000
{
	var $_dependent = '001';

	public function __construct(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['associate_users']; 
	}

	public function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source, $resume = false)
	{
		$proceed = $this->check_order($sessionobject, $this->_dependent);

		if ($proceed)
		{
			if($sessionobject->get_session_var('associateperpage') == 0)
			{
				$sessionobject->add_session_var('associateperpage', '25');
			}

			$displayobject->update_basic('title', $displayobject->phrases['associate_users']);
			$displayobject->update_html($displayobject->do_form_header('index','002'));
			$displayobject->update_html($displayobject->make_hidden_code('002','WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('associateusers','1'));
			$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['associate_users']));
			$displayobject->update_html($displayobject->make_description($displayobject->phrases['assoc_desc_1'] . ' ' . $displayobject->phrases['assoc_desc_2']));
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'], $displayobject->phrases['quit']));

			$sessionobject->add_session_var('doassociate', '0');
			$sessionobject->add_session_var('associatestartat', '0');
			$sessionobject->add_session_Var('modulestring', $this->modulestring);
		}
		else
		{
			// Dependant has not been run
			$displayobject->update_html($displayobject->do_form_header('index', ''));
			$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['dependency_error']));
			$displayobject->update_html($displayobject->make_description('<p>' . $displayobject->phrases['dependant_on'] . '<i><b> ' . $sessionobject->get_module_title($this->_dependent) . '</b> ' . $displayobject->phrases['cant_run'] . '</i> .'));
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'], $displayobject->phrases['reset']));
			$sessionobject->set_session_var(substr(get_class($this), -3), 'FALSE');
			$sessionobject->set_session_var('module', '000');
		}
	}

	public function resume(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		// Turn off the modules display
		$displayobject->update_basic('displaymodules', 'FALSE');

		// Get some more usable local vars
		$associate_start_at		= $sessionobject->get_session_var('associatestartat');
		$associate_per_page		= $sessionobject->get_session_var('associateperpage');
		$target_database_type	= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix	= $sessionobject->get_session_var('targettableprefix');
		$source_database_type	= $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix	= $sessionobject->get_session_var('sourcetableprefix');
		$modulestring			= $sessionobject->get_session_var('modulestring');

		// Get some usable variables
		$associate_users		= 	$sessionobject->get_session_var('associateusers');
		$do_associate			=	$sessionobject->get_session_var('doassociate');
		$class_num				= 	substr(get_class($this) , -3);

		// Start the timing
		if (!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num, 'start', $sessionobject->get_session_var('autosubmit'));
		}

		//	List from the start_at number
		if ($associate_users == 1)
		{
			// Get a list of the vb2 members in this current selection
			$userarray = $this->get_vb2_members_list($Db_source, $source_database_type, $source_table_prefix, $associate_start_at, $associate_per_page);

			// Build a list of the vB2 users with a box to enter a vB user id into
			$displayobject->update_html($displayobject->do_form_header('index', '002'));
			$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['assoc_list']));
			$displayobject->update_html($displayobject->make_description($displayobject->phrases['assoc_match']));

			// Set up list variables
			$any_more = false;
			$counter  = 1;

			// Build the list
			foreach ($userarray AS $userid => $username)
			{
				$displayobject->update_html($displayobject->make_input_code($counter . ') ' . $displayobject->phrases['user_id'] . ' - ' . $userid . ' :: ' . $username, 'user_to_ass_' . $userid, '', 10));
				$any_more = true;
				$counter++;
			}

			// If there are not any more, tell the user and quit out for them.
			if ($counter == 1)
			{
				$displayobject->update_html($displayobject->make_description($displayobject->phrases['no_users']));
				$displayobject->update_html($displayobject->table_footer());
			}
			else
			{
				$sessionobject->set_session_var('associatestartat', $associate_start_at + $associate_per_page);
			}

			if ($counter > 1)
			{
				// Continue with the association
				$sessionobject->set_session_var('associateusers', '0');
				$sessionobject->set_session_var('doassociate', '1');
				$displayobject->update_html($displayobject->make_hidden_code('doassociate', '1'));
				$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['associate'], ''));
			}

			// Quit button
			$displayobject->update_html($displayobject->do_form_header('index', '002'));
			$displayobject->update_html($displayobject->make_hidden_code('associateusers', '2'));
			$displayobject->update_html($displayobject->make_hidden_code('doassociate', '0'));
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['quit'], ''));
		}

		//	If there are some to associate
		if ($do_associate == 1)
		{
			$displayobject->update_html($displayobject->table_header());
			$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['associate_users']));
			$displayobject->update_html($displayobject->make_description('<p align="center">' . $displayobject->phrases['associating_users'] . '</p>'));

			$users_to_associate = $sessionobject->get_users_to_associate();

			foreach ($users_to_associate AS $key => $value)
			{
				// Getting username for display
				$userid = substr(key($value), 12);
				$username = $this->get_one_username($Db_source, $source_database_type, $source_table_prefix, $userid, 'userid');

				if ($this->associate_user($Db_target, $target_database_type, $target_table_prefix, substr(key($value),12),  current($value)))
				{
					$displayobject->update_html($displayobject->display_now('<p align="center">' . $displayobject->phrases['associating_user_1'] . '  ' . $username . $displayobject->phrases['associating_user_2'] . ' ' .  substr(key($value), 12) . $displayobject->phrases['associating_user_3'] . current($value) . ' - ' . $displayobject->phrases['successful'] . '.</p>'));
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1);
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num . '_objects_failed') + 1);
					$displayobject->update_html($displayobject->display_now('<p align="center">' . $displayobject->phrases['associating_user_1'] . '  ' . $username . $displayobject->phrases['associating_user_2'] . ' ' .  substr(key($value), 12) . $displayobject->phrases['associating_user_3'] . current($value) . ' - ' . $displayobject->phrases['failed'] . ' .</p>'));
				}
			}

			$sessionobject->delete_users_to_associate();

			// Continue with the association
			$sessionobject->set_session_var('associateusers', '1');
			$sessionobject->set_session_var('doassociate', '0');
			$displayobject->update_html($displayobject->make_description('<p align="center">' . $displayobject->phrases['continue'] . '. ' . $displayobject->phrases['redirecting'] . '</p>'));
			$displayobject->update_html($displayobject->table_footer());
			$displayobject->update_html($displayobject->print_redirect('index.php', $sessionobject->get_session_var('pagespeed')));
			$displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
		}

		//	Finish the module
		if ($associate_users == 2)
		{
			$displayobject->update_html($displayobject->table_header());
			$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['associate_users']));
			$displayobject->update_html($displayobject->make_description('<p align="center">' . $displayobject->phrases['completed'] . '. ' . $displayobject->phrases['redirecting'] . '</p>'));
			$displayobject->update_html($displayobject->table_footer());

			$sessionobject->timing($class_num, 'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');
			$sessionobject->add_session_var($class_num . '_objects_done', intval($counter));

			$displayobject->update_html($displayobject->module_finished($modulestring,
				$sessionobject->return_stats($class_num, '_time_taken'),
				$sessionobject->return_stats($class_num, '_objects_done'),
				$sessionobject->return_stats($class_num, '_objects_failed')
			));

			$sessionobject->set_session_var(substr(get_class($this), -3), 'FINISHED');
			$sessionobject->set_session_var('module', '000');
			$displayobject->update_basic('displaymodules', FALSE);

			$displayobject->update_html($displayobject->print_redirect('index.php', $sessionobject->get_session_var('pagespeed')));
		}
	}
}

?>