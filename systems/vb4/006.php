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
* vb4 Import Avatars
*
* @package 		ImpEx.vb4
*/
class vb4_006 extends vb4_000
{
	var $_dependent = '004';

	public function __construct(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_cust_pics'];
	}

	public function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source, $resume = false)
	{
		$proceed = $this->check_order($sessionobject, $this->_dependent);

		if ($proceed)
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_custom_pics'))
				{
					$displayobject->update_html($displayobject->table_header());
					$displayobject->update_html($displayobject->make_table_header($this->_modulestring));
					$displayobject->update_html($displayobject->make_description($displayobject->phrases['cust_pic_cleared']));
					$displayobject->update_html($displayobject->table_footer());
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error(substr(get_class($this), -3), $displayobject->phrases['custom_profile_pic_restart_failed'], $displayobject->phrases['check_db_permissions']);

				}
			}

			// Start up the table
			$displayobject->update_basic('title', $displayobject->phrases['import_cust_pic']);
			$displayobject->update_html($displayobject->do_form_header('index', substr(get_class($this), -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this), -3), 'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['cust_pics_per_page'], 'custompicsperpage', 50));

			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'], $displayobject->phrases['reset']));

			$sessionobject->add_session_var(substr(get_class($this), -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this), -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('custompicsstartat', '0');
			$sessionobject->add_session_var('modulestring', $this->_modulestring);
		}
		else
		{
			// Dependant has not been run
			$displayobject->update_html($displayobject->do_form_header('index', ''));
			$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['dependency_error']));
			$displayobject->update_html($displayobject->make_description('<p>' . $displayobject->phrases['dependant_on'] . '<i><b>' . $sessionobject->get_module_title($this->_dependent) . '</b>' . $displayobject->phrases['cant_run'] . '</i>.'));
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'], ''));
			$sessionobject->set_session_var(substr(get_class($this) , -3), 'FALSE');
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
		$custom_pics_start_at	= $sessionobject->get_session_var('custompicsstartat');
		$custom_pics_per_page	= $sessionobject->get_session_var('custompicsperpage');
		$class_num				= substr(get_class($this), -3);
		$idcache 				= new ImpExCache($Db_target, $target_database_type, $target_table_prefix);

		// Start the timing
		if (!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num, 'start', $sessionobject->get_session_var('autosubmit'));
		}

		if (intval($custom_pics_per_page) == 0)
		{
			$custom_pics_per_page = 20;
		}

		$customprofilepic_array = $this->get_details($Db_source, $source_database_type, $source_table_prefix, $displayobject, $custom_pics_start_at, $custom_pics_per_page, 'customprofilepic', 'userid');

		$customprofilepic_object = new ImpExData($Db_target, $sessionobject, 'customprofilepic');

		// Give the user some info
		$displayobject->update_html($displayobject->table_header());
		$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['import_cust_pics']));

		$displayobject->update_html($displayobject->print_per_page_pass(count($customprofilepic_array), $displayobject->phrases['custom_avatars_lower'], $custom_pics_start_at));

		if ($customprofilepic_array)
		{
			foreach ($customprofilepic_array AS $cust_pic_id => $cus_pic)
			{
				$try = (phpversion() < '5' ? $customprofilepic_object : clone($customprofilepic_object));

				$try->set_value('mandatory', 'importcustomprofilepicid',	$cust_pic_id);

				$userid = $idcache->get_id('user', $cus_pic['userid']);

				if (!$userid)
				{
					$displayobject->display_now('<br />' . $displayobject->phrases['userid_error']);
					$sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num . '_objects_failed') + 1);
					continue;
				}

				$try->set_value('nonmandatory', 'userid',					$userid);
				$try->set_value('nonmandatory', 'filedata',					$Db_target->escape_string($cus_pic['profilepicdata']));
				$try->set_value('nonmandatory', 'dateline',					$cus_pic['dateline']);
				$try->set_value('nonmandatory', 'filename',					$cus_pic['filename']);
				$try->set_value('nonmandatory', 'visible',					$cus_pic['visible']);

				if ($try->is_valid())
				{
					if ($try->import_custom_profile_pic($Db_target, $target_database_type, $target_table_prefix))
					{
						$displayobject->update_html($displayobject->make_description('<span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['cus_pic'] . ' -> ' . $try->get_value('nonmandatory', 'filename')));
						$sessionobject->add_session_var($class_num . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1);
					}
					else
					{
						$sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num . '_objects_failed') + 1);
						$sessionobject->add_error($cust_pic_id, $displayobject->phrases['custom_profile_pic_not_imported'], $displayobject->phrases['custom_profile_pic_not_imported_rem']);
						$displayobject->update_html($displayobject->make_description($displayobject->phrases['failed'] . ' :: ' . $displayobject->phrases['custom_profile_pic_not_imported']));
					}
				}
				else
				{
					$displayobject->display_now('<br />' . $displayobject->phrases['invalid_avatar_skipping'] . $try->_failedon);
				}
				unset($try);
			}
		}
		else
		{
			$displayobject->update_html($displayobject->make_description($displayobject->phrases['no_cust_pic_import']));
		}

		$displayobject->update_html($displayobject->table_footer());

		if (count($customprofilepic_array) == 0 OR count($customprofilepic_array) < $custom_pics_per_page)
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
			$sessionobject->set_session_var('custompicsstartat', $custom_pics_start_at+$custom_pics_per_page);
			$displayobject->update_html($displayobject->print_redirect_001('index.php'));
		}
	}
}

?>