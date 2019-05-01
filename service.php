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
	const PROFILE_FIELD_NAME = "wowgmc_chars";
	protected $user;
	protected $current_user_id;
	protected $ask_for_auth_groups;
	protected $ask_for_auth_help_link;
	protected $groups_in_guild;
	protected $groups_removed_users;
	protected $table_profile_fields;
	protected $profilefields;
	protected $profile_field_active;
	protected $trial_rank;
	protected $trial_groups;
	protected $config;
	protected $db;

	public function __construct(\phpbb\config\config $config, \phpbb\user $user,
			\phpbb\db\driver\driver_interface $db,
			\phpbb\profilefields\manager $profilefields, $root_path, $php_ext,
			$table_profile_fields)
	{
		$this->profile_field_active = null;
		$this->config = $config;
		$this->profilefields = $profilefields;
		$this->user = $user;
		$this->current_user_id = (int) $this->user->data['user_id'];
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
		$this->table_profile_fields = $table_profile_fields;
		$this->db = $db;

		if (! function_exists('group_user_add'))
		{
			include ($root_path . '/includes/functions_user' . $php_ext);
		}
	}

	public function get_auth_url()
	{
		$baseUrl = $this->config['wowmembercheck_webservice_url'];
		$guildId = (int) $this->config['wowmembercheck_webservice_guildId'];
		$macKey = $this->config['wowmembercheck_webservice_macKey'];
		$systemName = $this->config['wowmembercheck_webservice_systemName'];
		$redirectTarget = $this->config['wowmembercheck_webservice_afterAuthRedirectTo'];

		if (substr($baseUrl, - 1) !== "/")
		{
			$baseUrl = $baseUrl . "/";
		}

		$macBinary = hash_hmac("sha256",
				$guildId . $systemName . $this->current_user_id,
				base64_decode($macKey, TRUE), TRUE);
		$mac = base64_encode($macBinary);

		$authUrl = $baseUrl . "rest/auth/start?" .
				http_build_query(
						array(
							"guildId" => $guildId,
							"systemName" => $systemName,
							"remoteAccountId" => $this->current_user_id,
							"redirectTo" => $redirectTarget,
							"mac" => $mac
						), null, "&", \PHP_QUERY_RFC3986);
		return $authUrl;
	}

	private function is_profile_field_active()
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

	private function get_guild_groups_members(array & $userIds)
	{
		$inGuildGroups = explode(',',
				$this->config['wowmembercheck_inguild_groups']);
		$groupMemberships = \group_memberships($inGuildGroups);

		foreach ($groupMemberships as $user)
		{
			$userIds[$user['user_id']] = TRUE;
		}
	}

	private function get_to_update_user_ids_from_webservice(array & $userIds,
			\GuzzleHttp\Client & $client)
	{
		$url = $this->construct_rest_url("changes", "get");
		$response = $client->get($url, array());
		if ($response->getStatusCode() < 200 || $response->getStatusCode() > 299)
		{
			throw new \Exception(
					'Cant query changes: ' . $response->getStatusCode() . ' ' .
					$response->getReasonPhrase() . ': ' . $response->getBody());
		}
		$changes = json_decode($response->getBody(), true);

		$lastId = - 1;
		foreach ($changes as $change)
		{
			if ($lastId < $change['id'])
			{
				$lastId = $change['id'];
			}
			$userIds[$change['remoteAccountId']] = TRUE;
		}
		return $lastId;
	}

	private function reset_remote_commands(\GuzzleHttp\Client & $client, $lastId)
	{
		$url = $this->construct_rest_url("changes", "reset",
				array(
					"lastId" => $lastId
				));
		$client->get($url, array());
	}

	public function do_sync(bool $checkGroupMembers)
	{
		$client = $this->construct_client();
		$toUpdateUsers = array();
		if ($checkGroupMembers)
		{
			$this->get_guild_groups_members($toUpdateUsers);
		}
		$toResetRemoteCommandId = $this->get_to_update_user_ids_from_webservice(
				$toUpdateUsers, $client);


		$guildGroupMembers = array();
		$this->get_guild_groups_members($guildGroupMembers);

		$this->db->sql_transaction('begin');
		$result = array();
		foreach ($toUpdateUsers as $userId => $_)
		{
			$result[$userId] = $this->sync_user($client, $guildGroupMembers,
					$userId);
		}
		$this->db->sql_transaction('commit');

		$this->reset_remote_commands($client, $toResetRemoteCommandId);
		return $result;
	}

	public function sync_current_user()
	{
		$client = $this->construct_client();
		$guildGroupMembers = array();
		$this->get_guild_groups_members($guildGroupMembers);
		return $this->sync_user($client, $guildGroupMembers,
				$this->current_user_id);
	}

	private function construct_client()
	{
		$client = new \GuzzleHttp\Client(
				[
					'defaults' => [
						'allow_redirects' => true,
						'cookies' => false,
						'verify' => false,
						'connect_timeout' => 10,
						'timeout' => 10
					]
				]);
		return $client;
	}

	private function construct_rest_url($endpoint, $method,
			$queryParameters = null)
	{
		$url = $this->config['wowmembercheck_webservice_url'];
		$guildId = (int) $this->config['wowmembercheck_webservice_guildId'];
		$apiKey = $this->config['wowmembercheck_webservice_apiKey'];
		$systemName = $this->config['wowmembercheck_webservice_systemName'];

		if (substr($url, - 1) !== "/")
		{
			$url = $url . "/";
		}

		$url = $url . "rest/" . $guildId . "/" . $endpoint . "/" . $method . "?" .
				http_build_query(
						array(
							"systemName" => $systemName,
							"apiKey" => $apiKey
						), null, "&", \PHP_QUERY_RFC3986);
		if (! empty($queryParameters))
		{
			$url = $url . "&" .
					http_build_query($queryParameters, null, "&",
							\PHP_QUERY_RFC3986);
		}
		return $url;
	}

	private function change_user_groups($userId, $groupsToAdd, $groupsToRemove)
	{
		$groupMembershipsData = \group_memberships(false, $userId);
		$groupMemberships = array();
		foreach ($groupMembershipsData as $groupMembership)
		{
			$groupMemberships[] = (int) $groupMembership['group_id'];
		}

		// Only add groups, when user is not already member of this group
		$groupsToAdd = array_diff($groupsToAdd, $groupMemberships);
		// Only remove group if user is member of this group
		$groupsToRemove = array_intersect($groupsToRemove, $groupMemberships);

		foreach ($groupsToRemove as $group)
		{
			\group_user_del((int) $group, array(
				$userId
			));
		}
		foreach ($groupsToAdd as $group)
		{
			\group_user_add((int) $group, array(
				$userId
			), false, false, true);
		}
	}

	private function user_add($userId, $characters)
	{
		$this->change_user_groups($userId, $this->groups_in_guild, array());
		return $this->update_characters($userId, $characters);
	}

	private function user_remove($userId)
	{
		$this->change_user_groups($userId, $this->groups_removed_users,
				$this->groups_in_guild);
		return $this->update_characters($userId, array());
	}

	private function update_characters($userId, $characters)
	{
		$charsStr = "";
		foreach ($characters as $char)
		{
			if (! empty($charsStr))
			{
				$charsStr = $charsStr . ", ";
			}
			$charsStr = $charsStr . $char['name'] . "-" . $char['server'];
		}

		if ($this->is_profile_field_active())
		{
			if (empty($charsStr))
			{}
			else
			{
				$this->profilefields->update_profile_field_data($userId,
						array(
							"pf_" . self::PROFILE_FIELD_NAME => $charsStr
						));
			}
		}
		return $charsStr;
	}

	private function sync_user(\GuzzleHttp\Client & $client,
			array $guildGroupMembers, $userId)
	{
		$result = array(
			'result' => '',
			'characters' => ''
		);

		$url = $this->construct_rest_url("chars", "get",
				array(
					"remoteAccountId" => $userId
				));
		$response = $client->get($url, array());
		if ($response->getStatusCode() < 200 || $response->getStatusCode() > 299)
		{
			throw new \Exception(
					"Cant fetch characters for " . $userId . ": " .
					$response->getStatusCode() . " " .
					$response->getReasonPhrase() . ": " . $response->getBody());
		}
		$characters = json_decode($response->getBody(), true);

		$this->db->sql_transaction('begin');
		if (empty($characters) && ! empty($guildGroupMembers[$userId]))
		{
			$result['result'] = "Removed";
			$this->user_remove($userId);
		}
		elseif (! empty($characters) && empty($guildGroupMembers[$userId]))
		{
			$result['result'] = "Added";
			$result['characters'] = $this->user_add($userId, $characters);
		}
		elseif (! empty($characters))
		{
			$result['result'] = "Updated";
			$result['characters'] = $this->update_characters($userId,
					$characters);
		}
		$this->db->sql_transaction('commit');

		return $result;
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
