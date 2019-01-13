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
* The Display object.
*
* Handles HTML code for standalone use.
*
* @package		ImpEx
*/

if (!class_exists('ImpExFunction')) { die('Direct class access violation'); }

class ImpExDisplay extends ImpExFunction
{
	/**
	* Class version.
	*
	* This will allow the checking for interoperability of class version in different versions of ImpEx.
	*
	* @var    string
	*/
	var $_version = '0.0.1';

	/**
	* Build version.
	*
	* This corresponds to the build version of ImpEx.
	*
	* @var    string
	*/
	var $_build_version = '1.104';

	/**
	* Target versions.
	*
	* This corresponds to the available targets to import data.
	*
	* @var    array
	*/
	var $_target_versions = array (
		'forum' => array(
			'400'		=> 'vBulletin 4.0.* - 4.2.*',
			'360'		=> 'vBulletin 3.6.* - 3.8.*',
			'350'		=> 'vBulletin 3.5.*',
			'309'		=> 'vBulletin 3.0.*'
		),
		'blog' => array(
			'blog10'	=> 'vBulletin Blog 1.0.*',
			'blog40'	=> 'vBulletin Blog 4.0.* - 4.2.*'
		),
		'cms' => array(
			'cms10'		=> 'vBulletin Suite CMS 4.0.* - 4.2.*'
		),
	);

	/**
	* Store for display HTML.
	*
	* Hold all the HTML during the life of the object until display is called.
	*
	* @var    string
	*/
	var $_screenstring ='';

	/**
	* Internal flags for the display functions.
	*
	* Various state flags for the display.
	*
	* @var    string
	*/
	var $_screenbasic =  array(
		'title' 			=>		'Import / Export',
		'pageHTML'			=>		'',
		'showwarning'		=>		'TRUE',
		'warning'			=>		'',
		'system'			=>		'NONE',
		'choosesystem'		=>		'FALSE',
		'displaylinks'		=>		'TRUE',
		'autosubmit'		=>		'0',
		'donehead'			=>		'FALSE',
		'displaymodules'	=>		'TRUE'
	);

	/**
	* Constructor.
	*/
	public function __construct()
	{
	}

	/**
	* Retrieves the values needed to define a ImpExData object.
	*
	* @param	string	mixed	An accessor that appends a string (HTML) onto the pageHTML.
	*
	* @return	boolean
	*/
	public function update_html($html)
	{
		$this->_screenbasic['pageHTML'] .= $html;
		return TRUE;
	}

	/**
	* Retrives the values needed to define a ImpExData object.
	*
	* @param	string	mixed	The name of the basic value or flag to update.
	* @param	string	mixed	The value to update it with.
	*
	* @return	boolean	mixed	True|False
	*/
	public function update_basic($name, $status)
	{
		if (empty($status) OR $this->_screenbasic["$name"] == NULL)
		{
			return FALSE;
		}
		else
		{
			$this->_screenbasic["$name"] = $status;
			return TRUE;
		}
	}

	/**
	* HTML Page code - table text input.
	*
	* @param	string	mixed	Input title.
	* @param	string	mixed	HTML element name.
	* @param	string	mixed	The default value.
	* @param	string	mixed	Calls htmlspecialchars on the value.
	* @param	string	mixed	The size of the input.
	*
	* @return	string 	mixed	The formed HTML.
	*/
	public function make_input_code($title, $name, $value = '', $htmlise = 1, $size = 35)
	{
		if ($htmlise)
		{
			$value = htmlspecialchars($value);
		}

		return '
			<tr class="' . $this->get_row_bg() . '" valign="top">
				<td>' . $title . '</td>
				<td><input type="text" size="' . $size . '" name="' . $name . '" value="' . $value . '" /></td>
			</tr>';
	}

	/**
	* HTML Page code - select with input.
	*
	* @param	string	mixed	Input title.
	* @param	string	mixed	HTML element name.
	* @param	array	mixed	Array for the select code.
	*
	* @return	string	mixed	The formed HTML.
	*/
	public function make_select_input_code($title, $name, $select_array)
	{
		return '
			<tr class="' . $this->get_row_bg() . '" valign="top">
				<td>' . $title . '</td>
				<td>' . $this->make_select($select_array, $name) . '</td>
			</tr>';
	}

