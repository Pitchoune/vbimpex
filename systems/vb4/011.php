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
* vb4 Import Private Messages
*
* @package 		ImpEx.vb4
*/
class vb4_011 extends vb4_000
{
	var $_dependent 	= '004';

	public function __construct(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_pms'];
	}

	public function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source, $resume = false)
	{
		if ($this->check_order($sessionobject, $this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_private_messages'))
				{
					$displayobject->update_html($displayobject->table_header());
					$displayobject->update_html($displayobject->make_table_header($this->_modulestring));
					$displayobject->update_html($displayobject->make_description($displayobject->phrases['pms_cleared']));
					$displayobject->update_html($displayobject->table_footer());
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error(substr(get_class($this), -3), $displayobject->phrases['pm_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title', $displayobject->phrases['import_pms']);
			$displayobject->update_html($displayobject->do_form_header('index', substr(get_class($this), -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this), -3), 'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['pms_per_page'], 'pmperpage', 250));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'], $displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this), -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this), -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('pmstartat', '0');
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
		$target_database_type 	= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix  	= $sessionobject->get_session_var('targettableprefix');
		$source_database_type 	= $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix  	= $sessionobject->get_session_var('sourcetableprefix');
		$modulestring			= $sessionobject->get_session_var('modulestring');

		// Get some usable variables
		$pm_start_at 			= $sessionobject->get_session_var('pmstartat');
		$pm_per_page 			= $sessionobject->get_session_var('pmperpage');
		$class_num				= substr(get_class($this), -3);

		$idcache 				= new ImpExCache($Db_target, $target_database_type, $target_table_prefix);
		$pm_text_object 		= new ImpExData($Db_target, $sessionobject, 'pmtext');
		$pm_object 				= new ImpExData($Db_target, $sessionobject, 'pm');

		// Start the timing
		if (!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num, 'start', $sessionobject->get_session_var('autosubmit'));
		}

		if (intval($pm_per_page) == 0)
		{
			$pm_per_page = 150;
		}

		// Get the PM's for this pass and some refrence arrays
		$pm_array = $this->get_details($Db_source, $source_database_type, $source_table_prefix, $pm_start_at, $pm_per_page, 'pmtext', 'pmtextid');

		// Give the user some info
		$displayobject->update_html($displayobject->table_header());
		$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['import_pms']));

		$displayobject->update_html($displayobject->print_per_page_pass(count($pm_array), $displayobject->phrases['pms_lower'], $pm_start_at));

		if ($pm_array)
		{
			foreach ($pm_array AS $pm_id => $details)
			{
				$pm_text = (phpversion() < '5' ? $pm_text_object : clone($pm_text_object));

				unset($touserarray);

				$old_array = unserialize($details['touserarray']);

				if (!is_array($old_array))
				{
					continue;
				}

				foreach ($old_array AS $old_user_id => $username)
				{
					$userid = $idcache->get_id('user', $old_user_id);
					$touserarray[$userid] = $username;
				}

				// Mandatory
				# cache
				$pm_text->set_value('mandatory', 'fromuserid',			$idcache->get_id('user', $details['fromuserid']));
				$pm_text->set_value('mandatory', 'title',				$details['title']);
				$pm_text->set_value('mandatory', 'message',				$details['message']);
				$pm_text->set_value('mandatory', 'touserarray',			addslashes(serialize($touserarray)));
				$pm_text->set_value('mandatory', 'importpmid',			$pm_id);

				// Non Mandatory
				$pm_text->set_value('nonmandatory', 'fromusername',		$details['fromusername']);
				$pm_text->set_value('nonmandatory', 'iconid',			$details['iconid']);
				$pm_text->set_value('nonmandatory', 'dateline',			$details['dateline']);
				$pm_text->set_value('nonmandatory', 'showsignature',	$details['showsignature']);
				$pm_text->set_value('nonmandatory', 'allowsmilie',		$details['allowsmilie']);

				if ($pm_text->is_valid())
				{
					$pm_text_id = $pm_text->import_pm_text($Db_target, $target_database_type, $target_table_prefix);

					if ($pm_text_id)
					{
						$pms = $this->get_vb4_pms($Db_source, $source_database_type, $source_table_prefix, $pm_id);

						foreach ($pms AS $pm => $details)
						{
							$pm = $pm_object;

							$pm->set_value('mandatory', 'pmtextid',			$pm_text_id);
							$pm->set_value('mandatory', 'userid',			$idcache->get_id('user', $details['userid']));
							$pm->set_value('mandatory', 'importpmid',		$pm_id);

							// Not creating default folders atm, if its not default stuff it in the inbox
							if ($details['folderid'] == 0 OR $details['folderid'] == -1)
							{
								$pm->set_value('nonmandatory', 'folderid',	$details['folderid']);
							}
							else
							{
								$pm->set_value('nonmandatory', 'folderid',	'-1');
							}

							$pm->set_value('nonmandatory', 'messageread',	$details['messageread']);


							if ($pm->is_valid())
							{
								if ($pm->import_pm($Db_target, $target_database_type, $target_table_prefix))
								{
									$displayobject->update_html($displayobject->make_description('<span class="isucc"><b>' . $pm->how_complete() . '%</b></span> ' . $displayobject->phrases['pm'] . ' -> ' . $pm_text->get_value('nonmandatory', 'fromusername')));
									$sessionobject->add_session_var($class_num . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1);
								}
								else
								{
									$sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num . '_objects_failed') + 1);
									$sessionobject->add_error($pmtext_id, $displayobject->phrases['pm_not_imported'], $displayobject->phrases['pm_not_imported_rem_2']);
									$displayobject->update_html($displayobject->make_description($displayobject->phrases['failed'] . ' :: ' . $displayobject->phrases['pm_not_imported']));
									$sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num . '_objects_failed') + 1);
								}
							}
							else
							{
								$displayobject->update_html($displayobject->make_description($displayobject->phrases['invalid_object'] . $try->_failedon));
								$sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num . '_objects_failed') + 1);
							}
							unset($pm);
						}
					}
				}
				unset($pm_text);
			}
		}
		else
		{
			$displayobject->update_html($displayobject->make_description($displayobject->phrases['no_pm_to_import']));
		}

		$displayobject->update_html($displayobject->table_footer());

		if (count($pm_array) == 0 OR count($pm_array) < $pm_per_page)
		{
			$sessionobject->timing($class_num, 'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$displayobject->update_html($displayobject->table_header());
			$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['import_pms']));

			if ($this->update_user_pm_count($Db_target, $target_database_type, $target_table_prefix))
			{
				$displayobject->update_html($displayobject->make_description($displayobject->phrases['pm_counter_updated']));
			}
			else
			{
				$displayobject->update_html($displayobject->make_description($displayobject->phrases['pm_counter_error']));
			}

			$displayobject->update_html($displayobject->table_footer());

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
			$sessionobject->set_session_var('pmstartat', $pm_start_at + $pm_per_page);
			$displayobject->update_html($displayobject->print_redirect_001('index.php'));
		}
	}
}

?>