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
	protected $user;
	protected $log;

	public function __construct(\phpbb\config\config $config, \FH3095\WoWGuildMemberCheck\service $service, \phpbb\user $user, \phpbb\log\log $log, $charTable) {
		$this->config = $config;
		$this->user = $user;
		$this->log = $log;
	}

	public function run() {
		try {
			$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'WOW_GUILD_MEMBER_CHECK_CRON_RAN', false, array(1,2,3,4));
			throw new \Exception("aaa");
		} catch (\Exception $e) {
			$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'WOW_GUILD_MEMBER_CHECK_CRON_ERROR', false, array($e->getMessage(), nl2br($e->getTraceAsString(), true)));
		}
		// Run your cron actions here...

		$this->config->set('wowmembercheck_cron_lastrun', time(), false);
	}

	public function is_runnable() {
		return true;
	}

	public function should_run() {
		return $this->config['wowmembercheck_cron_lastrun'] < (time() - ($this->config['wowmembercheck_cron_interval'] * 60));
	}
}