	/**
	* HTML Page code - table header
	*
	* @param	string	mixed	Table title.
	* @param	string	mixed	HTML anchor name.
	* @param	string	mixed	Calls htmlspecialchars on the value.
	* @param	string	mixed	The collum span width.
	*
	* @return	string 	mixed	The formed HTML.
	*/
	public function make_table_header($title, $htmlise = 1, $colspan = 2)
	{
		return '
			<tr class="thead">
				<td colspan="' . $colspan . '">' . ($htmlise ? htmlspecialchars($title) : $title) . '</td>
			</tr>';
	}

	/**
	* HTML Page code - Applying 2 colors per table row.
	*
	* @return	string	mixed	CSS class.
	*/
	public function get_row_bg()
	{
		if (($bgcounter++ % 2) == 0)
		{
			return 'alt1';
		}
		else
		{
			return 'alt2';
		}
	}

	/**
	* HTML Page code - single table row line
	*
	* @param	string		mixed	Text
	*
	* @return	string		mixed	The formed HTML
	*/
	public function table_row($code)
	{
		$return_string = '<tr>' . $code . '</td>';

		return $return_string;
	}

	/**
	* HTML Page code - single table cell line
	*
	* @param	string		mixed	Text
	*
	* @return	string		mixed	The formed HTML
	*/
	public function table_cell($text, $alt = 'alt2')
	{
		$return_string = '<td class="' . $alt . '" colspan="2">' . $text . '</td>';

		return $return_string;
	}

	/**
	* HTML Page code - single table header.
	*
	* @param	boolean		mixed	Whether or not to place a <br /> before the opening table tag.
	* @param	string		mixed	Width for the <table> - default = '90%'.
	* @param	integer		mixed	Width in pixels for the table's 'cellspacing' attribute.
	* @param	boolean 	mixed	Whether to collapse borders in the table.
	*
	* @return	string		mixed	The formed HTML.
	*/
	public function table_header($echobr = true, $width = '90%', $cellspacing = 0, $id = '', $border_collapse = false)
	{
		$this->tableadded = 1;

		$return_string = '';

		$return_string .= ($echobr ? '<br />' : '') . '<table cellpadding="1" cellspacing="' . $cellspacing . '" border="0" align="center" width="' . $width . '" style="border-collapse:' . ($border_collapse ? 'collapse' : 'separate') . '" class="tblborder"' . ($id == '' ? '' : ' id="' . $id . '"') . '>';

		return $return_string;
	}

	/**
	* HTML Page code - Prints out a closing table tag and opens another for page layout purposes.
	*
	* @param	string	Code to be inserted between the two tables
	* @param	string	Width for the new table - default = '90%'
	*/
	function table_break($insert = '', $echobr = true, $width = '90%', $cellspacing = 0, $id = '', $border_collapse = false)
	{
		// ends the current table, leaves a break and starts it again.
		echo "</table>\n<br />\n\n";

		if ($insert)
		{
			echo "<!-- start mid-table insert -->\n$insert\n<!-- end mid-table insert -->\n\n<br />\n";
		}

		echo '<table cellpadding="1" cellspacing="' . $cellspacing . '" border="0" align="center" width="' . $width . '" style="border-collapse:' . ($border_collapse ? 'collapse' : 'separate') . '" class="tblborder"' . ($id == '' ? '' : ' id="' . $id . '"') . '>';
	}

	/**
	* HTML Page code - single table footer.
	*
	* @param	integer		mixed	Column span of the optional table row to be printed.
	* @param	string		mixed	If specified, creates an additional table row with this code as its contents.
	* @param	string		mixed	Tooltip for optional table row.
	* @param	boolean		mixed	Whether or not to close the <form> tag.
	*
	* @return	string		mixed	The formed HTML.
	*/
	public function table_footer($colspan = 2, $rowhtml = '', $tooltip = '', $echoform = true)
	{
		$return_string = '';

		if ($rowhtml)
		{
			if ($this->tableadded)
			{
				$return_string .= '<tr><td class="tfoot"' . ($colspan != 1 ? ' colspan="' . $colspan . '"' : '') . ' align="center"' . ($tooltip != '' ? ' title="' . $tooltip . '"' : '') . '>' . $rowhtml . '</td></tr>';
			}
			else
			{
				$return_string .= '<p align="center"' . ($tooltip != '' ? ' title="' . $tooltip . '"' : '') . '>' . $rowhtml . '</p>';
			}
		}

		$return_string .= '</table>';

		if ($echoform)
		{
			$return_string .= '</form>';
		}

		return $return_string;
	}

