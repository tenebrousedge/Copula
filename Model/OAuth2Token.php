<?php

/**
 *
 * @subpackage model
 * @package Copula
 */
App::uses('CopulaAppModel', 'Copula.Model');

use League\OAuth2\Client\Token\AccessToken as AccessToken;
use League\OAuth2\Client\Grant\RefreshToken as RefreshToken;

/**
 * This class uses several classes from League\OAuth2
 */
class OAuth2Token extends CopulaAppModel {

	public $useTable = 'oauth2_tokens';

	public $validate = array(
		'user_id' => array(
	                'numeric' => array(
	                        'rule' => 'notEmpty',
	                        'message' => 'user_id must be set'
	                ),
	                'unique' => array(
	                        'rule' => 'isUnique',
	                        'message' => 'user_id must be unique'
	                )
		),
		'provider' => array(
			'rule' => array('inList', array('Eventbrite', 'Facebook', 'Github', 'Google', 'Instagram', 'LinkedIn', 'Microsoft', 'Vkontakte')),
			'message' => 'API must be supported by league/oauth2-client'
		),
		'expires' => array(
			'rule' => array('numeric'),
			'message' => 'Expiration needs to be a unix timestamp'
		)
	);

/**
 * Retrieve a token from the database. Refresh it if necessary.
 *
 * @param string $userId  the associated user id
 * @param string $apiName the name of an API to search for
 * @return AccessToken token data
 */
	public function findToken($providerName, $userId) {
		$conditions = array(
			'user_id' => $userId,
			'provider_name' => $providerName
		);
		$result = $this->find('first', compact('conditions'));
		if (!empty($result)) {
			//return a token object
			$token = $this->createTokenObject($result[$this->alias]);
			if ($this->isExpired($token)) {
				$refreshed = $this->refreshToken($token, $this->_getProvider($providerName));
				$this->saveToken($token, $providerName, $userId);
				return $refreshed;
			}
			return $token;
		}
		return false;
	}

/**
 * Creates a token object from a data array
 *
 * @param array $tokenData
 */
	public function createTokenObject(array $tokenData) {
		if (isset($tokenData['user_id'])) {
			$tokenData['uid'] = $tokenData['user_id'];
			unset($tokenData['user_id']);
		}
		return new AccessToken($tokenData);
	}

/**
 * Checks whether a token record exists in the database
 *
 * It assumes the token is indexed by providerName and userId
 * @param string $providerName
 * @param string $userId
 */
	public function checkToken($providerName, $userId) {
		$conditions = array(
			'user_id' => $userId,
			'provider_name' => $providerName
		);
		return $this->hasAny($conditions);
	}

/**
 * Tests whether the token supplied is expired
 *
 * @param AccessToken $token
 */
	public function isExpired(AccessToken $token) {
		return $token->expires <= time();
	}

/**
 * This fetches an access token from the remote API
 *
 * @param string $code
 * @param string $providerName
 */
	public function getAccessToken($code, $providerName) {
		return $this->_getProvider($providerName)->getAccessToken('authorization_code', array('code' => $request->query['code']));
	}
/**
 * Save method
 *
 * @param AccessToken $token
 * @param string $providerName
 * @param string $userId
 */
	public function saveToken(AccessToken $token, $providerName, $userId) {
		$saveData = array(
			'provider' => $providerName,
			'access_token' => $token->accessToken,
			'user_id' => $userId,
			'expires' => $token->expires,
		);
		return $this->save($saveData);
	}

/**
 * Refresh token
 *
 * @param string $oldToken
 * @param Provider $provider
 */
	public function refreshToken(AccessToken $token, $provider) {
		return $provider->getAccessToken(new RefreshToken(), array('refresh_token' => $token->refreshToken));
	}

/**
 * If you want to use a custom provider, override this method.
 *
 * @param string $providerName The name of the provider object
 */
    protected function _getProvider($providerName) {
    	if (!isset($this->provider)) {
			$obj = '\\League\\OAuth2\\Client\\Provider\\' . $providerName;
			//provider settings vary, and should probably be kept out of source control
			$this->provider = new $obj(Configure::read("Importer.OAuth.$providerName"));
    	}
    	return $this->provider;
    }
}
