<?php

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'WOW_CLIENT_ID'			=> 'Client Key',
	'WOW_CLIENT_ID_EXPLAIN'	=> 'You need an account on <a href="https://dev.battle.net" target="_blank">https://dev.battle.net</a> and create an application there. You get a client-key and a client-secret.',
	'WOW_CLIENT_SECRET'		=> 'Client Secret',
	'WOW_GUILD_NAME'		=> 'Guild Name',
	'WOW_GUILD_SERVER'		=> 'Guild Server',
	'WOW_GUILD_REGION'		=> 'Guild Region',
	'WOW_PLEASE_SELECT'		=> '&lt;Please select&gt;',
));
