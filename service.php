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

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use OAuth\OAuth2\Service\BattleNet;
use OAuth\Common\Storage\SymfonySession;
use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Http\Uri\Uri;

class service
{
	const PROFILE_FIELD_NAME = "wowgmc_chars";
	protected $user;
	protected $table_user_group;
	protected $table_profile_fields;
	protected $profilefields;
	protected $request;
	protected $session;
	protected $config;
	protected $helper;
	protected $db;
	protected $profile_field_active;

	public static function get_compare_func_for_char_arrays()
	{
		$compareFunc = function ($c1, $c2)
		{
			$ret = strcasecmp($c1['server'], $c2['server']);
			if ($ret != 0)
			{
				return $ret;
			}
			return strcasecmp($c1['name'], $c2['name']);
		};
		return $compareFunc;
	}

	public function __construct(\phpbb\config\config $config,
			\phpbb\controller\helper $helper, \phpbb\user $user,
			\phpbb\request\request $request,
			\phpbb\db\driver\driver_interface $db,
			\phpbb\profilefields\manager $profilefields, $root_path, $php_ext,
			$table_user_group, $table_profile_fields)
	{
		$this->profile_field_active = null;
		$this->config = $config;
		$this->helper = $helper;
		$this->profilefields = $profilefields;
		$this->user = $user;
		$this->table_user_group = $table_user_group;
		$this->table_profile_fields = $table_profile_fields;
		$this->request = $request;
		$this->db = $db;
		$this->session = null;
		
		if (! function_exists('group_user_add'))
		{
			include ($root_path . '/includes/functions_user' . $php_ext);
		}
	}

