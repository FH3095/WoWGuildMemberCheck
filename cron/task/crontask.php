<?php
/**
 *
 * WoW Guild Member Check. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2018, FH3095
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace FH3095\WoWGuildMemberCheck\cron\task;


class crontask extends \phpbb\cron\task\base
{
	protected $config;
	protected $service;
	protected $user;
	protected $log;
	protected $db;
	protected $table_name;

	public function __construct(\phpbb\config\config $config, \FH3095\WoWGuildMemberCheck\service $service, \phpbb\db\driver\driver_interface $db,
		\phpbb\user $user, \phpbb\log\log $log, $char_table) {
		$this->config = $config;
		$this->service = $service;
		$this->user = $user;
		$this->log = $log;
		$this->db = $db;
		$this->table_name = $char_table;
	}

	public function run() {
		try {
			$this->db->sql_transaction('begin');
			$numDeletedChars = $this->update_characters_list();
			$numAddedUsers = $this->service->check_usergroups_for_add();
			$numRemovedUsers = $this->service->check_usergroups_for_remove();
			$this->db->sql_transaction('commit');
			$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'WOW_GUILD_MEMBER_CHECK_CRON_RAN', false,
				array($numDeletedChars, $numAddedUsers, $numRemovedUsers));
		} catch (\Exception $e) {
			$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'WOW_GUILD_MEMBER_CHECK_CRON_ERROR', false, array($e->getMessage(), nl2br($e->getTraceAsString(), true)));
		}

		$this->config->set('wowmembercheck_cron_lastrun', time(), false);
	}

	private function get_characters_from_battlenet() {
		$client = new \GuzzleHttp\Client([
			'base_url' => $this->config['wowmembercheck_guild_region'],
			'allow_redirects' => true,
			'cookies' => false,
			'connect_timeout' => 10,
			'read_timeout' => 10,
			'timeout' => 10,

		]);
		$url = '/wow/guild/' . rawurlencode($this->config['wowmembercheck_guild_server']) . '/' . rawurlencode($this->config['wowmembercheck_guild_name']) .
			'?apikey=' . rawurlencode($this->config['wowmembercheck_client_id']) . '&fields=members&locale=en_GB';
		$response = $client->get($url, array());
		if($response->getStatusCode() != 200) {
			throw new \Exception('Cant query guild members: ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase() .  ': ' . $response->getBody());
		}
		$curChars = json_decode($response->getBody(), true);


		$ret = array();
		foreach($curChars['members'] AS $char) {
			$ret[] = array('server' => $char['character']['realm'], 'name' => $char['character']['name']);
		}
		return $ret;
	}

	private function get_db_characters() {
		$sql = $this->db->sql_build_query('SELECT', array(
			'SELECT'	=> 'char_id,user_id,server,name',
			'FROM'		=> array($this->table_name => 'c2u'),
		));
		$result = $this->db->sql_query($sql);
		$rowset = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		return $rowset;
	}

	private function update_characters_list() {
		$curChars = $this->get_characters_from_battlenet();
		$dbChars = $this->get_db_characters();

		$toDelete = array_udiff($dbChars, $curChars, $this->service->get_compare_func_for_char_arrays());
		if(empty($toDelete)) {
			return 0;
		}
		$charIds = array();
		foreach($toDelete as $char) {
			$charIds[] = (int)$char['char_id'];
		}
		$sql = 'DELETE FROM ' . $this->table_name . ' WHERE ' . $this->db->sql_in_set('char_id', $charIds);
		$this->db->sql_query($sql);

		return count($charIds);
	}

	public function is_runnable() {
		return true;
	}

	public function should_run() {
		return $this->config['wowmembercheck_cron_lastrun'] < (time() - ($this->config['wowmembercheck_cron_interval'] * 1));
	}
}