	/**
	* HTML Page code - form header.
	*
	* @param	string	mixed	The target of the form.
	* @param	string	mixed	The action value.
	* @param	int		0|1		whether to use = ENCTYPE=multipart/form-data.
	* @param	int		0|1		whether to add the beginings of a table after the <form> tag.
	*
	* @return	string 	mixed	The formed HTML.
	*/
	public function do_form_header($phpscript, $action, $uploadform = 0, $addtable = 1, $name = 'name')
	{
		$return_string = '';
		$return_string .= '<form action="' . $phpscript . '.php"' . ($uploadform ? ' ENCTYPE="multipart/form-data"' : '') . ' name="' . $name . '" method="post">';

		$return_string .= ($addtable == 1 ? '<br /><table cellpadding="1" cellspacing="0" border="0" align="center" width="90%" class="tblborder">' : '');

		return $return_string;
	}

	/**
	* HTML Page code - form footer.
	*
	* @param	string	mixed	The submit name.
	* @param	string	mixed	The reset name.
	* @param	int		mixed	The column span width.
	* @param	string	mixed	Text for the back button ( onclick="history.back(1)" ).
	*
	* @return	string 	mixed	The formed HTML.
	*/
	public function do_form_footer($submitname, $resetname, $colspan = 2, $goback = '')
	{
		$tableadded = 1;
		$return_string = '';

		$return_string .= ($tableadded == 1 ? '<tr id="submitrow"><td colspan="' . $colspan . '" align="center">' : '<p><center>');
		$return_string .= '<p id="submitrow"><input type="submit" value="' . ($submitname ? $submitname : $this->phrases['submit']) . '" accesskey="s" />';

		$return_string .= '&nbsp;&nbsp;<input type="reset" value="' . ($resetname != '' ? $resetname : $this->phrases['reset']) . '" />';

		if ($goback != '')
		{
			$return_string .= '&nbsp;&nbsp;<input type="button" value="' . $goback . '" onclick="history.back(1)" />';
		}

		$return_string .= ($tableadded == 1 ? '</p></td></tr></table></td></tr></table>' : '</p></center>');
		$return_string .= '</form>';

		return $return_string;
	}

	/**
	* HTML Page code - description line.
	*
	* @param	string	mixed	The description text.
	* @param	int		1|0		htmlspecialchars($value).
	*
	* @return	string 	mixed	The formed HTML.
	*/
	public function make_description($text, $htmlise = 0, $colspan = 2, $class = '', $align = '')
	{
		$return_string = '<tr class="' . ($class ? $class : $this->get_row_bg()) . '" valign="top"><td colspan="2">' . ($htmlise == 0 ? $text : htmlspecialchars($text)) . '</td></tr>';
		return $return_string;
	}

	/**
	* HTML Page code - hidden form input.
	*
	* @param	string	mixed	The hidden value name.
	* @param	string	mixed	The value.
	* @param	int		1|0		htmlspecialchars($value).
	*
	* @return	string 	mixed	The formed HTML.
	*/
	public function make_hidden_code($name, $value = '', $htmlise = 1)
	{
		if ($htmlise)
		{
			$value = htmlspecialchars($value);
		}

		$return_string = '<input type="hidden" name="' . $name . '" value="' . $value . '" />';

		return $return_string;
	}