	public function get_auth_url()
	{
		$baseUrl = $this->config['wowmembercheck_webservice_url'];
		$guildId = (int) $this->config['wowmembercheck_webservice_guildId'];
		$userId = (int) $this->user->data['user_id'];
		$macKey = $this->config['wowmembercheck_webservice_macKey'];
		$systemName = $this->config['wowmembercheck_webservice_systemName'];
		$redirectTarget = $this->config['wowmembercheck_webservice_afterAuthRedirectTo'];
		
		if (substr($baseUrl, - 1) !== "/")
		{
			$baseUrl = $baseUrl . "/";
		}
		
		$macBinary = hash_hmac("sha256", $guildId . $systemName . $userId,
				base64_decode($macKey, TRUE), TRUE);
		$mac = base64_encode($macBinary);
		
		$authUrl = $baseUrl . "rest/auth/start?" . http_build_query(
				array(
					"guildId" => $guildId,
					"systemName" => $systemName,
					"remoteAccountId" => $userId,
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
				(int) $this->user->data['user_id']);
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

	public function check_guild_groups()
	{
		$this->db->sql_transaction('begin');
		
		$inGuildGroups = explode(',',
				$this->config['wowmembercheck_inguild_groups']);
		$groupMemberships = \group_memberships($inGuildGroups);
		$user_ids = array();
		foreach ($groupMemberships as $user)
		{
			$user_ids[$user['user_id']] = TRUE;
		}
		
		foreach ($user_ids as $user_id)
		{
			$this->check_characters_for_user((int) $user_id);
		}
		
		$this->db->sql_transaction('commit');
	}

	private function construct_rest_url($endpoint, $method,
			$queryParameters = null)
	{
		$url = $this->config['wowmembercheck_webservice_url'];
		$guildId = (int) $this->config['wowmembercheck_webservice_guildId'];
		$systemName = $this->config['wowmembercheck_webservice_systemName'];
		
		if (substr($url, - 1) !== "/")
		{
			$url = $url . "/";
		}
		
		$url = $url . "/rest/" . $guildId . "/" . $endpoint . "/" . $method . "?" . http_build_query(
				array(
					"systemName" => $systemName
				), null, "&", \PHP_QUERY_RFC3986);
		if (! empty($queryParameters))
		{
			$url = $url . "&" . http_build_query($queryParameters, null, "&",
					\PHP_QUERY_RFC3986);
		}
		return $url;
	}

	public function check_characters_for_user($user_id)
	{
	}

	public function update_user_characters($characters)
	{
		if ($this->user == null || ! ($this->user->data['user_type'] ==
				 USER_NORMAL || $this->user->data['user_type'] == USER_FOUNDER))
		{
			return false;
		}
		return $this->update_user_characters_for_user(
				(int) $this->user->data['user_id'], $characters);
	}

	public function update_user_characters_for_user($user_id, $characters)
	{
		$user_id = (int) $user_id;
		if ($user_id == ANONYMOUS)
		{
			return false;
		}
		
		$rowset = $this->get_wow_characters_from_db_for_user($user_id);
		if (null === $rowset)
		{
			return false;
		}
		
		$toDelete = array_udiff($rowset, $characters,
				self::get_compare_func_for_char_arrays());
		$toAdd = array_udiff($characters, $rowset,
				self::get_compare_func_for_char_arrays());
		
		$this->db->sql_transaction('begin');
		foreach ($toDelete as $char)
		{
			$sql = 'DELETE FROM ' . $this->table_name . ' WHERE ' . $this->db->sql_build_array(
					'DELETE',
					array(
						'user_id' => $user_id,
						'server' => $char['server'],
						'name' => $char['name']
					));
			$this->db->sql_query($sql);
		}
		foreach ($toAdd as $char)
		{
			$sql = 'INSERT INTO ' . $this->table_name . ' ' . $this->db->sql_build_array(
					'INSERT',
					array(
						'user_id' => $user_id,
						'server' => $char['server'],
						'name' => $char['name']
					));
			$this->db->sql_query($sql);
		}
		$this->check_usergroups_for_add();
		$this->check_usergroups_for_remove();
		$this->refresh_custom_field_for_users(array(
			$user_id
		));
		$this->db->sql_transaction('commit');
		return true;
	}

	public function save_user_characters_to_session($characters)
	{
		$this->start_session()->set(self::get_character_session_key(),
				$characters);
	}

	public function get_user_characters_from_session()
	{
		return $this->start_session()->get(self::get_character_session_key());
	}

	public function get_wow_characters($bnetService, $code)
	{
		if (! $bnetService->getStorage()->hasAccessToken(
				$bnetService->service()))
		{
			$bnetService->requestAccessToken($code);
		}
		$result = json_decode($bnetService->request('/wow/user/characters'));
		$characters = array();
		foreach ($result->characters as $character)
		{
			if (0 == strcasecmp($character->guildRealm,
					$this->config['wowmembercheck_guild_server']) && 0 == strcasecmp(
					$character->guild, $this->config['wowmembercheck_guild_name']))
			{
				$characters[] = array(
					'server' => $character->realm,
					'name' => $character->name
				);
			}
		}
		if (empty($characters))
		{
			return null;
		}
		return $characters;
	}

	public function get_wow_characters_from_db_for_user($user_id)
	{
		$user_id = (int) $user_id;
		if ($user_id == ANONYMOUS)
		{
			return null;
		}
		
		$sql = $this->db->sql_build_query('SELECT',
				array(
					'SELECT' => 'char_id,server,name',
					'FROM' => array(
						$this->table_name => 'c2u'
					),
					'WHERE' => 'user_id = ' . ((int) $user_id)
				));
		$result = $this->db->sql_query($sql);
		$rowset = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);
		
		return $rowset;
	}

	public function get_wow_characters_from_db()
	{
		if ($this->user == null || ! ($this->user->data['user_type'] ==
				 USER_NORMAL || $this->user->data['user_type'] == USER_FOUNDER))
		{
			return null;
		}
		return $this->get_wow_characters_from_db_for_user(
				(int) $this->user->data['user_id']);
	}

	public function start_session()
	{
		if (! is_null($this->session) and $this->session->isStarted())
		{
			return $this->session;
		}
		
		$this->request->enable_super_globals();
		$this->session = new Session();
		$this->session->start();
		$this->request->disable_super_globals();
		
		return $this->session;
	}

	public function delete_characters_for_user($user_ids)
	{
		$sql = 'DELETE FROM ' . $this->table_name . ' WHERE ' .
				 $this->db->sql_in_set('user_id', $user_ids);
		$this->db->sql_query($sql);
	}

	private function compare_usergroups_with_characters($groups,
			$searchUsersToAdd)
	{
		$columns = 'user_id';
		if (! $searchUsersToAdd)
		{
			$columns .= ',group_id';
		}
		$outerTable = $searchUsersToAdd ? $this->table_name : $this->table_user_group;
		$innerTable = $searchUsersToAdd ? $this->table_user_group : $this->table_name;
		$groupTableWhere = array(
			'group_id',
			'IN',
			$groups
		);
		$subSelectQuery = array(
			'SELECT' => 'user_id',
			'FROM' => array(
				$innerTable => 'table2'
			)
		);
		$outerWhere = array();
		
		if (! $searchUsersToAdd)
		{
			$outerWhere[] = $groupTableWhere;
		}
		else
		{
			$subSelectQuery['WHERE'] = array(
				'AND',
				array(
					$groupTableWhere
				)
			);
		}
		// HACK: phpBB currently cant build subqueries via sql_build_query, see
		// https://tracker.phpbb.com/browse/PHPBB3-15520
		// $outerWhere[] = array('user_id', 'NOT IN', '', 'SELECT',
		// $subSelectQuery);
		$subSql = $this->db->sql_build_query('SELECT', $subSelectQuery);
		$outerWhere[] = array(
			'user_id NOT IN (' . $subSql . ')'
		);
		// HACK End
		
		$sql = $this->db->sql_build_query('SELECT_DISTINCT',
				array(
					'SELECT' => $columns,
					'FROM' => array(
						$outerTable => 'table1'
					),
					'WHERE' => array(
						'AND',
						$outerWhere
					),
					'ORDER_BY' => $columns
				));
		$result = $this->db->sql_query($sql);
		$rowset = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);
		return $rowset;
	}

