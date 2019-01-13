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
* vb2_006 Import Thread module
*
* @package			ImpEx.vb2
*/
class vb2_006 extends vb2_000
{
	var $_dependent = '005';

	public function __construct(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_threads'];
	}

	public function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source, $resume = false)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_threads'))
				{
					$displayobject->update_html($displayobject->table_header());
					$displayobject->update_html($displayobject->make_table_header($this->_modulestring));
					$displayobject->update_html($displayobject->make_description($displayobject->phrases['threads_cleared']));
					$displayobject->update_html($displayobject->table_footer());
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error(substr(get_class($this), -3), $displayobject->phrases['thread_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title', $displayobject->phrases['import_thread']);
			$displayobject->update_html($displayobject->do_form_header('index', substr(get_class($this), -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this), -3), 'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['threads_per_page'], 'threadperpage', 2000));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'], $displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('threadstartat', '0');
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

		// Get some usable variables
		$thread_start_at		= $sessionobject->get_session_var('threadstartat');
		$thread_per_page		= $sessionobject->get_session_var('threadperpage');
		$idcache 				= new ImpExCache($Db_target, $target_database_type, $target_table_prefix);
		$class_num				= substr(get_class($this), -3);

		// Start the timing
		if (!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num, 'start', $sessionobject->get_session_var('autosubmit'));
		}

		if (intval($thread_per_page) == 0)
		{
			$thread_per_page = 150;
		}

		#$thread_array 		= $this->get_details($Db_source, $source_database_type, $source_table_prefix, $displayobject, $thread_start_at, $thread_per_page, 'thread', 'threadid');
		$source_data_array 	= $this->get_source_data($Db_source, $source_database_type, 'thread', 'threadid', null, $thread_start_at, $thread_per_page, $displayobject);
		$forum_ids_array 	= $this->get_forum_ids($Db_target, $target_database_type, $target_table_prefix);

		// Give the user some info
		$displayobject->update_html($displayobject->table_header());
		$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['import_threads']));

		$displayobject->update_html($displayobject->print_per_page_pass(count($source_data_array['data']), $displayobject->phrases['threads_lower'], $thread_start_at));

		$thread_object 			= new ImpExData($Db_target, $sessionobject, 'thread');

		if ($source_data_array['data'])
		{
			foreach ($source_data_array['data'] AS $thread_id => $thread_details)
			{
				$try = (phpversion() < '5' ? $thread_object : clone($thread_object));

				// Mandatory
				$try->set_value('mandatory', 'title',				$thread_details['title']);
				$try->set_value('mandatory', 'forumid',				$forum_ids_array["$thread_details[forumid]"]);
				$try->set_value('mandatory', 'importthreadid',		$thread_id);
				$try->set_value('mandatory', 'importforumid',		$thread_details['forumid']);

				// Non Mandatory
				$try->set_value('nonmandatory', 'lastpost',			$thread_details['lastpost']);
				$try->set_value('nonmandatory', 'pollid',			$thread_details['pollid']);
				$try->set_value('nonmandatory', 'open',				$thread_details['open']);
				$try->set_value('nonmandatory', 'replycount',		$thread_details['replycount']);
				$try->set_value('nonmandatory', 'postusername',		$thread_details['postusername']);
				$try->set_value('nonmandatory', 'postuserid',		$idcache->get_id('user', $thread_details['postuserid']));
				$try->set_value('nonmandatory', 'lastposter',		$thread_details['lastposter']);
				$try->set_value('nonmandatory', 'dateline',			$thread_details['dateline']);
				$try->set_value('nonmandatory', 'views',			$thread_details['views']);
				$try->set_value('nonmandatory', 'iconid',			$thread_details['iconid']);
				$try->set_value('nonmandatory', 'notes',			$thread_details['notes']);
				$try->set_value('nonmandatory', 'visible',			$thread_details['visible']);
				$try->set_value('nonmandatory', 'sticky',			$thread_details['sticky']);
				$try->set_value('nonmandatory', 'votenum',			$thread_details['votenum']);
				$try->set_value('nonmandatory', 'votetotal',		$thread_details['votetotal']);
				$try->set_value('nonmandatory', 'attach',			$thread_details['attach']);

				if ($try->is_valid())
				{
					if ($try->import_thread($Db_target, $target_database_type, $target_table_prefix))
					{
						$displayobject->update_html($displayobject->make_description('<span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['thread'] . ' -> ' . $try->get_value('mandatory', 'title')));
						$sessionobject->add_session_var($class_num . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1);
					}
					else
					{
						$sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num . '_objects_failed') + 1);
						$sessionobject->add_error($thread_id, $displayobject->phrases['thread_not_imported'], $displayobject->phrases['thread_not_imported_rem']);
						$displayobject->update_html($displayobject->make_description($displayobject->phrases['failed'] . ' :: ' . $displayobject->phrases['thread_not_imported']));
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
			$displayobject->update_html($displayobject->make_description($displayobject->phrases['no_thread_to_import']));
		}

		$displayobject->update_html($displayobject->table_footer());

		if (count($source_data_array['data']) == 0 OR count($source_data_array['data']) < $thread_per_page)
		{
			$displayobject->update_html($displayobject->table_header());
			$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['updating_pollids']));
			$displayobject->update_html($displayobject->make_description($displayobject->phrases['updating_pollids']));
			$this->update_poll_ids($Db_target, $target_database_type, $target_table_prefix);
			$displayobject->update_html($displayobject->table_footer());

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
			$sessionobject->set_session_var('threadstartat', $thread_start_at + $thread_per_page);
			$displayobject->update_html($displayobject->print_redirect_001('index.php'));
		}
	}
}

?>