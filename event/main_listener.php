<?php

namespace FH3095\WoWGuildMemberCheck\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class main_listener implements EventSubscriberInterface
{
	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup'				=> 'load_language_on_setup',
			'core.ucp_register_modify_template_data'	=> 'register_page',
			'core.ucp_profile_modify_profile_info'		=> 'profile_page',
			'core.user_add_after'						=> 'add_session_chars_to_db',
			'core.delete_user_after'					=> 'delete_characters_for_user',
		);
	}

	/* @var \phpbb\controller\helper */
	protected $helper;

	/* @var \phpbb\template\template */
	protected $template;

	/* @var \phpbb\user */
	protected $user;

	/** @var string phpEx */
	protected $php_ext;

	protected $service;

	/**
	 * Constructor
	 *
	 * @param \phpbb\controller\helper	$helper		Controller helper object
	 * @param \phpbb\template\template	$template	Template object
	 * @param \phpbb\user               $user       User object
	 * @param string                    $php_ext    phpEx
	 */
	public function __construct(\phpbb\controller\helper $helper, \phpbb\template\template $template, \phpbb\user $user, $php_ext, \FH3095\WoWGuildMemberCheck\service $service, $charTable)
	{
		$this->service = $service;
		$this->helper   = $helper;
		$this->template = $template;
		$this->user     = $user;
		$this->php_ext  = $php_ext;
	}

	/**
	 * Load common language files during user setup
	 *
	 * @param \phpbb\event\data	$event	Event object
	 */
	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'FH3095/WoWGuildMemberCheck',
			'lang_set' => 'common',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}

	public function register_page($event) {
		$vars = $event['template_vars'];
		$vars['S_WOWMEMBERCHECK_BNET_AUTH_PATH'] = (String)($this->service->get_battle_net_service()->getAuthorizationUri());
		$event['template_vars'] = $vars;
	}

	public function profile_page($event) {
		$chars = $this->service->get_wow_characters_from_db();
		$charsTxt = "";
		if ($chars !== null) {
			foreach($chars AS $char) {
				if(!empty($charsTxt)) {
					$charsTxt .= ', ';
				}
				$charsTxt .= $char['name'] . '-' . $char['server'];
			}
		}
		$this->template->assign_var('S_WOWMEMBERCHECK_CHARACTERS_IN_GUILD', $charsTxt);
		$this->template->assign_var('S_WOWMEMBERCHECK_BNET_AUTH_PATH', (String)($this->service->get_battle_net_service()->getAuthorizationUri()));
	}

	public function add_session_chars_to_db($event) {
		$user_id = $event['user_id'];
		$this->service->start_session();
		$chars = $this->service->get_wow_characters_from_db_for_user($user_id);
		if(!empty($chars)) {
			return;
		}

		$chars = $this->service->get_user_characters_from_session();
		if(empty($chars)) {
			return;
		}

		$this->service->update_user_characters_for_user($user_id, $chars);
	}

	public function delete_characters_for_user($event) {
		$user_ids = $event['user_ids'];
		$this->service->delete_characters_for_user($user_ids);
	}
}
