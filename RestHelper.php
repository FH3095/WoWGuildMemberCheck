<?php

namespace FH3095\WoWGuildMemberCheck;

class RestHelper
{
	protected $config;
	protected $baseUrl;
	protected $client;
	protected $current_user_id;

	public function __construct(\phpbb\config\config $config, \phpbb\user $user)
	{
		$this->config = $config;
		$this->current_user_id = (string) $this->user->data['user_id'];
		$this->baseUrl = $this->config['wowmembercheck_webservice_url'];
		if (substr($this->baseUrl, - 1) !== "/")
		{
			$this->baseUrl = $this->baseUrl . "/";
		}
		$this->client = null;
	}

	private function get_client()
	{
		if ($this->client !== null)
		{
			return $this->client;
		}

		$this->client = new \GuzzleHttp\Client(
				[
					'defaults' => [
						'allow_redirects' => true,
						'cookies' => false,
						'verify' => false,
						'connect_timeout' => 10,
						'timeout' => 10
					]
				]);
		return $this->client;
	}

	private function calcMac($str)
	{
		$macKey = $this->config['wowmembercheck_webservice_macKey'];
		$macBinary = hash_hmac("sha256", $str, base64_decode($macKey, TRUE),
				TRUE);
		$mac = base64_encode($macBinary);
		return $mac;
	}

	public function get_auth_url()
	{
		$systemName = $this->config['wowmembercheck_webservice_systemName'];
		$redirectTarget = $this->config['wowmembercheck_webservice_afterAuthRedirectTo'];

		$mac = $this->calcMac(
				$systemName . $this->current_user_id . $redirectTarget);

		$authUrl = $this->baseUrl . "auth/start?" .
				http_build_query(
						array(
							"systemName" => $systemName,
							"remoteId" => $this->current_user_id,
							"redirectTo" => $redirectTarget,
							"mac" => $mac
						), null, "&", \PHP_QUERY_RFC3986);
		return $authUrl;
	}

	public function get_ids()
	{
		$systemName = $this->config['wowmembercheck_webservice_systemName'];
		$mac = $this->calcMac($systemName);
		$url = $this->baseUrl . "accounts/remoteIdsByRemoteSystem?" .
				http_build_query(
						array(
							"systemName" => $systemName,
							"mac" => $mac
						), null, "&", \PHP_QUERY_RFC3986);

		$response = $this->get_client()->get($url, array());
		if ($response->getStatusCode() < 200 || $response->getStatusCode() > 299)
		{
			throw new \Exception(
					"Cant fetch characters for: " . $response->getStatusCode() .
					" " . $response->getReasonPhrase() . ": " .
					$response->getBody());
		}
		$ids = json_decode($response->getBody(), true, null,
				\JSON_THROW_ON_ERROR | \JSON_BIGINT_AS_STRING);

		$result = array();
		foreach ($ids as $id)
		{
			$result[] = (string) $id;
		}

		return $result;
	}

	public function get_characters($user_id)
	{
		$systemName = $this->config['wowmembercheck_webservice_systemName'];
		$mac = $this->calcMac($systemName . $user_id);
		$url = $this->baseUrl . "chars/get?" .
				http_build_query(
						array(
							"systemName" => $systemName,
							"remoteId" => $user_id,
							"mac" => $mac
						), null, "&", \PHP_QUERY_RFC3986);

		$response = $this->get_client()->get($url, array());
		if ($response->getStatusCode() < 200 || $response->getStatusCode() > 299)
		{
			throw new \Exception(
					"Cant fetch characters for: " . $response->getStatusCode() .
					" " . $response->getReasonPhrase() . ": " .
					$response->getBody());
		}
		$chars = json_decode($response->getBody(), true, null,
				\JSON_THROW_ON_ERROR | \JSON_BIGINT_AS_STRING);

		return $chars;
	}
}