	/**
	* HTML Page code - yes no.
	*
	* @param	string	mixed	The title of the radio group.
	* @param	string	mixed	The name of the value.
	* @param	int		2|1|0	The inital setting of the yes / no.
	*
	* @return	string 	mixed	The formed HTML.
	*/
	public function make_yesno_code($title, $name, $value = 1)
	{
		// Makes code for input buttons yes\no similar to make_input_code
		$string =
			'<tr class="' . $this->get_row_bg() . '" valign="top">' .
			'<td><p>' . $title . '</p></td>' .
			'<td><p>' . 
			$this->phrases['yes'] . ' <input type="radio" name="' . $name . '" value="1" ' . ($value == 1 OR ($name == 'pmpopup' AND $value == 2) ? 'checked="checked"' : '') . ' />' . 
			$this->phrases['no'] . ' <input type="radio" name="' . $name . '" value="0" ' . ($value == 0 ? 'checked="checked"' : '') . ' />' .
			($value == 2 AND $name == 'customtitle' ? $this->phrases['userset_nohtml'] . ' <input type="radio" name="' . $name . '" value="2" checked="checked" />' : '') .
			'</p></td>\n</tr>';

		return $string;
	}

	/**
	* HTML Page code - displays table with the states of the current modules and buttons depending on the state of the object (not run, running, run).
	* If a object is running no like code is generated.
	*
	* @param	object	sessionobject	The current session object.
	*
	* @return	string 	mixed	The formed HTML.
	*/
	public function display_modules(&$sessionobject)
	{
		$string = '<table class="tborder" cellpadding="6" cellspacing="0" border="0" align="center" width="90%">';
		$_done_objects 		= 0;
		$_failed_objects 	= 0;
		$_time_taken 		= 0;

		if ($this->_screenbasic['displaylinks'] == 'TRUE')
		{
			$string .= '
			<tr>
				<td class="tcat" colspan="6" align="center"><strong>' . $this->phrases['title'] . ' :: ' . $sessionobject->_session_vars['system'] . '</strong></td>
			</tr>
			<tr align="center">
				<td class="thead" colspan="2" align="left">' . $this->phrases['module'] . '</td>
				<td class="thead">' . $this->phrases['action'] . '</td>
				<td class="thead">' . $this->phrases['successful'] . '</td>
				<td class="thead">' . $this->phrases['failed'] . '</td>
				<td class="thead" align="right">' . $this->phrases['timetaken'] . '</td>
			</tr>';
		}

		// -1 at the moment to take care of the 000.php module
		$num_modules = $sessionobject->get_number_of_modules();

		for ($i = 1; $i <= $num_modules - 3; $i++)
		{
			// TODO: The clean up modules, loaded in index
			// Look for the final two
			/*
			if ($i == $num_modules -2)
			{
				$position = '901';
			}
			elseif ($i == $num_modules -1)
			{
				$position = '910';
			}
			else
			{*/
				$position = str_pad($i, 3, '0', STR_PAD_LEFT);
			//}

			$taken = 0;

			if ($this->_screenbasic['displaylinks'] == 'TRUE')
			{
				if (intval($sessionobject->return_stats($position, '_time_taken')) > 60)
				{
					$taken = intval($sessionobject->return_stats($position, '_time_taken') / 60) . $this->phrases['mins'];
				}
				else
				{
					$taken = intval($sessionobject->return_stats($position, '_time_taken')) . $this->phrases['secs'];
				}
				$string .= '
					<tr align="center">
						<td class="alt2" align="left">' . $position . '</td>
						<td class="alt1" align="left">' . $sessionobject->get_module_string($position) . '</td>
						<td class="alt2">
							<form action="index.php" method="post" style="display:inline">
								<input type="hidden" name="module" value="' . $position . '" />
								<input type="submit" value="' . (($sessionobject->get_session_var($position) == 'FINISHED') ? $this->phrases['redo'] : $this->phrases['start_module']) . '" />
							</form>
						</td>
						<td class="alt1">' . $sessionobject->return_stats($position, '_objects_done') . '</td>
						<td class="alt2">' . $sessionobject->return_stats($position, '_objects_failed') . '</td>
						<td class="alt1" align="right">' . $taken . '</td>
					</tr>';

				$_time_taken += intval($sessionobject->return_stats($position, '_time_taken'));
				$_done_objects += intval($sessionobject->return_stats($position, '_objects_done'));
				$_failed_objects += intval($sessionobject->return_stats($position, '_objects_failed'));
			}
			else
			{
				$string .= '
					<tr>
						<td><b>$position</b> ' . $sessionobject->get_module_string($position) . '</td>
						<td>' . $this->_modules["$position"] . '</td>
					</tr>';
			}
		}

		if ($this->_screenbasic['displaylinks'] == 'TRUE')
		{
			if ($_time_taken > 60)
			{
				$_time_taken = ($_time_taken / 60);
				$_append = $this->phrases['minute_title'];
			}
			else
			{
				$_append = $this->phrases['seconds_title'];
			}

			$string .= '
				<tr>
					<td class="tfoot" colspan="3" align="right"><strong>' . $this->phrases['totals'] . '</strong></td>
					<td class="tfoot" align="center"><strong>' . $_done_objects . '</strong></td>
					<td class="tfoot" align="center"><strong>' . $_failed_objects . '</strong></td>
					<td class="tfoot" align="right"><strong>' . round($_time_taken, 2) . $_append . '</strong></td>
				</tr>';
		}
		$string .= '</table>';

		return $string;
	}

