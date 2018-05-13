<?php

namespace FH3095\WoWGuildMemberCheck\acp;

// Code heavily inspired by and copied from paul999/ajaxshoutbox
class acp_module
{
	public $u_action;
	public $page_title;
	public $tpl_name;
	private $groups = null;
	const ARRAY_FIELDS = array(
		'wowmembercheck_inguild_groups',
		'wowmembercheck_removed_users_groups'
	);

	public function main($id, $mode)
	{
		global $config, $request, $template, $user, $phpbb_log;
		
		$user->add_lang_ext('FH3095/WoWGuildMemberCheck', 'acp');
		
		$is_submit = $request->is_set('submit');
		
		$form_key = 'acp_wowguildmembercheck';
		add_form_key($form_key);
		
		$display_vars = array(
			'legend1' => 'ACP_WOW_GUILD_MEMBER_CHECK_SETTINGS',
			'wowmembercheck_webservice_url' => array(
				'lang' => 'WOW_WEBSERVICE_URL',
				'validate' => 'string',
				'type' => 'text',
				'explain' => true
			),
			'wowmembercheck_webservice_guildId' => array(
				'lang' => 'WOW_WEBSERVICE_GUILDID',
				'validate' => 'int:1:2000000',
				'type' => 'number:1:2000000',
				'explain' => false
			),
			'wowmembercheck_webservice_apiKey' => array(
				'lang' => 'WOW_WEBSERVICE_APIKEY',
				'validate' => 'string',
				'type' => 'text',
				'explain' => false
			),
			'wowmembercheck_webservice_macKey' => array(
				'lang' => 'WOW_WEBSERVICE_MACKEY',
				'validate' => 'string',
				'type' => 'text',
				'explain' => false
			),
			'wowmembercheck_webservice_systemName' => array(
				'lang' => 'WOW_WEBSERVICE_SYSTEMNAME',
				'validate' => 'string',
				'type' => 'text',
				'explain' => false
			),
			'wowmembercheck_webservice_afterAuthRedirectTo' => array(
				'lang' => 'WOW_WEBSERVICE_AFTERAUTHREDIRECTTO',
				'validate' => 'string',
				'type' => 'text',
				'explain' => true
			),
			'wowmembercheck_inguild_groups' => array(
				'lang' => 'WOW_INGUILD_GROUPS',
				'validate' => 'string',
				'type' => 'custom',
				'explain' => true,
				'method' => 'get_groups'
			),
			'wowmembercheck_removed_users_groups' => array(
				'lang' => 'WOW_OUTOFGUILD_GROUPS',
				'validate' => 'string',
				'type' => 'custom',
				'explain' => true,
				'method' => 'get_groups'
			),
			'wowmembercheck_cron_interval' => array(
				'lang' => 'WOW_CRON_INTERVAL',
				'validate' => 'int:1:10080',
				'type' => 'number:1:10080',
				'explain' => true
			),
			'wowmembercheck_cron_full_check_interval' => array(
				'lang' => 'WOW_CRON_FULL_CHECK_INTERVAL',
				'validate' => 'int:1:10080',
				'type' => 'number:1:10080',
				'explain' => true
			),
			'legend4' => 'ACP_SUBMIT_CHANGES'
		);
		

		$cfg_array = $request->is_set('config') ? utf8_normalize_nfc(
				$request->variable('config', array(
					'' => ''
				), true)) : $config;
		$error = array();
		
		validate_config_vars($display_vars, $cfg_array, $error);
		
		foreach (self::ARRAY_FIELDS as $array_field)
		{
			if (1 != preg_match('/^([0-9]+,?)*$/D', $cfg_array[$array_field]))
			{
				$error[] = $user->lang['FORM_INVALID'];
			}
			$cfg_array[$array_field] = trim($cfg_array[$array_field], ',');
		}
		
		if ($is_submit && ! check_form_key($form_key))
		{
			$error[] = $user->lang['FORM_INVALID'];
		}
		// Do not write values if there is an error
		if (sizeof($error))
		{
			$is_submit = false;
		}
		
		// We go through the display_vars to make sure no one is trying to set
		// variables he/she is not allowed to...
		foreach ($display_vars as $config_key => $options)
		{
			// Sanity-Check: Ignore bad display_vars
			if (! is_array($options) && strpos($config_key, 'legend') === false)
			{
				continue;
			}
			
			// Set new value
			if (isset($cfg_array[$config_key]) &&
					 strpos($config_key, 'legend') === false && $is_submit)
			{
				$config->set($config_key, $cfg_array[$config_key]);
			}
			
			// Set template parameter
			if (strpos($config_key, 'legend') !== false)
			{
				$template->assign_block_vars('options',
						array(
							'S_LEGEND' => true,
							'LEGEND' => (isset($user->lang[$options])) ? $user->lang[$options] : $options
						));
			}
			else
			{
				$type = explode(':', $options['type']);
				
				$title_text = $options['lang'];
				if (isset($user->lang[$title_text]))
				{
					$title_text = $user->lang[$title_text];
				}
				
				$explain_text = '';
				if ($options['explain'] &&
						 isset($user->lang[$options['lang'] . '_EXPLAIN']))
				{
					$explain_text = $user->lang[$options['lang'] . '_EXPLAIN'];
				}
				
				$content = build_cfg_template($type, $config_key, $cfg_array,
						$config_key, $options);
				
				if (! empty($content))
				{
					$template->assign_block_vars('options',
							array(
								'KEY' => $config_key,
								'TITLE' => $title_text,
								'S_EXPLAIN' => $options['explain'],
								'TITLE_EXPLAIN' => $explain_text,
								'CONTENT' => $content
							));
				}
			}
		}
		
		if ($is_submit)
		{
			$phpbb_log->add('admin', $user->data['user_id'], $user->ip,
					'WOW_GUILD_MEMBER_CHECK_LOG_CONFIG_' . strtoupper($mode));
			
			$message = $user->lang('CONFIG_UPDATED');
			$message_type = E_USER_NOTICE;
			
			trigger_error($message . adm_back_link($this->u_action),
					$message_type);
		}
		
		$this->tpl_name = 'acp_board';
		$this->page_title = $user->lang['ACP_WOW_GUILD_MEMBER_CHECK'];
		
		$template->assign_vars(
				array(
					'L_TITLE' => $user->lang['ACP_WOW_GUILD_MEMBER_CHECK'],
					'L_TITLE_EXPLAIN' => $user->lang['ACP_WOW_GUILD_MEMBER_CHECK_EXPLAIN'],
					'S_ERROR' => (sizeof($error)) ? true : false,
					'ERROR_MSG' => implode('<br />', $error),
					'U_ACTION' => $this->u_action
				));
	}

