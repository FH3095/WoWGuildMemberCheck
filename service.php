<?php

/**
 *
 * WoW Guild Member Check. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2018
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */
namespace FH3095\WoWGuildMemberCheck;

class service
{
	protected $user;
	protected $current_user_id;
	protected $ask_for_auth_groups;
	protected $ask_for_auth_help_link;
	protected $groups_removed_users;
	protected $profileFieldHelper;
	protected $restHelper;
	protected $trial_rank;
	protected $trial_groups;
	protected $config;
	protected $db;

	public function __construct(\phpbb\config\config $config, \phpbb\user $user,
			\phpbb\db\driver\driver_interface $db,
			\FH3095\WoWGuildMemberCheck\ProfileFieldHelper $profileFieldHelper,
			\FH3095\WoWGuildMemberCheck\RestHelper $restHelper, $root_path,
			$php_ext)
	{
		$this->config = $config;
		$this->user = $user;
		$this->current_user_id = (string) $this->user->data['user_id'];
		$this->ask_for_auth_groups = explode(',',
				$this->config['wowmembercheck_ask_for_auth_groups']);
		$this->ask_for_auth_help_link = $this->config['wowmembercheck_ask_for_auth_help_link'];
		$this->groups_in_guild = explode(',',
				$this->config['wowmembercheck_inguild_groups']);
		$this->groups_removed_users = explode(',',
				$this->config['wowmembercheck_removed_users_groups']);
		$this->trial_rank = (int) $this->config['wowmembercheck_trial_rank'];
		$this->trial_groups = explode(',',
				$this->config['wowmembercheck_trial_groups']);
		$this->profileFieldHelper = $profileFieldHelper;
		$this->restHelper = $restHelper;
		$this->db = $db;

		if (! function_exists('group_user_add'))
		{
			include ($root_path . '/includes/functions_user' . $php_ext);
		}
	}

	private function get_guild_groups_members()
	{
		$result = array();
		$inGuildGroups = explode(',',
				$this->config['wowmembercheck_inguild_groups']);
		$groupMemberships = \group_memberships($inGuildGroups);

		foreach ($groupMemberships as $user)
		{
			$result[] = (string) $user['user_id'];
		}
		return $result;
	}

	private function get_groups_of_user($user_id)
	{
		$result = array();
		$groups = \group_memberships(false, array(
			$user_id
		));

		foreach ($groups as $group)
		{
			$result[] = (string) $group['group_id'];
		}
		return $result;
	}

	public function sync_current_user()
	{
		$restMembers = $this->restHelper->get_ids();
		if (in_array($this->current_user_id, $restMembers))
		{
			$this->db->sql_transaction('begin');
			$this->change_user_groups($this->current_user_id,
					$this->groups_in_guild, $this->groups_removed_users);
			$chars = $this->update_characters_and_rank($this->current_user_id);
			$this->db->sql_transaction('commit');

			return $this->build_sync_result("Updated", $chars);
		}
		else
		{
			return $this->build_sync_result("NotInGuild", array());
		}
	}

	public function sync_all()
	{
		$currentMembers = $this->get_guild_groups_members();
		$restMembers = $this->restHelper->get_ids();
		$toAdd = array_diff($restMembers, $currentMembers);
		$toDel = array_diff($currentMembers, $restMembers);
		$charSync = array_diff($currentMembers, $toDel);

		$this->db->sql_transaction('begin');
		$chars = array();
		foreach ($toAdd as $id)
		{
			$this->change_user_groups($id, $this->groups_in_guild,
					$this->groups_removed_users);
		}
		$groupsToRemoveFrom = array_unique(
				array_merge($this->groups_in_guild, $this->trial_groups));
		foreach ($toDel as $id)
		{
			$this->change_user_groups($id, $this->groups_removed_users,
					$groupsToRemoveFrom);
		}
		foreach ($charSync as $id)
		{
			$chars[$id] = $this->update_characters_and_rank($id);
		}
		$this->db->sql_transaction('commit');

		$result = array();
		foreach ($toAdd as $id)
		{
			$result[$id] = $this->build_sync_result("Added", $chars[$id]);
		}
		foreach ($toDel as $id)
		{
			$result[$id] = $this->build_sync_result("Removed",
					$this->profileFieldHelper->get_user_characters_from_profile_field(
							$id));
		}
		return $result;
	}

	private function change_user_groups($user_id, $groupsToAdd, $groupsToRemove,
			$makeNewGroupsDefault = true)
	{
		$existingUserGroups = $this->get_groups_of_user($user_id);
		// Only add groups, when user is not already member of this group
		$groupsToAdd = array_diff($groupsToAdd, $existingUserGroups);
		// Only remove group if user is member of this group
		$groupsToRemove = array_intersect($groupsToRemove, $existingUserGroups);

		foreach ($groupsToRemove as $group)
		{
			\group_user_del((int) $group, array(
				(int) $user_id
			));
		}
		foreach ($groupsToAdd as $group)
		{
			\group_user_add((int) $group, array(
				(int) $user_id
			), false, false, $makeNewGroupsDefault);
		}
	}

	private function update_characters_and_rank($user_id)
	{
		$characters = $this->restHelper->get_characters($user_id);
		$isTrial = false;
		$charsStr = "";
		foreach ($characters as $char)
		{
			if (! empty($charsStr))
			{
				$charsStr = $charsStr . ", ";
			}
			$charsStr = $charsStr . $char['name'] . "-" . $char['server'];
			if ($char['rank'] == $this->trial_rank)
			{
				$isTrial = true;
			}
		}
		$this->profileFieldHelper->update_profile_field($user_id, $charsStr);

		if ($isTrial == true)
		{
			$this->change_user_groups($user_id, $this->trial_groups, array(),
					false);
		}
		else
		{
			$this->change_user_groups($user_id, array(), $this->trial_groups,
					false);
		}
		return $charsStr;
	}

	private function build_sync_result($result, $characters)
	{
		return array(
			"result" => $result,
			"characters" => $characters
		);
	}

	public function getUserGroupsToAskForAuth()
	{
		return $this->ask_for_auth_groups;
	}

	public function getAskForAuthHelpLink()
	{
		return $this->ask_for_auth_help_link;
	}
}