	private function change_user_groups($user_ids, $groupsToAdd, $groupsToRemove)
	{
		$this->db->sql_transaction('begin');
		
		foreach ($groupsToRemove as $group)
		{
			\group_user_del((int) $group, $user_ids);
		}
		foreach ($groupsToAdd as $group)
		{
			\group_user_add((int) $group, $user_ids, false, false, true);
		}
		
		$this->db->sql_transaction('commit');
	}

	public function check_usergroups_for_remove()
	{
		$inGuildGroups = explode(',',
				$this->config['wowmembercheck_inguild_groups']);
		$removedUsersGroups = explode(',',
				$this->config['wowmembercheck_removed_users_groups']);
		
		$toRemoveUsers = $this->compare_usergroups_with_characters(
				$inGuildGroups, false);
		$user_ids = array();
		foreach ($toRemoveUsers as $user)
		{
			$user_ids[] = (int) $user['user_id'];
		}
		
		$this->change_user_groups($user_ids, $removedUsersGroups, $inGuildGroups);
		
		return count($user_ids);
	}

	public function check_usergroups_for_add()
	{
		$inGuildGroups = explode(',',
				$this->config['wowmembercheck_inguild_groups']);
		$removedUsersGroups = explode(',',
				$this->config['wowmembercheck_removed_users_groups']);
		
		$toAddUsers = $this->compare_usergroups_with_characters($inGuildGroups,
				true);
		$user_ids = array();
		foreach ($toAddUsers as $user)
		{
			$user_ids[] = (int) $user['user_id'];
		}
		
		$this->change_user_groups($user_ids, $inGuildGroups, $removedUsersGroups);
		
		return count($user_ids);
	}

	public function refresh_custom_field_for_users($user_ids)
	{
		if (! $this->is_profile_field_active())
		{
			return;
		}
		
		$this->db->sql_transaction('begin');
		foreach ($user_ids as $user_id)
		{
			if ($user_id == ANONYMOUS || $user_id == 0)
			{
				continue;
			}
			$charStr = "";
			$sql = $this->db->sql_build_query('SELECT',
					array(
						'SELECT' => 'name,server',
						'FROM' => array(
							$this->table_name => 'c2u'
						),
						'WHERE' => array(
							'user_id',
							'=',
							$user_id
						)
					));
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				if (! empty($charStr))
				{
					$charStr .= ', ';
				}
				$charStr .= $row['name'] . '-' . $row['server'];
			}
			$this->db->sql_freeresult($result);
			
			$this->profilefields->update_profile_field_data(intval($user_id),
					array(
						'pf_' . self::PROFILE_FIELD_NAME => $charStr
					));
		}
		$this->db->sql_transaction('commit');
	}

	protected static function get_character_session_key()
	{
		return "wowmembercheck_characters";
	}
}
