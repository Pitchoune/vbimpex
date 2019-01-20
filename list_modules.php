<?php

define('IDIR', (($getcwd = getcwd()) ? $getcwd : '.'));

if (!is_file(IDIR . '/ImpExConfig.php'))
{
	echo 'Cannot find ImpExConfig.php, have you configured the file and renamed it ?';
	exit;
}
else
{
	require_once (IDIR . '/ImpExConfig.php');
	require_once (IDIR . '/impex_language_' . $impexconfig['system']['language'] . '.php');
}

// #############################################################################
// Requires
// #############################################################################

require_once (IDIR . '/db_mysql.php');

require_once (IDIR . '/ImpExFunction.php');
require_once (IDIR . '/ImpExDatabaseCore.php');
require_once (IDIR . '/ImpExDatabase_360.php');
require_once (IDIR . '/ImpExModule.php');

require_once (IDIR . '/ImpExSession.php');
require_once (IDIR . '/ImpExController.php');
require_once (IDIR . '/ImpExDisplay.php');
require_once (IDIR . '/ImpExDisplayWrapper.php');

// #############################################################################
// Database connect
// #############################################################################

$ImpEx = new ImpExController();

$dbtype = strtolower($impexconfig['target']['databasetype']);

// Mysql and Mysqli support only - vBulletin never adopted any other storage method.
if ($dbtype == 'mysqli')
{
	$Db_target = new ImpEx_Database_Mysqli($ImpEx);
	$Db_source = new ImpEx_Database_Mysqli($ImpEx);
}
else
{
	$Db_target = new ImpEx_Database_Mysql($ImpEx);
	$Db_source = new ImpEx_Database_Mysql($ImpEx);
}

$Db_target->appname 		= 'vBulletin:ImpEx Target';
$Db_target->appshortname 	= 'vBulletin:ImpEx Target';
$Db_target->database 		= $impexconfig['target']['database'];
$Db_target->type 			= $impexconfig['target']['databasetype'];

$Db_target->connect(
	$impexconfig['target']['database'],
	$impexconfig['target']['server'],
	$impexconfig['target']['port'],
	$impexconfig['target']['user'],
	$impexconfig['target']['password'],
	$impexconfig['target']['persistent'],
	'',
	$impexconfig['target']['charset']
);

// Allow setting of SQL mode, not generally required
if (isset($impexconfig['system']['set_sql_mode']))
{
	$Db_target->force_sql_mode($impexconfig['system']['set_sql_mode']);
}
else
{
	$Db_target->force_sql_mode('');
}

// #############################################################################
// Session start
// #############################################################################

$ImpExDisplay = new ImpExDisplay();
$ImpExDisplay->phrases =& $impex_phrases;

$ImpExSession = new ImpExSession();

$tire_1 = '';
$tire_2 = '';
$tire_3 = '';

foreach(scandir(IDIR . "/systems/") AS $id => $folder)
{
	if ($folder[0] != '.' AND $folder != 'index.html')
	{
		require_once(IDIR . "/systems/" . $folder . "/000.php");
		$classname = $folder . "_000";

		$module = $folder['module'];

		$obj = new $classname($ImpExSession);

		// Main list
		if ($module[0] != '.' AND $module != 'index.html' AND substr($module, 0 -3) != 'bck')
		{
			require_once(IDIR . "/systems/" . $folder . "/000.php");

			$module = $folder . "_000";
			$module_obj = new $module($ImpExDisplay);

			switch ($module_obj->_tier)
			{
				case 1:
					$tire_1 .= "[tr][td]" . $module_obj->_modulestring . "[/td][td]" . $module_obj->_version . "[/td][/tr]\n";
					break;
				case 2:
					$tire_2 .= "[tr][td]" . $module_obj->_modulestring . "[/td][td]" . $module_obj->_version . "[/td][/tr]\n";
					break;
				case 3:
					$tire_3 .= "[tr][td]" . $module_obj->_modulestring . "[/td][td]" . $module_obj->_version . "[/td][/tr]\n";
					break;
				default:
					echo "<br /> Bah : " . $module_obj->_modulestring;
					break;
			}
			unset($module_obj);
		}
		
		foreach (scandir(IDIR . "/systems/" . $folder) AS $id => $module)
		{
			if ($module[0] != '.' AND $module != 'index.html' AND substr($module, 0 -3) != 'bck')
			{
				require_once(IDIR . "/systems/" . $folder . "/" . $module);
				$moduleClass = substr($folder . '_' . $module, 0, -4);

				$moduleobject = new $moduleClass($ImpExDisplay);

				if ($moduleobject->_modulestring != $ImpExDisplay->phrases['check_update_db'] AND $moduleobject->_modulestring != $ImpExDisplay->phrases['associate_users'])
				{
					if (substr($moduleobject->_modulestring, 0, 6) == 'Import')
					{
						echo "<br />[*]" . $moduleobject->_modulestring;
					}
					else
					{
						echo "<br />[b]" . $moduleobject->_modulestring . "[/b]";

						if ($module_obj->_tier)
						{
							echo "<br />[b]Tier = " . $moduleobject->_tier . "[/b]";
						}
						else
						{
							echo "<br />[b]Tier = 2[/b]";
						}
						echo "<br />Source version support in ImpEx = [b]" . $moduleobject->_version . "[/b]";
						echo "<br />[list]";
					}

					if (!$moduleobject->_modulestring)
					{
						#  meh
					}
				}
				unset($moduleobject);
			}
		}
		echo "<br />[/list]<br /><br />";
	}
}

echo "<hr />";

echo "[b]Tier 1[/b]\n[table]" . $tire_1 . "[/table]\n\n\n";
echo "[b]Tier 2[/b]\n[table]" . $tire_2 . "[/table]\n\n\n";
echo "[b]Tier 3[/b]\n[table]" . $tire_3 . "[/table]\n\n\n";

?>