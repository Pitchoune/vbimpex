<?php
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
* The Display Wrapper object
*
* Calls the vB3 admincp funtions where avaiable to override when not being
* run stand alone.
*
*
* @package 		ImpEx
*/

if (!class_exists('ImpExDisplay')) { die('Direct class access violation'); }

class ImpExDisplayWrapper extends ImpExDisplay
{
	public function __construct()
	{
		require_once(DIR . '/includes/functions.php');
		require_once(DIR . '/includes/adminfunctions.php');
	}

	private function call($function_name, $params = null)
	{
		// If NULL, set it to an empty array
		$params = (is_null($params) == true ? array() : $params);

		return call_user_func_array($function_name, $params);
	}

	public function display_now($var)
	{
		$string = $this->page_header();

		$this->_screenbasic['displaymodules'] = FALSE;

		$string .= $var;
		echo "\n" . $string;
		$string .= $this->page_footer();
		flush();
	}

	public function table_header($echobr = true, $width = '90%', $cellspacing = 0, $id = '', $border_collapse = false)
	{
		$this->call('print_table_start', array($echobr, $width, $cellspacing, $id, $border_collapse));
	}

	public function table_break($insert = '', $echobr = true, $width = '90%', $cellspacing = 0, $id = '', $border_collapse = false)
	{
		$this->call('print_table_break', array($insert, $width));
	}

	public function table_footer($colspan = 2, $rowhtml = '', $tooltip = '', $echoform = true)
	{
		$this->call('print_table_footer', array($colspan, $rowhtml, $tooltip, $echoform));
	}

	public function page_header()
	{
		if ($this->_screenbasic['donehead'] != 'FALSE')
		{
			return '';
		}

		if (!$this->_screenbasic['title'] OR $this->_screenbasic['title'] == 'NONE')
		{
			global $vbphrase;
			if (isset($vbphrase['import']))
			{
				$outtitle = $vbphrase['import'] . '/' . $vbphrase['export'];
			}
			else
			{
				$outtitle = $this->phrases['title'];
			}
		}
		else
		{
			$outtitle = $this->_screenbasic['title'];
		}

		$style = '<style type="text/css">.isucc { color: green; } .ifail { color: red; }</style>';

		$this->call('print_cp_header', array($outtitle, $this->_screenbasic['autosubmit'] != '0' ? 'document.name.submit();' : '', $style));
		$this->_screenbasic['donehead'] = 'TRUE';

		$string .= "\n" . '<b>' . $this->phrases['build_version'] . $this->_build_version . '</b>';

		return $string;
	}

	public function page_footer()
	{
		$this->call('print_cp_footer');
	}

	public function update_html($function_called)
	{
		if (!empty($function_called) AND method_exists($this, $function_called))
		{
			return $this->$function_called;
		}
	}

	public function make_input_code($title, $name, $value = '',$htmlise = 1,$size = 35)
	{
		$this->call('print_input_row', array($title, $name, $value));
	}

	public function make_table_header($title, $htmlise = 1, $colspan = 2, $anchor = '', $align = 'center', $helplink = 1)
	{
		$this->call('print_table_header', array($title, $colspan, $htmlise, $anchor, $align, $helplink));
	}

	public function get_row_bg()
	{
		return 1;
	}

	public function do_form_header($phpscript = '', $action, $uploadform = false ,$addtable = true, $name = 'cpform', $width = '90%', $target = '', $echobr = true, $method = 'post')
	{
		$this->call('print_form_header', array($phpscript, $action, $uploadform, $addtable, $name, $width, $target, $echobr, $method));
	}

	public function do_form_footer($submitname = '', $resetname = '_default_', $colspan = 2, $goback = '', $extra = '')
	{
		$this->call('print_submit_row', array($submitname , $resetname , $colspan , $goback , $extra));
	}

	public function do_form_footer_no_reset($submitname = 'Submit', $colspan = 2, $goback = '')
	{
		$this->call('print_submit_row', array($submitname , $resetname , $colspan , $goback , $extra));
	}

	public function make_description($text, $htmlise = 0, $colspan = 2, $class = '', $align = '')
	{
		$this->call('print_description_row', array($text, $htmlise, $colspan, $class, $align));
	}

	public function make_yesno_code($title, $name, $value = 1, $onclick = '')
	{
		$this->call('print_yes_no_row', array($title, $name, $value, $onclick));
	}

	public function make_hidden_code($name, $value = '', $htmlise = 1)
	{
		$this->call('construct_hidden_code', array($name, $value, $htmlise));
	}

	/**
	* Return the display string for the completed module.
	*
	* @param	string		mixed			The name of the module.
	* @param	int			mixed			The seconds taken to complete.
	* @param	int			mixed			Number of successful.
	* @param	int			mixed			Number of failed.
	*
	* @return	mixed		string|NULL		The formed HTML.
	*/
	public function module_finished($modulestring, $seconds, $successful, $failed)
	{
		if ($seconds <= 1)
		{
			$secondphrase = '1 ' . $this->phrases['second'];
		}
		else
		{
			$secondphrase = $seconds . ' ' . $this->phrases['seconds'];
		}

		echo '<p align="center">' . $this->phrases['module'] . ' : <b>' . $modulestring . '</b>. <i>' . $this->phrases['successful'] . '</i>, ' . $secondphrase . '.</p>
				<p align="center">' . $this->phrases['successful'] . ': <b>' . $successful . '</b>. ' . $this->phrases['failed'] . ': <b>' . $failed . '</b>.</p>';
	}
}

?>