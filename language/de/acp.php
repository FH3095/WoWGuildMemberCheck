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
	'WOW_PLEASE_SELECT'		=> '&lt;Please select&gt;',
	'WOW_INGUILD_GROUPS'			=> 'Guild member group',
	'WOW_INGUILD_GROUPS_EXPLAIN'	=> '<strong>Every</strong> member of this groups must have a character in the guild. Otherwise he will be removed from this groups.',
	'WOW_OUTOFGUILD_GROUPS'			=> 'Former member group',
	'WOW_OUTOFGUILD_GROUPS_EXPLAIN'	=> 'When a user is removed from the guild groups, he is added to this groups.<br/>When a user again is added to the guild groups, he will be automatically removed from this groups.',
	'WOW_CRON_INTERVAL'				=> 'Interval for cronjob',
	'WOW_CRON_INTERVAL_EXPLAIN'		=> 'The cronjob checks the guild members and changes the forum groups accordingly. Interval is in minutes.',
));
