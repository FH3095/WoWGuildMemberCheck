<?php

namespace FH3095\WoWGuildMemberCheck\migrations;

use phpbb\db\migration\migration;

class add_settings extends migration
{
	public function update_data()
	{
		return array(
			array('config.add', array('wowmembercheck_webservice_url', '')),
			array('config.add', array('wowmembercheck_webservice_guildId', '')),
			array('config.add', array('wowmembercheck_webservice_apiKey', '')),
			array('config.add', array('wowmembercheck_webservice_macKey', '')),
			array('config.add', array('wowmembercheck_webservice_systemName', 'Forum')),
			array('config.add', array('wowmembercheck_webservice_afterAuthRedirectTo', '')),
			array('config.add', array('wowmembercheck_inguild_groups', '')),
			array('config.add', array('wowmembercheck_removed_users_groups', '')),
			array('config.add', array('wowmembercheck_cron_interval', 60)),
			array('config.add', array('wowmembercheck_cron_lastrun', 0)),
		);
	}
}
