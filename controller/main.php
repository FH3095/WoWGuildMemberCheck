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
use OAuth\OAuth2\Service\BattleNet;
use OAuth\Common\Storage\SymfonySession;
use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Http\Uri\Uri;

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
		$this->request->enable_super_globals();
		$session = $this->service->start_session();
		$this->request->disable_super_globals();

		echo $session->getId() . '<br>';
		foreach($_SESSION AS $key => $value) {
			echo $key . '=>';
			var_dump($value);
			echo '<br><br><br>';
		}
		$serviceFactory = new \OAuth\ServiceFactory();
		$serviceFactory->setHttpClient(new \OAuth\Common\Http\Client\CurlClient());
		$credentials = new Credentials(
			'rqdbvuhx6stz4egtf6vwe4jph8dv28rx',
			'xB9UtmNAqWjyCAxBsGAjtRuuBftVYdb9',
			'https://www.risen-insanity.eu/forum/app.php/wowguildmembercheck/oauthtarget'
		);

		$storage = new SymfonySession($session);
		$battlenetService = $serviceFactory->createService('battlenet', $credentials, $storage, array(BattleNet::SCOPE_WOW_PROFILE), new Uri(BattleNet::API_URI_EU));
		// https://eu.battle.net/oauth/check_token?token=32t7w37zu9wa4j7c8yvyf9bv
		if($this->request->is_set('go')) {
			$session->invalidate();
			$url = $battlenetService->getAuthorizationUri();
			header( "Location: $url" );
		} elseif($session->has('wowMsg')) {
			$l_message = !$this->config['acme_demo_goodbye'] ? 'DEMO_HELLO' : 'DEMO_GOODBYE';
			$this->template->assign_var('DEMO_MESSAGE', $this->user->lang($l_message, $session->get('wowMsg')));
		} elseif($this->request->is_set('code')) {
			// This was a callback request from Battle.net, get the token
			$token = $battlenetService->requestAccessToken($this->request->variable('code', ''));
			// See https://dev.battle.net/io-docs for OAuth request types.
			//
			// Without any scopes specified, we can get their BattleTag.
			$result = json_decode($battlenetService->request('/account/user'));
			$msg = $result->battletag . ': ';
			$result = json_decode($battlenetService->request('/wow/user/characters'));
			foreach($result->characters as $character) {
				if(0==strcasecmp($character->guildRealm,'Rexxar') && 0==strcasecmp($character->guild,'Risen Insanity')) {
					$msg .= $character->name . '-' . $character->realm . ', ';
				}
			}
			$session->set('wowMsg', $msg);
			$l_message = !$this->config['acme_demo_goodbye'] ? 'DEMO_HELLO' : 'DEMO_GOODBYE';
			$this->template->assign_var('DEMO_MESSAGE', $this->user->lang($l_message, $msg));
		}
		return $this->helper->render('demo_body.html');
	}
}
