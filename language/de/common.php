<?php
if (! defined('IN_PHPBB'))
{
	exit();
}

if (empty($lang) || ! is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang,
		array(
			'ACP_WOW_GUILD_MEMBER_CHECK' => 'WoW Guild Member Check',
			'ACP_WOW_GUILD_MEMBER_CHECK_EXPLAIN' => 'Settings for WoW Guild Member Check',
			'ACP_WOW_GUILD_MEMBER_CHECK_SETTINGS' => 'Settings',
			'WOW_GUILD_MEMBER_CHECK_LOG_CONFIG_SETTINGS' => 'Wow Guild Member Check Settings updated',
			'WOW_GUILD_MEMBER_CHECK_BATTLENET_BUTTON_EXPLAIN' => 'Fetch characters from battle.net. Required to view the guild internal forum.',
			'WOW_GUILD_MEMBER_CHECK_BATTLENET_BUTTON' => 'Fetch characters from battle.net',
			'WOWMEMBERCHECK_OAUTH_RESULT_TITLE' => 'Fetched characters from battle net',
			'WOWMEMBERCHECK_OAUTH_CLOSE_NOW' => 'You can close this window now.',
			'WOW_GUILD_MEMBER_CHECK_BATTLENET_ACCESS_DENIED' => 'Battle.Net access denied: ',
			'WOW_GUILD_MEMBER_CHECK_BATTLENET_SUCCESS' => 'Battle.Net characters fetched successfully. Characters in guild: ',
			'WOW_GUILD_MEMBER_CHECK_CHARACTERS_IN_GUILD' => 'Characters in guild',
			'WOW_GUILD_MEMBER_CHECK_CRON_RAN' => '<strong>WoW Guild Member Check Cron ran</strong><br/>&raquo; FullSync %s, %s &laquo;',
			'WOW_GUILD_MEMBER_CHECK_CRON_ERROR' => '<strong>WoW Guild Member Check Cron error</strong><br/>&raquo; %s<br/>%s &laquo;',
			'WOW_WEBSERVICE_URL' => 'URL to the webservice',
			'WOW_WEBSERVICE_URL_EXPLAIN' => 'MUST use https in order to work properly with the Battle.Net API.',
			'WOW_WEBSERVICE_GUILDID' => 'Guild-ID for the webservice',
			'WOW_WEBSERVICE_APIKEY' => 'API Key for the webservice',
			'WOW_WEBSERVICE_MACKEY' => 'MAC Key for the webservice',
			'WOW_WEBSERVICE_SYSTEMNAME' => 'System name for the webservice',
			'WOW_WEBSERVICE_AFTERAUTHREDIRECTTO' => 'Redirect target page',
			'WOW_WEBSERVICE_AFTERAUTHREDIRECTTO_EXPLAIN' => 'Page to show after user authorized us to fetch his characters. Usually &lt;Forum-URL&gt;/app.php/wowguildmembercheck/oauthtarget .',
			'WOW_CRON_FULL_CHECK_INTERVAL' => 'Full check for guild groups',
			'WOW_CRON_FULL_CHECK_INTERVAL_EXPLAIN' => 'After this interval all members of the guild-member-groups are checked.',
			'WOW_AUTH_MSG' => 'Um den Gilden-internen Teil des Forums zu sehen verknüpfe bitte deinen Battle.net-Account mit dem Forum.',
			'WOW_AUTH_MSG' => 'Du hast noch keinen Zugriff auf das Forum. Bitte verknüpfe deinen Battle.net Account.',
			'WOW_AUTH_HELP_TEXT' => 'Hilfe',
			'WOW_AUTH_BUTTON' => 'Verknüpfen'
		));
