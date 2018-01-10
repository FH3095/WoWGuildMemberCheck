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
	'ACP_WOW_GUILD_MEMBER_CHECK'			=> 'WoW Guild Member Check',
	'ACP_WOW_GUILD_MEMBER_CHECK_EXPLAIN'	=> 'TODO_SETTINGS',
	'ACP_WOW_GUILD_MEMBER_CHECK_SETTINGS'	=> 'Settings',
	'WOW_GUILD_MEMBER_CHECK_LOG_CONFIG_SETTINGS'	=> 'Wow Guild Member Check Settings updated',
));
