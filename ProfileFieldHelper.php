<?php

namespace FH3095\WoWGuildMemberCheck;

class ProfileFieldHelper
{
	const PROFILE_FIELD_NAME = "wowgmc_chars";
	protected $current_user_id;
	protected $table_profile_fields;
	protected $profilefields;
	protected $profile_field_active;
	protected $db;

	public function __construct(\phpbb\user $user,
			\phpbb\db\driver\driver_interface $db,
			\phpbb\profilefields\manager $profilefields, $table_profile_fields)
	{
		$this->profile_field_active = null;
		$this->profilefields = $profilefields;
		$this->current_user_id = (int) $user->data['user_id'];
		$this->table_profile_fields = $table_profile_fields;
		$this->db = $db;
	}

	public function is_profile_field_active()
	{
		if ($this->profile_field_active !== null)
		{
			return $this->profile_field_active;
		}

		$sql = $this->db->sql_build_query('SELECT',
				array(
					'SELECT' => 'field_id',
					'FROM' => array(
						$this->table_profile_fields => 'pf'
					),
					'WHERE' => array(
						'AND',
						array(
							array(
								'field_name',
								'IN',
								self::PROFILE_FIELD_NAME
							),
							array(
								'field_active',
								'IN',
								1
							)
						)
					)
				));
		$result = $this->db->sql_query($sql);
		$this->profile_field_active = ($this->db->sql_fetchrow($result) != false);
		$this->db->sql_freeresult($result);

		return $this->profile_field_active;
	}

	public function get_current_user_characters_from_profile_field()
	{
		return $this->get_user_characters_from_profile_field(
				$this->current_user_id);
	}

	public function get_user_characters_from_profile_field($user_id)
	{
		if (! $this->is_profile_field_active())
		{
			return "";
		}

		$fields = $this->profilefields->grab_profile_fields_data($user_id);
		$fields = array_shift($fields);

		if (! isset($fields[self::PROFILE_FIELD_NAME]) ||
				$fields[self::PROFILE_FIELD_NAME]["value"] == null)
		{
			return "";
		}

		return $fields[self::PROFILE_FIELD_NAME]["value"];
	}

	public function update_profile_field($user_id, $str)
	{
		if ($this->is_profile_field_active())
		{
			if (! empty($str))
			{
				$this->profilefields->update_profile_field_data($user_id,
						array(
							"pf_" . self::PROFILE_FIELD_NAME => $str
						));
			}
		}
	}
}
