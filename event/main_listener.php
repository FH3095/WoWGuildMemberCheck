<?php

namespace FH3095\WoWGuildMemberCheck\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class main_listener implements EventSubscriberInterface
{
	static public function getSubscribedEvents()
	{
		return array(
			//'core.display_forums_modify_template_vars'	=> 'display_forums_modify_template_vars',
			'core.user_setup'				=> 'load_language_on_setup',
			'core.page_header'				=> 'add_page_header_link',
			'core.viewonline_overwrite_location'	=> 'viewonline_page',
			'core.ucp_register_modify_template_data' => 'register_page',
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

	/**
	 * Add a link to the controller in the forum navbar
	 */
	public function add_page_header_link()
	{
		$this->template->assign_vars(array(
			'U_DEMO_PAGE0'	=> $this->helper->route('FH3095_WoWGuildMemberCheck_OAuthTarget', array('go' => '0')),
			'U_DEMO_PAGE1'	=> $this->helper->route('FH3095_WoWGuildMemberCheck_OAuthTarget', array('go' => '1')),
			'U_DEMO_PAGE2'	=> $this->helper->route('FH3095_WoWGuildMemberCheck_OAuthTarget', array('go' => '2')),
		));
	}

	/**
	 * Show users viewing Acme Demo on the Who Is Online page
	 *
	 * @param \phpbb\event\data	$event	Event object
	 */
	public function viewonline_page($event)
	{
		if ($event['on_page'][1] === 'app' && strrpos($event['row']['session_page'], 'app.' . $this->php_ext . '/demo') === 0)
		{
			$event['location'] = $this->user->lang('VIEWING_ACME_DEMO');
			$event['location_url'] = $this->helper->route('FH3095_WoWGuildMemberCheck_OAuthTarget', array('go' => '1'));
		}
	}

	/**
	 * A sample PHP event
	 * Modifies the names of the forums on index
	 *
	 * @param \phpbb\event\data	$event	Event object
	 */
	public function display_forums_modify_template_vars($event)
	{
		$forum_row = $event['forum_row'];
		$forum_row['FORUM_NAME'] .= ' :: Acme Event ::';
		$event['forum_row'] = $forum_row;
	}
	
	public function register_page($event) {
		$vars = $event['template_vars'];
		$vars['S_WOWMEMBERCHECK_BNET_AUTH_PATH'] = (String)($this->service->get_battle_net_service()->getAuthorizationUri());
		$event['template_vars'] = $vars;
	}
}
