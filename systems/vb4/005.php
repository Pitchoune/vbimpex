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
*
*/
class vb4_005 extends vb4_000
{
	var $_dependent = '004';

	public function __construct(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_avatars'];
	}

	public function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source, $resume = false)
	{
		$proceed = $this->check_order($sessionobject, $this->_dependent);

		if ($proceed)
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_avatars'))
				{
					$displayobject->update_html($displayobject->table_header());
					$displayobject->update_html($displayobject->make_table_header($this->_modulestring));
					$displayobject->update_html($displayobject->make_description($displayobject->phrases['avatars_cleared']));
					$displayobject->update_html($displayobject->table_footer());
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error(substr(get_class($this), -3), $displayobject->phrases['avatar_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title', $displayobject->phrases['import_avatar']);
			$displayobject->update_html($displayobject->do_form_header('index', substr(get_class($this), -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this), -3), 'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['avatar_per_page'], 'avatarperpage', 50));
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['avatar_path'], 'avatar_path', $sessionobject->get_session_var('avatar_path'), 1, 60));

			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'], $displayobject->phrases['reset']));

			$sessionobject->add_session_var('avatarsstartat', '0');
			$sessionobject->add_session_var('normal_avatars_done', 'no');
		}
		else
		{
			// Dependant has not been run
			$displayobject->update_html($displayobject->do_form_header('index', ''));
			$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['dependency_error']));
			$displayobject->update_html($displayobject->make_description('<p>' . $displayobject->phrases['dependant_on'] . '<i><b>' . $sessionobject->get_module_title($this->_dependent) . '</b>' . $displayobject->phrases['cant_run'] . '</i>.'));
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'], ''));
			$sessionobject->set_session_var(substr(get_class($this), -3), 'FALSE');
			$sessionobject->set_session_var('module', '000');
		}
	}

	public function resume(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		// Set up working variables.
		$displayobject->update_basic('displaymodules', 'FALSE');
		$target_database_type	= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix	= $sessionobject->get_session_var('targettableprefix');

		$source_database_type	= $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix	= $sessionobject->get_session_var('sourcetableprefix');

		$avatar_start_at		= $sessionobject->get_session_var('avatarsstartat');
		$avatar_per_page		= $sessionobject->get_session_var('avatarperpage');
		$class_num				= substr(get_class($this), -3);

		// Start the timing
		if (!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num, 'start', $sessionobject->get_session_var('autosubmit'));
		}

		if ($sessionobject->get_session_var('normal_avatars_done') != 'yes')
		{
			$avatar_array = $this->get_details($Db_source, $source_database_type, $source_table_prefix, $displayobject, 0, -1, 'avatar', 'avatarid');

			$displayobject->update_html($displayobject->table_header());
			$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['import_avatars']));

			$avatar_object = new ImpExData($Db_target, $sessionobject, 'avatar');

			$displayobject->update_html($displayobject->make_description('<b>' . $displayobject->phrases['imported'] . ' ' . count($avatar_array) . ' ' . $displayobject->phrases['avatars'] . '</b><br /><br /><b>' . $displayobject->phrases['from'] . '</b> : ' . ($avatar_start_at + 1) . ' :: <b>' . $displayobject->phrases['to'] . '</b> : ' . ($avatar_start_at + count($avatar_array))));

			if ($avatar_array)
			{
				foreach ($avatar_array AS $avatar_id => $avatar)
				{
					$try = (phpversion() < '5' ? $avatar_object : clone($avatar_object));

					$try->set_value('mandatory', 	'importavatarid',	$avatar_id);
					$try->set_value('nonmandatory', 'title',			$avatar['title']);
					$try->set_value('nonmandatory', 'minimumposts',		$avatar['minimumposts']);
					$try->set_value('nonmandatory', 'avatarpath',		$avatar['avatarpath']);
					$try->set_value('nonmandatory', 'imagecategoryid',	$avatar['imagecategoryid']);
					$try->set_value('nonmandatory', 'displayorder',		$avatar['displayorder']);

					if ($try->is_valid())
					{
						if ($try->import_vb3_avatar($Db_target, $target_database_type, $target_table_prefix))
						{
							$displayobject->update_html($displayobject->make_description('<span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['avatar'] . ' -> ' . $try->get_value('nonmandatory', 'title')));
							$sessionobject->add_session_var($class_num . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1);
						}
						else
						{
							$sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num . '_objects_failed') + 1);
							$sessionobject->add_error($user_id, $displayobject->phrases['avatar_not_imported'], $displayobject->phrases['avatar_not_imported_rem']);
							$displayobject->update_html($displayobject->make_description($displayobject->phrases['failed'] . ' :: ' . $displayobject->phrases['avatar_not_imported']));
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
				$displayobject->update_html($displayobject->make_description($displayobject->phrases['no_avatar_to_import']));
			}
			$sessionobject->add_session_var('normal_avatars_done', 'yes');
		}

		$displayobject->update_html($displayobject->table_footer());

		$customavatar_object = new ImpExData($Db_target, $sessionobject, 'customavatar');

		$custom_avatar_array = $this->get_details($Db_source, $source_database_type, $source_table_prefix, $displayobject, $avatar_start_at, $avatar_per_page, 'customavatar', 'userid');
		$users_ids = $this->get_user_ids($Db_target, $target_database_type, $target_table_prefix);

		$displayobject->update_html($displayobject->table_header());
		$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['import_custom_avatars']));

		$displayobject->update_html($displayobject->make_description('<b>' . $displayobject->phrases['imported'] . ' ' . count($custom_avatar_array) . ' ' . $displayobject->phrases['custom_avatars'] . '</b>'));

		if ($custom_avatar_array)
		{
			foreach ($custom_avatar_array AS $avatar_id => $avatar)
			{
				$try = (phpversion() < '5' ? $customavatar_object : clone($customavatar_object));

				$try->set_value('mandatory', 'importcustomavatarid',	$avatar_id);

				$try->set_value('nonmandatory', 'userid',				$users_ids["$avatar[userid]"]);

				if ($avatar['avatardata'])
				{
					// old stored in db
					$try->set_value('nonmandatory', 'filedata',			$Db_target->escape_string($avatar['avatardata']));
				}
				else if ($avatar['filedata'])
				{
					// new stored in db
					$try->set_value('nonmandatory', 'filedata',			$Db_target->escape_string($avatar['filedata']));
				}
				else
				{
					// either, stored in file system
					$avatar_path = $sessionobject->get_session_var('avatar_path');

					// Get the revision
					$avatarrevision = $Db_source->query_first("
						SELECT avatarrevision
						FROM " . $source_table_prefix . "user
						WHERE userid = " . $avatar['userid'] . "
					");

					// Go get it
					$try->set_value('nonmandatory', 'filedata',	$Db_target->escape_string($this->vb_file_get_contents("{$avatar_path}/avatar" . $avatar['userid'] . "_" . $avatarrevision['avatarrevision'] . ".gif")));
				}

				$try->set_value('nonmandatory', 'dateline',				$avatar['dateline']);
				$try->set_value('nonmandatory', 'filename',				$avatar['filename']);
				$try->set_value('nonmandatory', 'visible',				$avatar['visible']);
				$try->set_value('nonmandatory', 'filesize',				$avatar['filesize']);

				if ($sessionobject->get_session_var('targetsystem') == '350')
				{
					if ($avatar['width'])
					{
						$try->set_value('nonmandatory', 'width',		$avatar['width']);
					}

					if ($avatar['height'])
					{
						$try->set_value('nonmandatory', 'height',		$avatar['height']);
					}
				}

				if ($try->is_valid())
				{
					if ($try->import_vb3_customavatar($Db_target, $target_database_type, $target_table_prefix))
					{
						$displayobject->update_html($displayobject->make_description('<span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['custom_avatar'] . ' -> ' . $try->get_value('nonmandatory','filename')));
						$sessionobject->add_session_var($class_num . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1);
					}
					else
					{
						$sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num . '_objects_failed') + 1);
						$sessionobject->add_error($avatar_id, $displayobject->phrases['custom_avatar_not_imported'], $displayobject->phrases['custom_avatar_not_imported_rem']);
						$displayobject->update_html($displayobject->make_description($displayobject->phrases['failed'] . ' :: ' . $displayobject->phrases['custom_avatar_not_imported']));
					}
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1);
					$displayobject->update_html($displayobject->make_description($displayobject->phrases['invalid_object'] . $try->_failedon));
				}

				unset($try);
			}
		}
		else
		{
			$displayobject->update_html($displayobject->make_description($displayobject->phrases['no_custom_avatar_import']));
		}

		$displayobject->update_html($displayobject->table_footer());

		if (count($custom_avatar_array) == 0 OR count($custom_avatar_array) < $avatar_per_page)
		{
			$sessionobject->timing($class_num, 'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');

			$displayobject->update_html($displayobject->module_finished($displayobject->phrases['import_avatars'], $sessionobject->return_stats($class_num, '_time_taken'), $sessionobject->return_stats($class_num, '_objects_done'), $sessionobject->return_stats($class_num, '_objects_failed')));

			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('module', '000');
			$sessionobject->set_session_var('autosubmit', '0');
			$displayobject->update_html($displayobject->print_redirect_001('index.php', $sessionobject->get_session_var('pagespeed')));
		}
		else
		{
			$displayobject->update_html($displayobject->print_redirect_001('index.php', $sessionobject->get_session_var('pagespeed')));
			$sessionobject->set_session_var('avatarsstartat', $avatar_start_at+$avatar_per_page);
		}
	}
}

?>
