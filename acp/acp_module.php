<?php

namespace FH3095\WoWGuildMemberCheck\acp;

use OAuth\OAuth2\Service\BattleNet;

// Code heavily inspired by and copied from paul999/ajaxshoutbox

class acp_module {
	public $u_action;
	public $new_config = array();
	public $page_title;
	public $tpl_name;

	public function main($id, $mode)
	{
		global $config, $request, $template, $user, $phpbb_log;

		$user->add_lang_ext('FH3095/WoWGuildMemberCheck', 'acp');


		$submit = $request->is_set('submit');

		$form_key = 'acp_wowguildmembercheck';
		add_form_key($form_key);

		$display_vars = array(
			'title'	=> 'ACP_WOW_GUILD_MEMBER_CHECK',
			'vars'	=> array(
				'legend1'				=> 'ACP_WOW_GUILD_MEMBER_CHECK_SETTINGS',
				'wowmembercheck_client_id'			=> array('lang' => 'WOW_CLIENT_ID',		'validate' => 'string',	'type' => 'text:33',	'explain' => true),
				'wowmembercheck_client_secret'		=> array('lang' => 'WOW_CLIENT_SECRET',	'validate' => 'string',	'type' => 'text:33',	'explain' => false),
				'wowmembercheck_guild_name'			=> array('lang' => 'WOW_GUILD_NAME',	'validate' => 'string',	'type' => 'text:33',	'explain' => false),
				'wowmembercheck_guild_server'		=> array('lang' => 'WOW_GUILD_SERVER',	'validate' => 'string',	'type' => 'text:33',	'explain' => false),
				'wowmembercheck_guild_region'		=> array('lang'	=> 'WOW_GUILD_REGION',	'validate' => 'string', 'type' => 'select',		'explain' => false,
					'method' => 'region_select'),
				/*
				'wow_client_id'			=> array('lang' => 'WOW_CLIENT_ID',			'validate' => 'bool',	'type' => 'radio:yes_no','explain' => false),
				'ajaxshoutbox_prune_days'			=> array('lang' => 'AJAXSHOUTBOX_PRUNE_DAYS',			'validate' => 'int',	'type' => 'number:1:9999','explain' => false, 'append' => ' ' . $user->lang['DAYS']),
				'legend2'				=> 'ACP_AJAXSHOUTBOX_SETTINGS',
				'ajaxshoutbox_date_format'      	=> array('lang' => 'AJAXSHOUTBOX_DEFAULT_DATE_FORMAT',	'validate' => 'string',	'type' => 'custom', 'method' => 'dateformat_select', 'explain' => true),
				*/
				'legend4'				=> 'ACP_SUBMIT_CHANGES',
			)
		);


		$this->new_config = $config;
		$cfg_array = $request->is_set('config') ? utf8_normalize_nfc($request->variable('config', array('' => ''), true)) : $this->new_config;
		$error = array();

		validate_config_vars($display_vars['vars'], $cfg_array, $error);

		if ($submit && !check_form_key($form_key))
		{
			$error[] = $user->lang['FORM_INVALID'];
		}
		// Do not write values if there is an error
		if (sizeof($error))
		{
			$submit = false;
		}

		// We go through the display_vars to make sure no one is trying to set variables he/she is not allowed to...
		foreach ($display_vars['vars'] as $config_name => $null)
		{
			if (!isset($cfg_array[$config_name]) || strpos($config_name, 'legend') !== false)
			{
				continue;
			}

			$this->new_config[$config_name] = $cfg_array[$config_name];

			if ($submit)
			{
				$config->set($config_name, $this->new_config[$config_name]);
			}
		}

		if ($submit)
		{
			$phpbb_log->add('admin', $user->data['user_id'], $user->ip, 'WOW_GUILD_MEMBER_CHECK_LOG_CONFIG_' . strtoupper($mode));

			$message = $user->lang('CONFIG_UPDATED');
			$message_type = E_USER_NOTICE;

			trigger_error($message . adm_back_link($this->u_action), $message_type);
		}

		$this->tpl_name = 'acp_board';
		$this->page_title = $display_vars['title'];

		$template->assign_vars(array(
			'L_TITLE'			=> $user->lang[$display_vars['title']],
			'L_TITLE_EXPLAIN'	=> $user->lang[$display_vars['title'] . '_EXPLAIN'],

			'S_ERROR'			=> (sizeof($error)) ? true : false,
			'ERROR_MSG'			=> implode('<br />', $error),

			'U_ACTION'			=> $this->u_action,
		));

		// Output relevant page
		foreach ($display_vars['vars'] as $config_key => $vars)
		{
			if (!is_array($vars) && strpos($config_key, 'legend') === false)
			{
				continue;
			}

			if (strpos($config_key, 'legend') !== false)
			{
				$template->assign_block_vars('options', array(
					'S_LEGEND'		=> true,
					'LEGEND'		=> (isset($user->lang[$vars])) ? $user->lang[$vars] : $vars)
				);

				continue;
			}

			$type = explode(':', $vars['type']);

			$l_explain = '';
			if ($vars['explain'] && isset($user->lang[$vars['lang'] . '_EXPLAIN']))
			{
				$l_explain =  $user->lang[$vars['lang'] . '_EXPLAIN'];
			}

			$content = build_cfg_template($type, $config_key, $this->new_config, $config_key, $vars);

			if (empty($content))
			{
				continue;
			}

			$template->assign_block_vars('options', array(
				'KEY'			=> $config_key,
				'TITLE'			=> (isset($user->lang[$vars['lang']])) ? $user->lang[$vars['lang']] : $vars['lang'],
				'S_EXPLAIN'		=> $vars['explain'],
				'TITLE_EXPLAIN'	=> $l_explain,
				'CONTENT'		=> $content,

			));

			unset($display_vars['vars'][$config_key]);
		}
	}

	public function region_select($value, $key) {
		global $user;

		$ret = "\n";
		$regions = array(
			$user->lang['WOW_PLEASE_SELECT']  => '',
			'US' => BattleNet::API_URI_US,
			'EU' => BattleNet::API_URI_EU,
			'KR' => BattleNet::API_URI_KR,
			'TW' => BattleNet::API_URI_TW,
			'CN' => BattleNet::API_URI_CN,
			'SEA' => BattleNet::API_URI_SEA,
		);

		foreach($regions AS $regKey => $regValue) {
			$ret .= '<option value="' . $regValue . '" ' . (($value == $regValue) ? 'selected="selected"' : '') . '>' . $regKey . '</option>'  . "\n";
		}
		return $ret;
	}
}
