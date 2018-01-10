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

	/**
	 * Constructor
	 *
	 * @param \phpbb\config\config		$config
	 * @param \phpbb\controller\helper	$helper
	 * @param \phpbb\template\template	$template
	 * @param \phpbb\user				$user
	 */
	public function __construct(\phpbb\config\config $config, \phpbb\controller\helper $helper, \phpbb\template\template $template, \phpbb\user $user, \phpbb\request\request $request,
			\FH3095\WoWGuildMemberCheck\service $service, $charTable)
	{
		$this->config = $config;
		$this->helper = $helper;
		$this->template = $template;
		$this->user = $user;
		$this->request = $request;
		$this->service = $service;
	}

	/**
	 * Demo controller for route /demo/{name}
	 *
	 * @param string $name
	 *
	 * @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
	 */
	public function handleOAuthTarget()
	{
		$session = $this->service->start_session();
		$battlenetService = $this->service->get_battle_net_service();
		// https://eu.battle.net/oauth/check_token?token=32t7w37zu9wa4j7c8yvyf9bv
		/*define('USER_NORMAL', 0);
		define('USER_INACTIVE', 1);
		define('USER_IGNORE', 2);
		define('USER_FOUNDER', 3);*/

		if($this->request->is_set('go')) {
			if($this->request->variable('go', 0) == 1) {
				$session->invalidate();
				$url = $battlenetService->getAuthorizationUri();
				header( 'Location: ' . $url );
				exit;
			} elseif($this->request->variable('go', 0) == 2) {
				$session->remove('wowmembercheck_characters');
				header('Location: ' . $this->helper->route('FH3095_WoWGuildMemberCheck_OAuthTarget', array('go' => '0')));
				exit;
			}
		}
		if($session->has('wowmembercheck_characters')) {
			$this->template->assign_var('DEMO_TITLE', 'Title');
			$this->template->assign_var('DEMO_MESSAGE', 'Hello '. print_r($this->service->get_user_characters_from_session(), true));
		} else {
			$totalNew = false;
			if(!$battlenetService->getStorage()->hasAccessToken($battlenetService->service())) {
				$battlenetService->requestAccessToken($this->request->variable('code', ''));
				$totalNew = true;
			}
			$characters = $this->service->get_wow_characters($battlenetService, $this->request->variable('code', ''));
			$this->service->save_user_characters_to_session($characters);
			$this->template->assign_var('DEMO_TITLE', 'Title');
			$this->template->assign_var('DEMO_MESSAGE', 'Hello ' . ($totalNew ? 'total ' : '') . 'new '. print_r($this->service->get_user_characters_from_session(), true));
		}
		return $this->helper->render('oauth_result_body.html');
	}
}
