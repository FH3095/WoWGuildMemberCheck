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

	public function __construct(\phpbb\config\config $config, \phpbb\controller\helper $helper, \phpbb\template\template $template, \phpbb\user $user, \phpbb\request\request $request,
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
		$this->service->check_guild_groups();
		return $this->helper->render('oauth_result_body.html');
		$session = $this->service->start_session();
		$battlenetService = $this->service->get_battle_net_service();


		if($this->service->get_user_characters_from_session() == null) {
			if($battlenetService->getStorage()->hasAccessToken($battlenetService->service()) || $this->request->is_set('code')) {
				$characters = $this->service->get_wow_characters($battlenetService, $this->request->variable('code', ''));
				$this->service->save_user_characters_to_session($characters);
			}
		}
		if($this->service->get_user_characters_from_session()) {
			$this->service->update_user_characters($this->service->get_user_characters_from_session());
			$charactersStr = "";
			foreach($this->service->get_user_characters_from_session() AS $character) {
				if(!empty($charactersStr)) {
					$charactersStr  .= ', ';
				}
				$charactersStr .= $character['name']  . '-' . $character['server'];
			}

			$resultMsg = $this->user->lang['WOW_GUILD_MEMBER_CHECK_BATTLENET_SUCCESS'] . $charactersStr . '.';
			$this->template->assign_var('WOWMEMBERCHECK_OAUTH_RESULT', $resultMsg);
		} else {
			$this->template->assign_var('WOWMEMBERCHECK_OAUTH_RESULT', '<span class="error">' . $this->user->lang['WOW_GUILD_MEMBER_CHECK_BATTLENET_ACCESS_DENIED'] . '</span>');
		}
		return $this->helper->render('oauth_result_body.html');
	}
}
