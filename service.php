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
	protected $user;

	protected $table_name;

	protected $request;

	protected $session;

	protected $config;

	protected $helper;


	public function __construct(\phpbb\config\config $config, \phpbb\controller\helper $helper, \phpbb\user $user, \phpbb\request\request $request, $table_name)
	{
		$this->config = $config;
		$this->helper = $helper;
		$this->user = $user;
		$this->table_name = $table_name;
		$this->request = $request;
		$this->session = null;
	}

	public function get_battle_net_service() {
		$session = $this->start_session();

		$serviceFactory = new \OAuth\ServiceFactory();
		$serviceFactory->setHttpClient(new \OAuth\Common\Http\Client\CurlClient());
		$credentials = new Credentials($this->config['wowmembercheck_client_id'], $this->config['wowmembercheck_client_secret'],
			$this->helper->route('FH3095_WoWGuildMemberCheck_OAuthTarget', array(), false, false, UrlGeneratorInterface::ABSOLUTE_URL)
		);

		$storage = new SymfonySession($session);
		return $serviceFactory->createService('battlenet', $credentials, $storage, array(BattleNet::SCOPE_WOW_PROFILE), new Uri($this->config['wowmembercheck_guild_region']));
	}

	public function update_user_characters($characters) {
		if($this->user == null || $this->user->data['user_id'] == ANONYMOUS || !($this->user->data['user_type'] == USER_NORMAL || $this->user->data['user_type'] == USER_FOUNDER)) {
			return false;
		}
		return true;
	}

	public function save_user_characters_to_session($characters) {
		$this->start_session()->set(self::get_character_session_key(), $characters);
	}

	public function get_user_characters_from_session() {
		return $this->start_session()->get(self::get_character_session_key());
	}

	public function get_wow_characters($bnetService, $code) {
		if(!$bnetService->getStorage()->hasAccessToken($bnetService->service())) {
			$bnetService->requestAccessToken($code);
		}
		$result = json_decode($bnetService->request('/wow/user/characters'));
		$characters = array();
		foreach($result->characters as $character) {
			if( 0==strcasecmp($character->guildRealm, $this->config['wowmembercheck_guild_server']) &&
				0==strcasecmp($character->guild, $this->config['wowmembercheck_guild_name'])) {
				$characters[] = array('server'=>$character->realm, 'name'=>$character->name);
			}
		}
		if(empty($characters)) {
			return null;
		}
		return $characters;
	}

	public function start_session() {
		if (!is_null($this->session) and $this->session->isStarted()) {
			return $this->session;
		}

		$this->request->enable_super_globals();
		$this->session = new Session();
		$this->session->start();
		$this->request->disable_super_globals();

		return $this->session;
	}

	protected static function get_character_session_key() {
		return "wowmembercheck_characters";
	}
}
