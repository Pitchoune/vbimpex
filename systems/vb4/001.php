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
* vb4
*
* @package 		ImpEx.vb4
*/
class vb4_001 extends vb4_000
{
	public function __construct(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['check_update_db'];
	}

	public function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source, $resume = false)
	{
		$displayobject->update_basic('title', $displayobject->phrases['get_db_info']);
		$displayobject->update_html($displayobject->do_form_header('index', ''));
		$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['get_db_info']));
		$displayobject->update_html($displayobject->make_hidden_code('database', 'working'));

		$displayobject->update_html($displayobject->make_description($displayobject->phrases['check_tables']));

		$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['check_update_db'], ''));
		$sessionobject->add_session_var(substr(get_class($this), -3) . '_objects_done', '0');
		$sessionobject->add_session_var(substr(get_class($this), -3) . '_objects_failed', '0');

		$sessionobject->add_session_var('modulestring', $this->_modulestring);
	}

	public function resume(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if (!$sessionobject->get_session_var('sourceexists'))
		{
			$displayobject->display_error($displayobject->phrases['sourceexists_is_false']);
			exit;
		}

		// Turn off the modules display
		$displayobject->update_basic('displaymodules', 'FALSE');

		// Get some more usable local vars
		$target_db_type 		= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix 	= $sessionobject->get_session_var('targettableprefix');
		$source_db_type			= $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix 	= $sessionobject->get_session_var('sourcetableprefix');
		$modulestring			= $sessionobject->get_session_var('modulestring');

		// Get some usable variables
		$class_num        = substr(get_class($this), -3);
		$databasedone     = true;

		// Start the timing
		if (!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num, 'start', $sessionobject->get_session_var('autosubmit'));
		}

		$displayobject->update_basic('title', $displayobject->phrases['altering_tables']);
		$displayobject->update_html($displayobject->table_header());
		$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['altering_tables']));
		$displayobject->update_html($displayobject->make_description($displayobject->phrases['alter_desc_1'] . $displayobject->phrases['alter_desc_2'] . $displayobject->phrases['alter_desc_3'] . $displayobject->phrases['alter_desc_4']));

		// Add an importids now
		foreach ($this->_import_ids AS $id => $table_array)
		{
			foreach ($table_array AS $tablename => $column)
			{
				if ($this->add_import_id($Db_target, $target_db_type, $target_table_prefix, $tablename, $column))
				{
					$displayobject->update_html($displayobject->make_description('<b>' . $tablename . '</b> - ' . $column . ' <i>' . $displayobject->phrases['completed'] . '</i>'));
				}
				else
				{
					$sessionobject->add_error($class_num, $displayobject->phrases['table_alter_fail'], $displayobject->phrases['table_alter_fail_rem']);
				}
			}
		}

		$displayobject->update_html($displayobject->table_footer(2, '', '', false));

		// Add the importpostid for the attachment imports and the users for good measure
		$this->add_index($Db_target, $target_db_type, $target_table_prefix, 'post');
		$this->add_index($Db_target, $target_db_type, $target_table_prefix, 'user');

		// Check the database connection
		$result = $this->check_database($Db_source, $source_db_type, $source_table_prefix, $sessionobject->get_session_var('sourceexists'), $displayobject);

		if ($result['code'])
		{
			$sessionobject->timing($class_num, 'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');
			$sessionobject->add_session_var($class_num . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1);

			$displayobject->update_html($displayobject->module_finished($modulestring,
				$sessionobject->return_stats($class_num, '_time_taken'),
				$sessionobject->return_stats($class_num, '_objects_done'),
				$sessionobject->return_stats($class_num, '_objects_failed')
			));

			$sessionobject->set_session_var($class_num, 'FINISHED');
			$sessionobject->set_session_var('module', '000');
			$displayobject->update_basic('displaymodules', 'FALSE');
			$displayobject->update_html($displayobject->print_redirect_001('index.php', $sessionobject->get_session_var('pagespeed')));
		}
		else
		{
			$sessionobject->add_session_var($class_num . '_objects_failed', intval($sessionobject->get_session_var($class_num . '_objects_failed')) + 1);
			$displayobject->update_html($displayobject->make_description('' . $displayobject->phrases['failed'] . ' ' . $displayobject->phrases['check_db_permissions'] . ''));
			$sessionobject->set_session_var('001', 'FAILED');
			$sessionobject->set_session_var('module', '000');
			$displayobject->update_html($displayobject->print_redirect_001('index.php', $sessionobject->get_session_var('pagespeed')));
		}
	}
}

?>