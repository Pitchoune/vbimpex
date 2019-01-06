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
* Common specific vB functions.
*
* If a function is used in more that two places it goes here.
*
* @package 		ImpEx
*/

if (!defined('IDIR')) { die; }

class ImpExFunction
{
	var $source_table_cache = array();

	/**
	* Constructor
	*/
	public function __constructor()
	{
	}

	/**
	* Checks the cache of a table.
	*
	* @param	string		tablename	Name of the table.
	*
	* @return	boolean		mixed		True|False
	*/
	public function check_table_cache($tablename)
	{
		if (in_array($tablename, $this->source_table_cache))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	* Returns unix timestamp from a timestamp(14).
	*
	* @param	string	mixed	The string to parse.
	*
	* @return	int		mixed	Unix timestamp.
	*/
	public function time_to_stamp($old_date)
	{
		return mktime(substr($old_date, 8, 2), substr($old_date, 10, 2), substr($old_date, 12, 2), substr($old_date, 4, 2), substr($old_date, 6, 2), substr($old_date, 0, 4));
	}

	/**
	* Converts any passed value into boolean value.
	*
	* @param	string		optionstring	String of the value to pass.
	*
	* @return	boolean		mixed			Boolean value.
	*/
	public function option2bin($optionstring)
	{
		$optionstring = strtolower(trim($optionstring));

		switch ($optionstring)
		{
			case 'yes':
			case 'is':
			case 'one':
			case 'on':
			case 'true':
			case 'y':
				return 1;

			case 'no':
			case 'is not':
			case 'off':
			case 'false':
			case 'n':
				return 0;

			default:
				return $optionstring;
		}
	}

	/**
	* Checks if given birthday corresponds to a COPPA user following current date.
	*
	* @param	string	birthday	Birthday date. Can be in format YYYY-MM-DD or unixtime.
	*
	* @return	array	Array of values with COPPA status.
	*/
	public function is_coppa($birthday)
	{
		$return_array = array(
			'status' 	=> false,
			'is_coppa'	=> true
		);

		$date_bits = array();
		$unix_ts = 0;

		if (stristr($birthday, "-"))
		{
			//Its YYYY-MM-DD
			$date_bits = explode('-', $birthday);

			if (!checkdate(intval($date_bits[1]), intval($date_bits[2]), intval($date_bits[0])))
			{
				return $return_array;
			}

			$birthday = @mktime(0, 0, 0, $date_bits[1], $date_bits[2], $date_bits[0]);
		}

		// 410240038 13 years of seconds
		if ($birthday > (time() - 410240038))
		{
			$return_array['status'] = true;
			$return_array['is_coppa'] = true;
		}
		else
		{
			$return_array['status'] = true;
			$return_array['is_coppa'] = false;
		}

		return $return_array;
	}

	/**
	* Deprecated.
	*/
	public function iif($expression, $returntrue, $returnfalse = '')
	{
		return $expression ? $returntrue : $returnfalse;
	}

	/**
	* Converts specific entities into usual characters into given text.
	*
	* @param	string	text	Text to change characters.
	*
	* @return	string	mixed	Text converted.
	*/
	public function unhtmlspecialchars($text)
	{
		return str_replace(array('&lt;', '&gt;', '&quot;', '&amp;'), array('<', '>', '"', '&'), $text);
	}

	/**
	* Parse URLs.
	*
	* @param	string	messagetext	Text of message to parse.
	*
	* @return	string	mixed		Text parsed.
	*/
	public function vb_parse_url($messagetext)
	{
		$taglist = '\[b|\[i|\[u|\[color|\[size|\[font|\[left|\[center|\[right|\[indent|\[quote|\[highlight|\[\*';

		$urlSearchArray = array(
			"#(^|(?<=[^_a-z0-9-=\]\"'/@]|(?<=" . $taglist . ")\]))((https?|ftp|gopher|news|telnet)://|www\.)((\[(?!/)|[^\s[()^$!`\"'|{}<>])+)(?!\[/url|\[/img)(?=[,.]*([\s)[]|$))#siU"
		);

		$urlReplaceArray = array(
			"[url]\\2\\4[/url]"
		);

		$emailSearchArray = array(
			"/([ \n\r\t])([_a-z0-9-]+(\.[_a-z0-9-]+)*@[^\s]+(\.[a-z0-9-]+)*(\.[a-z]{2,4}))/si",
			"/^([_a-z0-9-]+(\.[_a-z0-9-]+)*@[^\s]+(\.[a-z0-9-]+)*(\.[a-z]{2,4}))/si"
		);

		$emailReplaceArray = array(
			"\\1[email]\\2[/email]",
			"[email]\\0[/email]"
		);


		$text = preg_replace($urlSearchArray, $urlReplaceArray, $messagetext);

		if (strpos($text, '@'))
		{
			$text = preg_replace($emailSearchArray, $emailReplaceArray, $text);
		}

		if ($text)
		{
			return $text;
		}

		return $messagetext;
	}

	/**
	* Emulates file_get_contents() PHP function if needed.
	*
	* @param	string	filename	Path of the file.
	*
	* @return	mixed	mixed		File content if valid.
	*/
	public function vb_file_get_contents($filename)
	{
		if (function_exists('file_get_contents'))
		{
			if ($handle = @fopen ($filename, "rb"))
			{
				return file_get_contents($filename);
			}
		}
		else
		{
			if ($handle = @fopen ($filename, "rb"))
			{
				do
				{
					$data = fread($handle, 1);
					if (strlen($data) == 0)
					{
						break;
					}
					$contents .= $data;
				} while(true);

				@fclose ($handle);
				return $contents;
			}
		}
		return false;
	}

	/**
	* Callback function to parse smilies.
	*
	* @param	string	imgsrc		URL of the image.
	* @param	string	fulltag		Full tag of the smilie.
	* @param	array	smilies		Array of smilies.
	*
	* @return	string	mixed		Smilie image url.
	*/
	public function parse_smilie_callback_php5($imgsrc, $fulltag, $smilies)
	{
		// strip extra quotes added by /e modifier
		$imgsrc = str_replace('\"', '"', $imgsrc);
		$fulltag = str_replace('\"', '"', $fulltag);

		if (isset($smilies["$imgsrc"]))
		{
			// found this smilie by image location, replace it
			return $smilies["$imgsrc"];
		}
		else
		{
			// didn't find a smilie, so it probably isn't one
			return $fulltag;
		}
	}

	/**
	* Converts HTML code to BB Code.
	*
	* @param	string	htmlcode		HTML code to convert.
	* @param	int		parse_smilies	Parse smilies?
	* @param	int		parse_urls		Parse URLs?
	*
	* @return	string	mixed			Converted HTML code into BB Code.
	*/
	public function html_2_bb($htmlcode, $parse_smilies = 1, $parse_urls = 1)
	{
		$smilies = $this->_smilies;

		// line breaks
		$htmlcode = preg_replace('#<br\s*/?>#i', "\n", $htmlcode);
		$htmlcode = preg_replace('#<p\s*/?>#i', "\n\n", $htmlcode);

		// do smilies
		if ($parse_smilies == 1)
		{
			$htmlcode = preg_replace('#<img[^>]*src=("|\')(.*)\\1[^>]*/??>#iUe', "\$this->parse_smilie_callback_php5('\\2', '\\0', \$smilies)", $htmlcode);
			$htmlcode = preg_replace('#<img[^>]*src=([^"\' ]*) [^>]*/??>#iUe', "\$this->parse_smilie_callback_php5('\\1', '\\0', \$smilies)", $htmlcode);
		}

		// images (beyond any smilies stripped above)
		$htmlcode = preg_replace('#<img[^>]*src=("|\')(.*)\\1[^>]*/??>#iU', '[img]$2[/img]', $htmlcode);

		// bold and italics
		$htmlcode = preg_replace('#<(/?(b|i))>#i', '[$1]', $htmlcode);
		$htmlcode = preg_replace('#<(/?)strong>#i', '[$1b]', $htmlcode);
		$htmlcode = preg_replace('#<(/?)em>#i', '[$1i]', $htmlcode);

		// catch pretty much any email address...
		$htmlcode = preg_replace('#<a[^>]*href=("|\')mailto:(.*)\\1[^>]*>(.*)</a>#iU', '[email=$1$2$1]$3[/email]', $htmlcode);
		$htmlcode = preg_replace('#<a[^>]*href=mailto:([^"\' ]*) [^>]*>(.*)</a>#iU', '[email="$1"]$2[/email]', $htmlcode);

		// ...same with urls
		$htmlcode = preg_replace('#<a[^>]*href=("|\')(.*)\\1[^>]*>(.*)</a>#iU', '[url=$1$2$1]$3[/url]', $htmlcode);
		$htmlcode = preg_replace('#<a[^>]*href=([^"\' ]*) [^>]*>(.*)</a>#iU', '[url="$1"]$2[/url]', $htmlcode);

		// do code tags
		$htmlcode = preg_replace('#<BLOCKQUOTE><font[^>]*>code:</font><hr(\s+/)?><pre>(.*)</pre><hr(\s+/)?></BLOCKQUOTE>#siU', '[code]$1[/code]', $htmlcode);

		// do quotes
		$htmlcode = preg_replace('#<blockquote><font[^>]*>quote:<hr(\s+/)?><font[^>]*>(.*)</font><hr(\s+/)?></blockquote>(<font[^>]*>)?#siU', '[quote]$3[/quote]', $htmlcode);
		$htmlcode = preg_replace('#(</font>)?<blockquote><font[^>]*>quote:</font><hr(\s+/)?><font[^>]*>(.*)</font><hr(\s+/)?></blockquote>(<font[^>]*>)?#siU', '[quote]$3[/quote]', $htmlcode);

		// Final catch !
		$htmlcode = preg_replace('#</font><blockquote><font class="small">Quote:</font><hr />#','[ QUOTE ]',$htmlcode);
		$htmlcode = preg_replace('#<hr /></blockquote><font class="post">#','[/ QUOTE ]',$htmlcode);

		// umm.. this one's pretty ugly :)
		$htmlcode = preg_replace("#</p> <small> </small> <pre style=\"font-size:x-small; font-family: monospace;\"> </pre> <STRONG> </strong> <blockquote><font size=\"1\" face=\"([^\"]+)\">quote:</font><hr /><font size=\"2\" face=\"([^\"]+)\"> <hr /></blockquote>#si", "[quote]", $htmlcode);

		// do lists
		$htmlcode = preg_replace('#<ul(\s+type=("?)square\\1)>#iU', '[list]', $htmlcode);
		$htmlcode = preg_replace('#<ol type=("?)(a|A|i|I|1)\\1>#iU', '[list=$2]', $htmlcode);
		$htmlcode = preg_replace('#<ol[^>]*>#iU', '[list=1]', $htmlcode);
		$htmlcode = preg_replace('#</(ol|ul)>#i', '[/list]', $htmlcode);
		$htmlcode = preg_replace('#<li>#U', '[*]', $htmlcode);
		$htmlcode = preg_replace('#</li>#U', '', $htmlcode);

		$htmlcode = str_replace('&nbsp;', '', $htmlcode);

		// any stray comments
		$htmlcode = preg_replace('#<!--.*-->#U', '', $htmlcode);

		// misc stuff
		$htmlcode = preg_replace('#<small>(.*)</small>#siU', '[size="1"]$1[/size]', $htmlcode);

		if ($parse_urls)
		{
			$htmlcode=$this->vb_parse_url($htmlcode);
		}

		return $htmlcode;
	}

	/**
	* Easter egg ;)
	*/
	public function all_your_posts_are_belong_to_us($post)
	{
		return $post;
	}

	/**
	* Simple path checker
	*
	* @param	object	displayobject	The displayobject
	* @param	object	sessionobject	The current session object
	* @param	string	mixed			The full path
	*
	* @return	boolean
	*/
	public function check_path(&$displayobject, &$sessionobject, &$path)
	{
		if (is_dir($path))
		{
			$displayobject->display_now('<br /><b>path</b> - ' . $path . ' <font color="green"><i>' . $this->phrases['ok'] . '</i></font>');
			return true;
		}
		else
		{
			$displayobject->display_now('<br /><b>' . $path . '</b> - <font color="red"><i>' . $this->phrases['not_ok'] . '</i></font>');
			$sessionobject->add_error('fatal', $this->_modulestring, $path . $this->phrases['path_is_incorrect'], $this->phrases['check_board_structure']);
			return false;
		}
	}

	/**
	* Simple file checker
	*
	* @param	object	displayobject	The displayobject
	* @param	object	sessionobject	The current session object
	* @param	string	mixed			The full path and filename
	*
	* @return	boolean
	*/
	public function check_file(&$displayobject, &$sessionobject, &$file)
	{
		if (is_file($file))
		{
			$displayobject->display_now('<br /><b>file</b> - ' . $file . ' <font color="green"><i>' . $this->phrases['ok'] . '</i></font>');
			return true;
		}
		else
		{
			$displayobject->display_now('<br /><b>' . $file . '</b> - <font color="red"><i>' . $this->phrases['not_ok'] . '</i></font>');
			$sessionobject->add_error('fatal', $this->_modulestring, $file . $this->phrases['path_is_incorrect'], $this->phrases['check_board_structure']);
			return false;
		}
	}

	/**
	* Scans a given directory.
	*
	* @param	string	dirstr		Directory root.
	*
	* @return	string	mixed		List of files if files are found.
	*/
	public function scandir($dirstr)
	{
		if (!is_dir($dirstr))
		{
			return false;
		}

		if (!function_exists("scandir"))
		{
			$files = array();

			if (is_dir($dirstr))
			{
				$fh = opendir($dirstr);

				while (false !== ($filename = readdir($fh)))
				{
					array_push($files, $filename);
				}

				closedir($fh);
				return $files;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return scandir($dirstr);
		}
	}

	/**
	* Fetchs the attachment path.
	*
	* @param	int			userid			User ID which attachment is owned.
	* @param	string		base_path		Base path of the attachment.
	* @param	string		as_new			Expanded paths?
	* @param	int			attachmentid	Attachment ID to fetch path.
	* @param	boolean		thumb			Thumb required?
	*
	* @return	string		mixed			Attachment path.
	*/
	public function fetch_attachment_path($userid, $base_path, $as_new, $attachmentid = 0, $thumb = false)
	{
		if ($as_new) // expanded paths
		{
			$path = $base_path . '/' . implode('/', preg_split('//', $userid,  -1, PREG_SPLIT_NO_EMPTY));
		}
		else
		{
			$path = $base_path . '/' . $userid;
		}

		if ($attachmentid)
		{
			if ($thumb)
			{
				$path .= '/' . $attachmentid . '.thumb';
			}
			else
			{
				$path .= '/' . $attachmentid . '.attach';
			}
		}

		return $path;
	}

	/**
	* Recursive creation of file path.
	*
	* @param	string		path	Path of the directory.
	* @param	int			mode	Mode of the directory.
	*
	* @return	boolean
	*/
	public function vbmkdir($path, $mode = 0777)
	{
		if (is_dir($path))
		{
			if (!(is_writable($path)))
			{
				@chmod($path, $mode);
			}
			return true;
		}
		else
		{
			$oldmask = @umask(0);
			$partialpath = dirname($path);

			if (!$this->vbmkdir($partialpath, $mode))
			{
				return false;
			}
			else
			{
				return mkdir($path, $mode);
			}
		}
	}

	/**
	* Checks the size of the given avatar.
	*
	* @param	string	url				URL of the avatar.
	* @param	int		size_allowed	Size allowed for the avatar.
	*
	* @return	boolean
	*/
	public function check_avatar_size(&$url, &$size_allowed)
	{
		if ($url AND @fopen($url, 'r'))
		{
			$size = strlen($this->vb_file_get_contents($url));

			if ($size >	intval($size_allowed))
			{
				return $size;
			}
			else
			{
				return true;
			}
		}
		else
		{
			return false;
		}
	}

	/**
	* Save content into a file.
	*
	* @param	string	filename	Path and name of the file.
	* @param	string	contents	Contents to write into the file.
	*
	* @return	boolean
	*/
	public function vb_file_save_contents($filename, $contents)
	{
		if (function_exists('file_put_contents'))
		{
			return file_put_contents($filename, $contents);
		}
		else
		{
			if ($handle = @fopen ($filename, "w"))
			{
				$result = fwrite($handle, $contents);
				@fclose ($handle);
				if ($result)
				{
					return true;
				}
			}
		}
		return false;
	}	
}

?>