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

	public function __construct(\phpbb\config\config $config,
			\FH3095\WoWGuildMemberCheck\service $service,
			\phpbb\db\driver\driver_interface $db, \phpbb\user $user,
			\phpbb\log\log $log)
	{
		$this->config = $config;
		$this->service = $service;
		$this->user = $user;
		$this->log = $log;
		$this->db = $db;
	}

	public function run()
	{
		try
		{
			$isFullSyncNeeded = $this->config['wowmembercheck_cron_lastfullcheck'] < (time() - $this->config['wowmembercheck_cron_full_check_interval'] *
					 60);
			$this->db->sql_transaction('begin');
			$results = $this->service->do_sync($isFullSyncNeeded);
			$this->db->sql_transaction('commit');
			
			$numbers = array();
			$texts = array();
			foreach ($results as $userId => $result)
			{
				$resultStr = $result['result'];
				if (! isset($numbers[$resultStr]))
				{
					$numbers[$resultStr] = 0;
					$texts[$resultStr] = "";
				}
				
				$numbers[$resultStr] = $numbers[$resultStr] + 1;
				$curText = $texts[$resultStr];
				if (! empty($curText))
				{
					$curText = ", ";
				}
				$curText = $curText . $userId;
				if (! empty($result['characters']))
				{
					$curText = $curText . "(" . $result['characters'] . ")";
				}
				$texts[$resultStr] = $curText;
			}
			
			ksort($numbers);
			$logText = "";
			foreach ($numbers as $resultType => $resultTypeNumber)
			{
				if (! empty($logText))
				{
					$logText = $logText . " ; ";
				}
				$logText = $logText . $resultType . ": " . $resultTypeNumber .
						 " [" . $texts[$resultType] . "]";
			}
			$this->log->add('admin', $this->user->data['user_id'],
					$this->user->ip, 'WOW_GUILD_MEMBER_CHECK_CRON_RAN', false,
					array(
						$isFullSyncNeeded ? "true" : "false",
						$logText
					));
			if ($isFullSyncNeeded)
			{
				$this->config->set('wowmembercheck_cron_lastfullcheck', time());
			}
		}
		catch (\Exception $e)
		{
			$this->log->add('admin', $this->user->data['user_id'],
					$this->user->ip, 'WOW_GUILD_MEMBER_CHECK_CRON_ERROR', false,
					array(
						$e->getMessage(),
						nl2br($e->getTraceAsString(), true)
					));
		}
		
		$this->config->set('wowmembercheck_cron_lastrun', time());
	}

	public function is_runnable()
	{
		return true;
	}

	public function should_run()
	{
		return $this->config['wowmembercheck_cron_lastrun'] <
				 (time() - ($this->config['wowmembercheck_cron_interval'] * 60));
	}
}
