<?php

namespace FH3095\WoWGuildMemberCheck\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class main_listener implements EventSubscriberInterface
{

	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup' => 'load_language_on_setup',
			'core.ucp_profile_modify_profile_info' => 'profile_page'
		);
	}
	protected $template;
	protected $service;

	/**
	 * Constructor
	 *
	 * @param \phpbb\controller\helper $helper
	 *        	Controller helper object
	 * @param \phpbb\template\template $template
	 *        	Template object
	 * @param \phpbb\user $user
	 *        	User object
	 * @param string $php_ext
	 *        	phpEx
	 */
	public function __construct(\phpbb\template\template $template,
			\FH3095\WoWGuildMemberCheck\service $service)
	{
		$this->service = $service;
		$this->template = $template;
	}

	/**
	 * Load common language files during user setup
	 *
	 * @param \phpbb\event\data $event
	 *        	Event object
	 */
	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'FH3095/WoWGuildMemberCheck',
			'lang_set' => 'common'
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}

	public function profile_page($event)
	{
		$this->template->assign_var('S_WOWMEMBERCHECK_CHARACTERS_IN_GUILD',
				$this->service->get_current_user_characters_from_profile_field());
		$this->template->assign_var('S_WOWMEMBERCHECK_BNET_AUTH_PATH',
				(string) ($this->service->get_auth_url()));
	}
}
