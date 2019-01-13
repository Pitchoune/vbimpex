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
* vb2_007 Import Post module
*
* @package			ImpEx.vb2
*/
class vb2_007 extends vb2_000
{
	var $_dependent = '006';

	public function __construct(&$displayobject)
	{
		$this->_modulestring = $displayobject->phrases['import_posts'];
	}

	public function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source, $resume = false)
	{
		if ($this->check_order($sessionobject, $this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_posts'))
				{
					$displayobject->update_html($displayobject->table_header());
					$displayobject->update_html($displayobject->make_table_header($this->_modulestring));
					$displayobject->update_html($displayobject->make_description($displayobject->phrases['posts_cleared']));
					$displayobject->update_html($displayobject->table_footer());
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error(substr(get_class($this), -3), $displayobject->phrases['post_restart_failed'], $displayobject->phrases['check_db_permissions']);
				}
			}

			// Start up the table
			$displayobject->update_basic('title', $displayobject->phrases['import_post']);
			$displayobject->update_html($displayobject->do_form_header('index', substr(get_class($this), -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this), -3), 'WORKING'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));

			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code($displayobject->phrases['posts_per_page'], 'postperpage', 2000));

			// End the table
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this), -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this), -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('poststartat', '0');
			$sessionobject->add_session_var('modulestring', $this->_modulestring);
		}
		else
		{
			// Dependant has not been run
			$displayobject->update_html($displayobject->do_form_header('index', ''));
			$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['dependency_error']));
			$displayobject->update_html($displayobject->make_description('<p>' . $displayobject->phrases['dependant_on'] . '<i><b> ' . $sessionobject->get_module_title($this->_dependent) . '</b>' . $displayobject->phrases['cant_run'] . '</i>.'));
			$displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'], ''));
			$sessionobject->set_session_var(substr(get_class($this), -3),'FALSE');
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
		$post_start_at			= $sessionobject->get_session_var('poststartat');
		$post_per_page			= $sessionobject->get_session_var('postperpage');
		$idcache 				= new ImpExCache($Db_target, $target_database_type, $target_table_prefix);
		$class_num				= substr(get_class($this), -3);

		// Start the timing
		if (!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num, 'start', $sessionobject->get_session_var('autosubmit'));
		}

		if (intval($post_per_page) == 0)
		{
			$post_per_page = 150;
		}

		$source_data_array	= $this->get_source_data($Db_source, $source_database_type, "{$source_table_prefix}post", "postid", null, $post_start_at, $post_per_page, $displayobject);

		// Give the user some info
		$displayobject->update_html($displayobject->table_header());
		$displayobject->update_html($displayobject->make_table_header($displayobject->phrases['import_posts']));

		$displayobject->update_html($displayobject->print_per_page_pass(count($source_data_array['data']), $displayobject->phrases['posts_lower'], $post_start_at));

		$post_object = new ImpExData($Db_target, $sessionobject, 'post');

		if ($source_data_array['data'])
		{
			foreach ($source_data_array['data'] AS $post_id => $post_details)
			{
				$try = (phpversion() < '5' ? $post_object : clone($post_object));

				// Mandatory
				$try->set_value('mandatory', 'threadid',			$idcache->get_id('thread', $post_details['threadid']));
				$try->set_value('mandatory', 'userid',				$idcache->get_id('user', $post_details['userid']));
				$try->set_value('mandatory', 'importthreadid',		$post_details['threadid']);

				// Non Mandatory
				$try->set_value('nonmandatory', 'parentid',			$idcache->get_id('post', $post_details['parentid']));
				$try->set_value('nonmandatory', 'username',			$post_details['username']);
				$try->set_value('nonmandatory', 'title',			$post_details['title']);
				$try->set_value('nonmandatory', 'dateline',			$post_details['dateline']);
				$try->set_value('nonmandatory', 'pagetext',			$this->html_2_bb($post_details['pagetext']));
				$try->set_value('nonmandatory', 'allowsmilie',		$post_details['allowsmilie']);
				$try->set_value('nonmandatory', 'showsignature',	$post_details['showsignature']);
				$try->set_value('nonmandatory', 'ipaddress',		$post_details['ipaddress']);
				$try->set_value('nonmandatory', 'iconid',			$post_details['iconid']);
				$try->set_value('nonmandatory', 'visible',			$post_details['visible']);
				$try->set_value('nonmandatory', 'importpostid',		$post_id);

				if ($try->is_valid())
				{
					if ($try->import_post($Db_target, $target_database_type, $target_table_prefix))
					{
						$displayobject->update_html($displayobject->make_description('<span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['post'] . ' -> ' . $try->get_value('nonmandatory', 'username')));
						$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1);
					}
					else
					{
						$displayobject->update_html($displayobject->make_description($displayobject->phrases['failed'] . ' :: ' . $displayobject->phrases['post_not_imported']));
						$sessionobject->set_session_var($class_num . '_objects_failed', $sessionobject->get_session_var($class_num . '_objects_failed') + 1);
						$sessionobject->add_error($post_id, $displayobject->phrases['post_not_imported'], $displayobject->phrases['post_not_imported_rem']);
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
			$displayobject->update_html($displayobject->make_description($displayobject->phrases['no_post_to_import']));
		}

		$displayobject->update_html($displayobject->table_footer());

		if (count($source_data_array['data']) == 0 OR count($source_data_array['data']) < $post_per_page)
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
			$sessionobject->set_session_var('poststartat', $post_start_at + $post_per_page);
			$displayobject->update_html($displayobject->print_redirect_001('index.php'));
		}
	}
}

?>