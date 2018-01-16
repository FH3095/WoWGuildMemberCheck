<?php

namespace FH3095\WoWGuildMemberCheck\migrations;

use phpbb\db\migration\migration;

class add_settings extends migration
{
	public function update_data()
	{
		return array(
			array('config.add', array('wowmembercheck_client_id', '')),
			array('config.add', array('wowmembercheck_client_secret', '')),
			array('config.add', array('wowmembercheck_guild_name', '')),
			array('config.add', array('wowmembercheck_guild_server', '')),
			array('config.add', array('wowmembercheck_guild_region', '')),
			array('config.add', array('wowmembercheck_group_add_inguild', '')),
			array('config.add', array('wowmembercheck_group_remove_inguild', '')),
			array('config.add', array('wowmembercheck_group_add_outofguild', '')),
			array('config.add', array('wowmembercheck_group_remove_outofguild', '')),
		);
	}
}
