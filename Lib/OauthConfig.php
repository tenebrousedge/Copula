<?php

App::uses('ConnectionManager', 'Model');

class OauthConfig {

	public static function getConfiguredApis() {
		$objects = ConnectionManager::enumConnectionObjects();
		$apis = array();
		foreach ($objects as $name) {
			if ($this->isOauthApi($name)) {
				$apis[] = $name;
			}
		}
		return array_keys($apis);
	}

	/**
	 * 
	 * @param string $dbConfig
	 * @return array array containing 'key' and 'secret' for oauth actions
	 */
	public static function getCredentials($dbConfig) {
		$config = ConnectionManager::getDataSource($dbConfig)->config;
		$credentials = array('key' => $config['login'], 'secret' => $config['password']);
		return $credentials;
	}

	/**
	 * 
	 * @param string $dbConfig
	 * @return array
	 */	
	public static function getAccessToken($dbConfig) {
		$config = ConnectionManager::getDataSource($dbConfig)->config;
		$token = array();
		if (!empty($config['access_token'])) {
			if ($config['authMethod'] == 'OAuth') {
				$token['oauth_token'] = $config['access_token'];
				$token['oauth_token_secret'] = (!empty($config['token_secret'])) ? $config['token_secret'] : null;
			} elseif ($config['authMethod'] == 'OAuthV2') {
				$token['access_token'] = $config['access_token'];
				$token['refresh_token'] = (!empty($config['refresh_token'])) ? $config['refresh_token'] : null;
			}
		}
		return array_filter($token);
	}

	/**
	 * 
	 * @param string $dbConfig
	 * @return string|boolean
	 */
	public static function isOauthApi($dbConfig) {
		$config = ConnectionManager::getDataSource($dbConfig)->config;
		if (in_array($config['authMethod'], array('OAuth', 'OAuthV2'))) {
			return $config['authMethod'];
		} else {
			return false;
		}
	}

	/**
	 * 
	 * @param string $dbConfig
	 * @param string $token
	 * @param string $tokenSecret
	 * @return boolean
	 */
	public static function setAccessToken($dbConfig, $token, $tokenSecret = null) {
		$method = self::isOauthApi($dbConfig);
		if ($method) {
			$config = array();
			$config['access_token'] = $token;
			if ($method == 'OAuth') {
				$config['token_secret'] = $tokenSecret;
			} elseif ($method == 'OAuthV2') {
				$config['refresh_token'] = $tokenSecret;
			}
			ConnectionManager::getDataSource($dbConfig)->setConfig($config);
			return true;
		}
	}

	/**
	 * 
	 * @param string $dbConfig The name of the Api config
	 * @param string $path The type of path to return, e.g. 'access', 'request', or 'authorize'
	 * @return string The assembled URI
	 */
	public static function getAuthUri($dbConfig, $path, $extra = array()) {
		$config = ConnectionManager::getDataSource($dbConfig)->config;
		if (!empty($config[$path])) {
			if ($config['authMethod'] == 'OAuth') {
				return $config['scheme'] . '://' . $config['host'] . '/' . $config[$path];
			} elseif ($config['authMethod'] == 'OAuthV2') {
				$uri = $config['scheme'] . '://' . $config['host'] . '/' . $config[$path];
				$query = array('redirect_uri' => $config['callback'], 'client_id' => $config['login']);
				if (!empty($config['scope'])) {
					$query['scope'] = $config['scope'];
				}
				return $uri . Router::queryString($query, $extra);
			}
		}
	}

}

?>