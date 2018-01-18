<?php

namespace FH3095\WoWGuildMemberCheck\migrations;

class create_table extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array();
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'wowguildmembercheck_wowchartouser' => array(
					'COLUMNS'	=> array(
						'char_id'		=> array('UINT', null, 'auto_increment'),
						'user_id'		=> array('UINT', 0),
						'server'		=> array('VCHAR:32', ''),
						'name'			=> array('VCHAR:32', ''),
					),
					'PRIMARY_KEY' => 'char_id',
					'KEYS'        => array(
						'idx_user_id' => array('INDEX', 'user_id'),
						'idx_server_name' => array('UNIQUE', array('server', 'name')),
					)
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'wowguildmembercheck_wowchartouser',
			),
		);
	}
}
