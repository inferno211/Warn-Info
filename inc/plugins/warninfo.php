<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.");
}


function warninfo_info()
{
	return array(
		"name"			=> "Warn info in post",
		"description"	=> "Show information about warn in post.",
		"website"		=> "http://github.com/inferno211",
		"author"		=> "Piotr `Inferno` Grencel",
		"authorsite"	=> "http://github.com/inferno211",
		"version"		=> "1.0.1",
		"guid" 			=> "",
		"codename"		=> "",
		"compatibility" => "18*"
	);
}

function warninfo_install()
{
	global $db, $mybb;

	$setting_group = array(
		'name' => 'warninfo_group',
		'title' => 'Warn info in post',
		'description' => 'Show information about warn in post.',
		'disporder' => 5,
		'isdefault' => 0
	);

	$gid = $db->insert_query("settinggroups", $setting_group);

	$setting_array = array(
		'warninfo_on' => array(
			'title' => 'Turn on',
			'description' => 'Plugin enabled?',
			'optionscode' => 'onoff',
			'value' => 1,
			'disporder' => 1
		),
		'warninfo_date' => array(
			'title' => 'Date format',
			'description' => 'Format of date?',
			'optionscode' => 'text',
			'value' => 'j-m-Y, G:i',
			'disporder' => 2
		),
	);

	foreach($setting_array as $name => $setting)
	{
		$setting['name'] = $name;
		$setting['gid'] = $gid;

		$db->insert_query('settings', $setting);
	}
	rebuild_settings();

	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";

	find_replace_templatesets(
		"postbit",
		"#" . preg_quote('{$post[\'message\']}') . "#i",
		'{$post[\'message\']}{$post[\'warninfo\']}'
	);

	find_replace_templatesets(
		"postbit_classic",
		"#" . preg_quote('{$post[\'message\']}') . "#i",
		'{$post[\'message\']}{$post[\'warninfo\']}'
	);
}

function warninfo_is_installed()
{
	global $mybb;
	if(isset($mybb->settings['warninfo_on']))
	{
		return true;
	}
	return false;
}

function warninfo_uninstall()
{
	global $db;

	$db->delete_query("settinggroups", "name=\"warninfo_group\"");
	$db->delete_query("settings", "name LIKE \"warninfo%\"");

	rebuild_settings();

	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";

	find_replace_templatesets(
		"postbit",
		"#" . preg_quote('{$post[\'message\']}{$post[\'warninfo\']}') . "#i",
		'{$post[\'message\']}'
	);

	find_replace_templatesets(
		"postbit_classic",
		"#" . preg_quote('{$post[\'message\']}{$post[\'warninfo\']}') . "#i",
		'{$post[\'message\']}'
	);
}

$plugins->add_hook("postbit", "warninfo");

function warninfo(&$post)
{
	global $mybb, $db, $lang;
	if(!$mybb->settings['warninfo_on']) return 0;
	$lang->load('warninfo');

	$post['warninfo'] = '';

	$query = $db->simple_select("warnings", "*", "`pid`='".$post['pid']."'");
	if($db->num_rows($query) != 0)
	{
		while($warn = $db->fetch_array($query))
		{
			if($warn['daterevoked'] == 0)
			{
				if($warn['expired'] == 1)
				{
					$expires = $lang->warninfo_expired;
				}
				else
				{
					if ($warn['expires'] != 0) {
					    $expires = date($mybb->settings['warninfo_date'], $warn['expires']);
					} else {
					    $expires = 'Nigdy';
					}
				}
				$admin = get_user($warn['issuedby']);

				$reason = '';
				if($warn['tid'] != 0)
				{
					$query_reason = $db->simple_select("warningtypes", "*", "`tid`='".$warn['tid']."'");
					if($db->num_rows($query_reason) != 0)
					{
						$reason_info = $db->fetch_array($query_reason);
						$reason = $reason_info['title'];
					}
				}
				else
				{
					$reason = $warn['title'];
				}

				$post['warninfo'] .= '
				<div class="red_alert">
					'.$lang->warninfo_info.'<br />
					'.$lang->warninfo_reason.$reason.'<br />
					'.$lang->warninfo_date.date($mybb->settings['warninfo_date'], $warn['dateline']).'<br />
					'.$lang->warninfo_expires.$expires.'<br />
					'.$lang->warninfo_admin.build_profile_link($admin['username'], $warn['issuedby']).'<br />
					'.$lang->warninfo_value.(100/$mybb->settings['maxwarningpoints']) * $warn['points'].'%
				</div>';
			}
		}
	}
}
