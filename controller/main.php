<?php

/**
 *
 * WoW Guild Member Check. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2018, FH3095
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */
namespace FH3095\WoWGuildMemberCheck\controller;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * WoW Guild Member Check main controller.
 */
class main
{
	/* @var \phpbb\config\config */
	protected $config;
	
	/* @var \phpbb\controller\helper */
	protected $helper;
	
	/* @var \phpbb\template\template */
	protected $template;
	
	/* @var \phpbb\user */
	protected $user;
	protected $request;
	protected $service;

	public function __construct(\phpbb\config\config $config,
			\phpbb\controller\helper $helper, \phpbb\template\template $template,
			\phpbb\user $user, \phpbb\request\request $request,
			\FH3095\WoWGuildMemberCheck\service $service)
	{
		$this->config = $config;
		$this->helper = $helper;
		$this->template = $template;
		$this->user = $user;
		$this->request = $request;
		$this->service = $service;
	}

	public function handleOAuthTarget()
	{
		if ($this->request->is_set("error"))
		{
			$msg = '<span class="error">' .
					 $this->user->lang['WOW_GUILD_MEMBER_CHECK_BATTLENET_ACCESS_DENIED'];
			$msg = $msg . $this->request->variable("error", "") . ": ";
			$msg = $msg . $this->request->variable("errorDescription", "");
			$msg = $msg . '</span>';
			$this->template->assign_var('WOWMEMBERCHECK_OAUTH_RESULT', $msg);
			return $this->helper->render('oauth_result_body.html');
		}
		
		$result = $this->service->sync_current_user();
		$msg = "[" . $result['result'] . "] " .
				 $this->user->lang['WOW_GUILD_MEMBER_CHECK_BATTLENET_SUCCESS'] .
				 $result['characters'];
		$this->template->assign_var('WOWMEMBERCHECK_OAUTH_RESULT', $msg);
		return $this->helper->render('oauth_result_body.html');
	}
}