	public function get_groups($value, $key)
	{
		global $db;
		
		$selectedValues = array_filter(explode(',', $value));
		
		if ($this->groups == null)
		{
			$this->groups = array();
			$sql = 'SELECT group_name, group_id FROM ' . GROUPS_TABLE .
					 ' WHERE group_type <> ' . GROUP_SPECIAL .
					 ' ORDER BY group_type DESC, group_name ASC, group_id ASC';
			$result = $db->sql_query($sql);
			while ($row = $db->sql_fetchrow($result))
			{
				$this->groups[$row['group_id']] = $row['group_name'];
			}
			$db->sql_freeresult($result);
		}
		
		$ret = '<select id="' . $key .
				 '" multiple="multiple" size="10" onchange="wowmembercheck_mutiple_changed(\'' .
				 $key . '\');">';
		foreach ($this->groups as $id => $name)
		{
			$ret .= '<option value="' . $id . '" ' .
					 (in_array($id, $selectedValues) ? 'selected="selected"' : '') .
					 '>' . $name . '</option>' . "\n";
		}
		$ret .= '</select><br/>';
		$ret .= '<input type="text" value="' . $value . '" name="config[' . $key .
				 ']" id="' . $key . '_text" readonly="readonly" />';
		
		return $ret;
	}
}
