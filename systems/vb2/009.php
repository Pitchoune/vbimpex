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
* vb2_009 Import Pmtext module
*
* @package			ImpEx.vb2
*/
class vb2_009 extends vb2_000
{
	var $_dependent = '004';

	public function __construct(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_pms'];
	}

	public function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source, $resume = false)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_private_messages'))
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
			$displayobject->update_basic('title', $displayobject->phrases['import_pm']);
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
		$target_database_type	= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix	= $sessionobject->get_session_var('targettableprefix');
		$source_database_type	= $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix	= $sessionobject->get_session_var('sourcetableprefix');
		$modulestring			= $sessionobject->get_session_var('modulestring');

		// Get some usable variables
		$pm_start_at			= $sessionobject->get_session_var('pmstartat');
		$pm_per_page			= $sessionobject->get_session_var('pmperpage');
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

		// Get an array of pmtext details
		$pm_array 	= $this->get_details($Db_source, $source_database_type, $source_table_prefix, $displayobject, $pm_start_at, $pm_per_page, 'privatemessage', 'privatemessageid');

		// Give the user some info
		$displayobject->update_html($displayobject->table_header());
		$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['import_pms']));

		$displayobject->update_html($displayobject->print_per_page_pass(count($pm_array), $displayobject->phrases['pms_lower'], $pm_start_at));

		if ($pm_array)
		{
			foreach ($pm_array AS $pm_id => $pm)
			{
				$vB_pm_text = (phpversion() < '5' ? $pm_text_object : clone($pm_text_object));

				unset($touserarray);

				$userid 	= $idcache->get_id('user', $pm['touserid']);
				$username	= $idcache->get_id('username', $pm['touserid']);

				$touserarray[$userid] = $username;

				$vB_pm_text->set_value('mandatory', 'fromuserid',		$idcache->get_id('user', $pm['fromuserid']));
				$vB_pm_text->set_value('mandatory', 'title',			$pm['title']);
				$vB_pm_text->set_value('mandatory', 'message',			$pm['message']);
				$vB_pm_text->set_value('mandatory', 'importpmid',		$pm_id);

				$vB_pm_text->set_value('mandatory', 'touserarray',		addslashes(serialize($touserarray)));
				$vB_pm_text->set_value('nonmandatory', 'fromusername',	$idcache->get_id('username', $pm['fromuserid']));
				$vB_pm_text->set_value('nonmandatory', 'iconid',		$pm['iconid']);
				$vB_pm_text->set_value('nonmandatory', 'dateline',		$pm['dateline']);
				$vB_pm_text->set_value('nonmandatory', 'showsignature',	$pm['showsignature']);

				if ($vB_pm_text->is_valid())
				{
					$pm_text_id = $vB_pm_text->import_pm_text($Db_target, $target_database_type, $target_table_prefix);

					if ($pm_text_id)
					{
						$vB_pm_to = (phpversion() < '5' ? $pm_object : clone($pm_object));
						$vB_pm_from = (phpversion() < '5' ? $pm_object : clone($pm_object));

						// The touser pm
						$vB_pm_to->set_value('mandatory', 'pmtextid',			$pm_text_id);
						$vB_pm_to->set_value('mandatory', 'userid',				$idcache->get_id('user', $pm['touserid']));
						$vB_pm_to->set_value('nonmandatory', 'folderid',		'0');
						$vB_pm_to->set_value('nonmandatory', 'messageread',		'0');
						$vB_pm_to->set_value('nonmandatory', 'messageread',		'0');

						// The fromuser pm
						$vB_pm_from->set_value('mandatory', 'pmtextid',			$pm_text_id);
						$vB_pm_from->set_value('mandatory', 'userid',			$idcache->get_id('user', $pm['fromuserid']));
						$vB_pm_from->set_value('nonmandatory', 'folderid',		'-1');
						$vB_pm_from->set_value('nonmandatory', 'messageread',	'0');

						if ($vB_pm_text->is_valid())
						{
							if ($vB_pm_to->import_pm($Db_target, $target_database_type, $target_table_prefix) AND $vB_pm_from->import_pm($Db_target, $target_database_type, $target_table_prefix))
							{
								$displayobject->update_html($displayobject->make_description('<span class="isucc"><b>' . $vB_pm_to->how_complete() . '%</b></span> ' . $displayobject->phrases['pm'] . ' -> ' . $vB_pm_text->get_value('nonmandatory', 'fromusername')));
								$sessionobject->add_session_var($class_num . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1);
							}
							else
							{
								$sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num . '_objects_failed') + 1);
								$sessionobject->add_error($pmtext_id, $displayobject->phrases['pm_not_imported'], $displayobject->phrases['pm_not_imported_rem_1']);
								$displayobject->update_html($displayobject->make_description($displayobject->phrases['failed'] . ' :: ' . $displayobject->phrases['pm_not_imported']));
							}
						}
						else
						{
							$displayobject->update_html($displayobject->make_description($displayobject->phrases['invalid_object'] . $try->_failedon));
							$sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num . '_objects_failed') + 1);
						}
					}
					else
					{
						$sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num. '_objects_failed') + 1);
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
				unset($vB_pm_text, $vB_pm_to, $vB_pm_from);
			}
		}
		else
		{
			$displayobject->update_html($displayobject->make_description($displayobject->phrases['no_pm_to_import']));
		}

		$displayobject->update_html($displayobject->table_footer());

		if (count($pm_array) == 0 OR count($pm_array) < $pm_per_page)
		{
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$displayobject->update_html($displayobject->table_header());
			$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['import_pms']));

			if ($this->update_user_pm_count($Db_target, $target_database_type, $target_table_prefix))
			{
				$displayobject->update_html($displayobject->make_description($displayobject->phrases['completed']));
			}
			else
			{
				$displayobject->update_html($displayobject->make_description($displayobject->phrases['failed']));
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