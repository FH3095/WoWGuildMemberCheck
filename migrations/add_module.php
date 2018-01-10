<?php

namespace FH3095\WoWGuildMemberCheck\migrations;

use phpbb\db\migration\migration;

class add_module extends migration
{
	public function effectively_installed()
	{
		return isset($this->config['fh3095_wowguildmembercheck_acpinstalled']) && $this->config['fh3095_wowguildmembercheck_acpinstalled'] == 1;
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v31x\v314');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('fh3095_wowguildmembercheck_acpinstalled', 1)),
			array('module.add', array('acp', 'ACP_CAT_DOT_MODS', 'ACP_WOW_GUILD_MEMBER_CHECK')),
			array('module.add', array(
				'acp', 'ACP_WOW_GUILD_MEMBER_CHECK', array(
					'module_basename'	=> '\\FH3095\\WoWGuildMemberCheck\\acp\\acp_module',
					'modes'				=> array('settings'),
				),
			)),
		);
	}
}