	/**
	* Class version - Finds a module version by including the file and creating one then accessing the local version number (need to be updated to use an accessor).
	*
	* @see choose_system.
	*
	* @param	string			systemname	The subdirectory that comes after systems.
	* @param	int				XXX			A three digit number corrisponding to the module number that you are quering.
	*
	* @return	string|boolean 	mixed		The formed HTML.
	*/
	public function module_ver($file, $num)
	{
		// Do not include IDIR below, already included in calling function choose_system().
		$modulepath = 'impex/systems/' . $file . '/' . $num . '.php';

		$details = array('title' => '', 'version' => '', 'homepage' => '');
		$tit = $ver = $hom = FALSE;

		if (file_exists($modulepath))
		{
			$base_file = file($modulepath);
		}
		else
		{
			return false;
		}

		$details['product'] = 'forum';

		foreach($base_file AS $line)
		{
			$line = trim($line);

			if (strpos($line, '$_product'))
			{
				$details['product'] = substr($line, strpos($line, "'") + 1, -2);
				#$prod = true;
			}

			if (strpos($line, '$_modulestring'))
			{
				$details['title'] = substr($line, strpos($line, "'") + 1, -2);
				$tit = true;
			}

			if (strpos($line, '$_version'))
			{
				$details['version'] = substr($line, strpos($line, "'") + 1, -2);
				$ver = true;
			}

			if (strpos($line, '$_homepage'))
			{
				$details['homepage'] = substr($line, strpos($line, "'") + 1, -2);
				$hom = true;
			}

			if ($tit AND $ver AND $hom)
			{
				continue;
			}
		}

		unset($base_file);

		return $details;
	}

