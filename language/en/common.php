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
	'WOW_GUILD_MEMBER_CHECK_BATTLENET_BUTTON_EXPLAIN'	=> 'Fetch characters from battle.net. Required to view the guild internal forum.',
	'WOW_GUILD_MEMBER_CHECK_BATTLENET_BUTTON'			=> 'Fetch characters from battle.net',
	'WOWMEMBERCHECK_OAUTH_RESULT_TITLE'			=> 'Fetched characters from battle net',
	'WOWMEMBERCHECK_OAUTH_CLOSE_NOW'			=> 'You can close this window now.',
	'WOW_GUILD_MEMBER_CHECK_BATTLENET_ACCESS_DENIED'	=> 'Battle.Net access denied.',
	'WOW_GUILD_MEMBER_CHECK_BATTLENET_SUCCESS'			=> 'Battle.Net characters fetched successfully. Characters in guild: ',
));
