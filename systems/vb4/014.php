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
* vb4 Import Attachments
*
* @package 		ImpEx.vb4
*/
class vb4_014 extends vb4_000
{
	var $_dependent = '009';

	public function __construct(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_attachments'];
	}

	public function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source, $resume = false)
	{
		if ($this->check_order($sessionobject, $this->_dependent))
		{
			if ($this->_restart)
			{
				$contentinfo = array(
					'productid' => 'vbulletin',
					'class'     => 'Post',
				);

				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source, 'clear_imported_attachments', $contentinfo))
				{
					$displayobject->update_html($displayobject->table_header());
					$displayobject->update_html($displayobject->make_table_header($this->_modulestring));
					$displayobject->update_html($displayobject->make_description($displayobject->phrases['attachments_cleaned']));
					$displayobject->update_html($displayobject->table_footer());
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error(substr(get_class($this), -3), $displayobject->phrases['attachment_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title', $displayobject->phrases['import_attachments']);
			$displayobject->update_html($displayobject->do_form_header('index', substr(get_class($this), -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this), -3), 'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['attachments_per_page'], 'attachmentperpage', 250));
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['path_to_upload'], 'attachmentsfolder', $sessionobject->get_session_var('attachmentsfolder'), 1, 60));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'], $displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this), -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this), -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('attachmentstartat', '0');
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
		$attachment_start_at	= $sessionobject->get_session_var('attachmentstartat');
		$attachment_per_page	= $sessionobject->get_session_var('attachmentperpage');

		$attachment_object 		= new ImpExData($Db_target, $sessionobject, 'attachment');
		$class_num				= substr(get_class($this) , -3);

		// Start the timing
		if (!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num, 'start', $sessionobject->get_session_var('autosubmit'));
		}

		if (intval($attachment_per_page) == 0)
		{
			$attachment_per_page = 200;
		}

		$attachment_array = $this->get_vb4_attachment_details($Db_source, $source_database_type, $source_table_prefix, $attachment_start_at, $attachment_per_page, 'vbulletin', 'Post');

		// Give the user some info
		$displayobject->update_html($displayobject->table_header());
		$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['import_attachments']));

		$displayobject->update_html($displayobject->print_per_page_pass(count($attachment_array), $displayobject->phrases['attachments_lower'], $attachment_start_at));

		if ($attachment_array)
		{
			foreach ($attachment_array AS $attachment_id => $attachment)
			{
				$try = (phpversion() < '5' ? $attachment_object : clone($attachment_object));

				// If its null, its stored in the source file system
				if (strlen($sessionobject->get_session_var('attachmentsfolder')) > 1)
				{
					$id_string = strval($attachment['userid']);
					$attach_path = '/';

					for ($i = 0; $i <= strlen($id_string); $i++)
					{
						$attach_path .= $id_string[$i] . '/';
					}

					$attach_path = substr($attach_path, 0, -1);
					$attach_path = $sessionobject->get_session_var('attachmentsfolder') . $attach_path . $attachment['filedataid'] . '.attach';

					if (!is_file($attach_path))
					{
						$displayobject->update_html($displayobject->make_description('<b>' . $displayobject->phrases['source_file_not'] . ' </b> :: ' . $attach_path));
						$sessionobject->add_error($attachment_id, $displayobject->phrases['attachment_not_imported'], $attach_path . ' - ' . $displayobject->phrases['attachment_not_imported_rem_1']);
						$sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num . '_objects_failed') + 1);
						continue;
					}

					$attachment['filedata'] = $this->vb_file_get_contents($attach_path);
				}

				$try->set_value('mandatory', 'importattachmentid',	$attachment_id);
				$try->set_value('mandatory', 'filename',			$attachment['filename']);
				$try->set_value('mandatory', 'filedata',			$attachment['filedata']);

				$try->set_value('nonmandatory', 'dateline',			$attachment['dateline']);
				$try->set_value('nonmandatory', 'visible',			$attachment['state']);
				$try->set_value('nonmandatory', 'counter',			$attachment['counter']);
				$try->set_value('nonmandatory', 'filesize',			$attachment['filesize']);
				$try->set_value('nonmandatory', 'postid',			$attachment['contentid']);
				$try->set_value('nonmandatory', 'filehash',			$attachment['filehash']);
				$try->set_value('nonmandatory', 'settings',			$attachment['settings']);
				$try->set_value('nonmandatory', 'width',			$attachment['width']);
				$try->set_value('nonmandatory', 'height',			$attachment['height']);
				$try->set_value('nonmandatory', 'displayorder',		$attachment['displayorder']);
				$try->set_value('nonmandatory', 'caption',			$attachment['caption']);

				// Check that if there is some file data
				if ($try->is_valid() AND !empty($attachment['filedata']))
				{
					if ($try->import_vb4_attachment($Db_target, $target_database_type, $target_table_prefix))
					{
						$displayobject->update_html($displayobject->make_description('<span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['attachment'] . ' -> ' . $try->get_value('mandatory','filename')));
						$sessionobject->add_session_var($class_num . '_objects_done', intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1);
					}
					else
					{
						$sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num . '_objects_failed') + 1);
						$sessionobject->add_error($attachment_id, $displayobject->phrases['attachment_not_imported'], $displayobject->phrases['attachment_not_imported_rem_2']);
						$displayobject->update_html($displayobject->make_description($displayobject->phrases['failed'] . ' :: ' . $displayobject->phrases['attachment_not_imported']));
					}
				}
				else
				{
					if (empty($attachment['filedata']))
					{
						$displayobject->update_html($displayobject->make_description($displayobject->phrases['invalid_object'] . $try->_failedon . ' <b>' . $displayobject->phrases['source_file_not'] . ' </b>'));
					}
					else
					{
						$displayobject->update_html($displayobject->make_description($displayobject->phrases['invalid_object'] . $try->_failedon));
					}
					$sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num. '_objects_failed') + 1);
				}
				unset($try);
			}
		}
		else
		{
			$displayobject->update_html($displayobject->make_description($displayobject->phrases['no_attachment_to_import']));
		}

		$displayobject->update_html($displayobject->table_footer());

		if (count($attachment_array) == 0 OR count($attachment_array) < $attachment_per_page)
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
			$sessionobject->set_session_var('attachmentstartat', $attachment_start_at + $attachment_per_page);
			$displayobject->update_html($displayobject->print_redirect_001('index.php'));
		}
	}
}

?>