	/**
	* Choose system - Lists the available systems to be imported from depending on what is in systems local version number (need to be updated to use an accessor).
	*
	* @param	object	sessionobject	The current session object
	*
	* @return	string 	mixed			The formed HTML
	*/
	public function choose_system(&$sessionobject)
	{
		$return = $this->do_form_header('index', 'post');
		$return .= $this->make_table_header($this->phrases['title']);
		$return .= $this->make_hidden_code('module', '000');
		$form .= $this->phrases['select_system'] . '   <select name="system">';
		$each = '<hr /><h4 align="center">' . $this->phrases['installed_systems'] . '</h4><table width="100%">';

		$systems_list = array();
		$system_details = array();

		if ($handle = opendir(IDIR . '/systems'))
		{
			while (false !== ($file = readdir($handle)))
			{
				if ($file[0] != '.' AND $file != '.svn' AND $file != 'index.html' AND substr($file, -4) != '.zip' )
				{
					if ($details = $this->module_ver($file, '000'))
					{
						$system_details["$file"] = $details;

						$title = $details['title'];
						$product = $details['product'];

						if (doubleval($details['version']))
						{
							$title .= " ($details[version])";
						}
					}
					else
					{
						$title = $file;
					}
					$systems_list[$product]["$file"] = $title;
				}
			}
			closedir($handle);
		}

		foreach($systems_list AS $id => $product)
		{
			natcasesort($systems_list[$id]);
		}

		$system_count = min(3, count($systems_list));
		$each .= '<tr>';

		do
		{
			$each .= '<th>' . $this->phrases['system'] . '</th><th>' . $this->phrases['version'] . '</th>';
			$system_count--;
		}
		while ($system_count > 0);

		$each .= '</tr><tr>';

		$i = 1;
		$rows = 0;

		foreach ($systems_list AS $product => $product_array)
		{
			$form .= '<optgroup label="' . $product . '">';

			foreach ($product_array AS $file => $title)
			{
				$form .= '<option value="' . $file . '">' . $i . ' . ' . $title . '</option>';

				if ($system_details["$file"])
				{
					$details =& $system_details["$file"];
					$rows++;

					$each .=  '
						<td>' . $i . ' . <a target="_blank" href="' . $details['homepage'] .'">' . $details['title'] . '</a></td>
						<td align="center">  <b>' . $details['version'] . '</b></td>
					';

					if ($rows == 3)
					{
						$each .= '</tr><tr>';
						$rows = 0;
					}
				}

				$i++;
			}
			$form .= '</optgroup>';
		}

		$form .= '</select>';
		$each .= '</tr></table>';

		$to = $this->phrases['select_target_system'] . '<select name="targetsystem">';

		foreach ($this->_target_versions AS $product => $prod_list)
		{
			$to .= '<optgroup label="' . $product . '">';

			foreach($prod_list AS $ver => $text)
			{
				$to .= "<option value=\"" . $ver . "\">" . $text . "</option>";
			}

			$to .= '</optgroup>';
		}
		$to .= "</select>";
		$return .= $this->make_description($form, 0, 1, '', 'center');
		$return .= $this->make_description($to, 0, 1, '', 'center');
		$return .= $this->make_description($each, 0, 1, '', 'center');
		$return .= $this->do_form_footer($this->phrases['start_import'], $this->phrases['reset']);

		return $return;
	}

	/**
	* HTML Page code - select.
	*
	* @param	string	mixed	The output to be displayed, usually the $_screenstring.
	*
	* @return	string 	mixed	The formed HTML.
	*/
	public function make_select($select_array, $select_name)
	{
		$return_string = '<select name="' . $select_name . '">';

		foreach ($select_array AS $select_value => $select_display)
		{
			$return_string .= '<option value="' . $select_value . '">' . $select_display . '</option>';
		}
		$return_string .= '</select>';

		return $return_string;
	}

	/**
	* Outputs the content of the header before index.php is called, ensures that the <html>, <head> and <body> tags are outputted correctly and not disrupted by an echo() etc.
	*
	* Output is augmented by object state and internal flags.
	*
	* @param	string	mixed	The output to be displayed, usally the $_screenstring.
	*
	* @return	string 	mixed	The formed HTML.
	*/
	public function display_now($screentext)
	{
		$string = $this->page_header();

		$this->_screenbasic['displaymodules'] = 'FALSE';

		$string .= $screentext;
		echo "\n" . $string;
		flush();
	}

	/**
	* HTML Page code - Displays an error.
	*
	* @param	string	screentext	Error text.
	*
	* @return	string	mixed		The formed HTML.
	*/
	public function display_error($screentext)
	{
		$this->display_now($screentext);
	}

	/**
	* HTML Page code - returns the html page header code depending on the internal flag autosubmit
	*
	* @return	string 	mixed	The formed HTML
	*/
	public function page_header()
	{
		if ($this->_screenbasic['donehead'] != 'FALSE')
		{
			return '';
		}

		if (!$this->_screenbasic['title'] OR $this->_screenbasic['title'] == 'NONE')
		{
			$outtitle = $this->phrases['title'];
		}
		else
		{
			$outtitle = $this->_screenbasic['title'];
		}

		$css = '<style type="text/css">.isucc { color: green; } .ifail { color: red; }</style><link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />';

		if ($this->_screenbasic['autosubmit'] == '0')
		{
			$string = '<html><head><title>' . $outtitle . '</title>' . $css . '</head><body>';
		}
		else
		{
			$string = '<html><head><title>' . $outtitle . '</title>' . $css . '</head><body onload="document.name.submit();">';
		}

		$string .= '<b>' . $this->phrases['remove'] . '</b>';
		$string .= '<b><br>' . $this->phrases['build_version'] . $this->_build_version . '</b>';

		$this->_screenbasic['donehead'] = 'TRUE';

		return $string;
	}

