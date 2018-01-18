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
			array('config.add', array('wowmembercheck_inguild_groups', '')),
			array('config.add', array('wowmembercheck_removed_users_groups', '')),
			array('config.add', array('wowmembercheck_cron_interval', 1440)),
			array('config.add', array('wowmembercheck_cron_lastrun', 0)),
		);
	}
}
