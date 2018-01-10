<?php

namespace FH3095\WoWGuildMemberCheck\acp;

class acp_info {
	function module()
	{
		return array(
			'filename'	=> '\\FH3095\\WoWGuildMemberCheck\\acp\\acp_module',
			'title'		=> 'ACP_WOW_GUILD_MEMBER_CHECK',
			'modes'		=> array(
				'settings'	=> array(
					'title' => 'ACP_WOW_GUILD_MEMBER_CHECK_SETTINGS',
					'auth' => 'ext_FH3095/WoWGuildMemberCheck && acl_a_board',
					'cat' => array('ACP_WOW_GUILD_MEMBER_CHECK')
				),
			),
		);
	}
}