	/**
	* HTML Page code - returns the html page footer code.
	*
	* @return	string 	mixed	The formed HTML.
	*/
	public function page_footer()
	{
		$string = '<br /><table align="center"><tr><td><a href="' . $this->phrases['finished_import_url'] . '" target="_blank">' . $this->phrases['finished_import'] . '</a></td></tr></table>';
		$string .= '</body></html>';
		return $string;
	}

	/**
	* Main display - returns the current HTML stored in the object.
	*
	* @see		display_modules.
	* @see		choose_system.
	*
	* @param	object	sessionobject	The current session object.
	*
	* @return	string 	mixed	The formed HTML.
	*/
	public function display(&$sessionobject)
	{
		if ($this->_screenbasic['showwarning'] == 'TRUE')
		{
			$string .= $this->_screenbasic['warning'];
		}

		if ($this->_screenbasic['displaymodules'] != 'FALSE')
		{
			$string .= $this->display_modules($sessionobject);
		}

		if ($this->_screenbasic['choosesystem'] == 'TRUE')
		{
			$string .= $this->choose_system($sessionobject);
		}

		$string .= $this->_screenbasic['pageHTML'];

		return $string;
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

		return '<p align="center">' . $this->phrases['module'] . ' : <b>' . $modulestring . '</b>. <i>' . $this->phrases['successful'] . '</i>, ' . $secondphrase . '.</p>
				<p align="center">' . $this->phrases['successful'] . ': <b>' . $successful . '</b>. ' . $this->phrases['failed'] . ': <b>' . $failed . '</b>.</p>';
	}

	/**
	* HTML Page code - Prints a redirect code.
	*
	* @param	string	gotopage	Page number to be redirected.
	* @param	string	timeout		Timeout time.
	*
	* @return	string	mixed		The formed HTML.
	*/
	public function print_redirect($gotopage, $timeout = 0.5)
	{
		if (step_through)
		{
			return $this->print_redirect_001($gotopage, $timeout);
		}
		else
		{
			$rt = '';

			// performs a delayed javascript page redirection
			// get rid of &amp; if there are any...
			$gotopage = str_replace('&amp;', '&', $gotopage);

			$rt .= '<p align="center" class="smallfont"><a href="' . $gotopage . '" onclick="clearTimeout(timerID);"></a></p>';
			$rt .= "\n<script type=\"text/javascript\">\n";

			if ($timeout == 0)
			{
				$rt .= 'window.location="' . $gotopage . '";';
			}
			else
			{
				$rt .= "myvar = \"\"; timeout = " . ($timeout * 10) . ";
				function exec_refresh()
				{
					window.status=\"Redirecting\"+myvar; myvar = myvar + \" .\";
					timerID = setTimeout(\"exec_refresh();\", 100);
					if (timeout > 0)
					{ timeout -= 1; }
					else { clearTimeout(timerID); window.status=\"\"; window.location=\"$gotopage\"; }
				}
				exec_refresh();";
			}

			$rt .= "\n</script>\n";

			// No return here, we want to have the JS code to work, not something to display.
			echo $rt;
		}
	}

	/**
	* HTML Page code - Prints a redirect code.
	*
	* @param	string	gotopage	Page number to be redirected.
	* @param	string	timeout		Timeout time.
	*
	* @return	string	mixed		The formed HTML.
	*/
	public function print_redirect_001($gotopage, $timeout = 0.5)
	{
		$rt = '<br />';
		$rt .= $this->do_form_header('index', '');
		$rt .= $this->do_form_footer($this->phrases['continue'], '');
		return $rt;
	}

	/**
	* HTML Page code - Prints passing code per page.
	*
	* @param	string	count			Actual value of passed items.
	* @param	string	datatypename	Name of the type of data.
	* @param	string	startat			Value of where to start at.
	*
	* @return	string	mixed			The formed HTML.
	*/
	public function print_per_page_pass($count, $datatypename, $startat)
	{
		$rt = $this->make_description('<b>' . $this->phrases['importing'] . ' ' . $count . ' ' . $datatypename . '</b><br /><br /><b>' . $this->phrases['from'] . '</b> : ' . (($count + $startat == 0) ? 0 : ($startat + 1)) . ' :: <b>' . $this->phrases['to'] . '</b> : ' . ($startat + $count) . '');

		return $rt;
	}

	/**
	* Construct Phrase
	*
	* this function is actually just a wrapper for sprintf but makes identification of phrase code easier
	* and will not error if there are no additional arguments. The first parameter is the phrase text, and
	* the (unlimited number of) following parameters are the variables to be parsed into that phrase.
	*
	* @param	string	Text of the phrase
	* @param	mixed	First variable to be inserted
	* ..		..		..
	* @param	mixed	Nth variable to be inserted
	*
	* @return	string	The parsed phrase
	*/
	public function construct_phrase()
	{
		$args = func_get_args();
		$numargs = sizeof($args);

		// FAIL SAFE: check if only parameter is an array,
		// if so we should have called construct_phrase_from_array instead
		if ($numargs == 1 AND is_array($args[0]))
		{
			return construct_phrase_from_array($args[0]);
		}

		// if this function was called with the phrase as the first argument, and an array
		// of paramters as the second, combine into single array for construct_phrase_from_array
		else if ($numargs == 2 AND is_string($args[0]) AND is_array($args[1]))
		{
			array_unshift($args[1], $args[0]);
			return construct_phrase_from_array($args[1]);
		}

		// otherwise just package arguments up as an array
		// and call the array version of this func
		else
		{
			return construct_phrase_from_array($args);
		}
	}

	/**
	* Construct Phrase from Array
	*
	* this function is actually just a wrapper for sprintf but makes identification of phrase code easier
	* and will not error if there are no additional arguments. The first element of the array is the phrase text, and
	* the (unlimited number of) following elements are the variables to be parsed into that phrase.
	*
	* @param	array	array containing phrase and arguments
	*
	* @return	string	The parsed phrase
	*/
	public function construct_phrase_from_array($phrase_array)
	{
		$numargs = sizeof($phrase_array);

		// if we have only one argument then its a phrase
		// with no variables, so just return it
		if ($numargs < 2)
		{
			return $phrase_array[0];
		}

		// call sprintf() on the first argument of this function
		$phrase = @call_user_func_array('sprintf', $phrase_array);
		if ($phrase !== false)
		{
			return $phrase;
		}
		else
		{
			// if that failed, add some extra arguments for debugging
			for ($i = $numargs; $i < 10; $i++)
			{
				$phrase_array["$i"] = "[ARG:$i UNDEFINED]";
			}
			if ($phrase = @call_user_func_array('sprintf', $phrase_array))
			{
				return $phrase;
			}
			// if it still doesn't work, just return the un-parsed text
			else
			{
				return $phrase_array[0];
			}
		}
	}
}

class CLI_ImpExDisplay extends ImpExDisplay
{
	function update_html($html) { return true; }

	function update_basic($name, $status) { return true; }
	function make_input_code($title, $name, $value = '', $htmlise = 1, $size = 35) { return true; }
	function make_table_header($title, $htmlise = 1, $colspan = 2) { return true; }
	function get_row_bg() { return true; }
	function do_form_header($phpscript, $action, $uploadform = 0, $addtable = 1, $name = 'name') { return true; }
	function do_form_footer($submitname = 'Submit', $resetname = 'Reset', $colspan = 2, $goback = '') { return true; }
	function make_description($text, $htmlise = 0) { return true; }
	function make_hidden_code($name, $value = '', $htmlise = 1) { return true; }
	function make_yesno_code($title, $name, $value = 1) { return true; }
	function display_modules(&$sessionobject)  { return true; }
	function module_ver($file, $num) { return true; }
	function choose_system(&$sessionobject) { return true; }
	function make_select($select_array, $select_name) { return true; }

	function display_now($screentext)
	{
		echo ".";
		return true;
	}

	function display_error($screentext) { return true; }
	function page_header() { return true; }
	function page_footer() { return true; }
	function display(&$sessionobject) { return true; }
	function module_finished($modulestring, $seconds, $successful, $failed) { return true; }
	function print_redirect($gotopage, $timeout = 0.5) { return true; }
	function construct_phrase() { return true; }
}

?>