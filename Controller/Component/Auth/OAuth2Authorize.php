<?php

App::uses('BaseAuthorize', 'Controller/Component/Auth');
App::uses('OAuth2Token', 'Copula.Model');

/**
 * This originally was written to only return a yes/no for whether the user was authorized.
 * Single responsibility is good, but it created more problems than it solved,
 * so as much of the oauth code as possible has been pushed to the model layer.
 * This object should have all of the procedural code.
 * Also, I cut out some "features". If you need additional complications, write them.
 *
 * @property League\OAuth2\Client\Provider $provider
 * @property OAuth2Token $store
 */

 class OAuth2Authorize extends BaseAuthorize {

/**
 * It would be nice if we could pass our objects into the constructor.
 * Unfortunately we don't control the calling environment
 *
 * @param \ComponentCollection $collection
 * @param array $settings
 */
    public function __construct(\ComponentCollection $collection, $settings = array()) {
		parent::__construct($collection, $settings);
		//storing data in the session is also possible, and it's less complicated to set up
		//but storing the data in the database is probably more robust
		$this->store = ClassRegistry::init('Copula.OAuth2Token');
		/*
        If you want to do per-controller config, this is the place to check for it.
		*/
    }

/**
 * Checks user authorization.
 *
 * @param array $user Active user data
 * @param CakeRequest $request Request instance.
 * @return bool
 */
    public function authorize($user, \CakeRequest $request) {
		//the authorize object config needs to contain information about which APIs it uses.
		//I don't think there's any use case that needs to authorize multiple APIs in the same controller
		$providerName = $this->settings['provider'];
		//indexing by service and user_id should be sufficient
		if ($this->store->checkToken($providerName, $user['id'])) {
			//token exists, return true
			return true;
		}
        /*
          The following is a proxy for determining whether we've been called by the OAuth callback
          It is possible to test this more accurately but it shouldn't be necessary
          However, note that this will collide with other uses of "code" as a query parameter for controllers that this Authorize object is attached to
          Doing something simple with an obvious pitfall is probably better than doing something complicated with less obvious flaws
        */
		if (isset($request->query['code'])) {
			//checking the state is optional, technically
			//but storing it in the session is probably correct
			if (isset($request->query['state']) && (CakeSession::read('Auth.OAuth.state') !== $request->query['state'])) {
				return false;
			}
			$token = $this->store->getAccessToken($request->query['code'], $providerName);
			//there's probably not any situation where you would want to call getAccessToken and not save the result in the db, but the code should still be separate
			return (bool)$this->store->saveToken($token, $providerName, $user['id']);
		}
		//if no token is found for a given API, set the redirect and return false
		//it may be better to set AuthComponent->unauthorizedRedirect but it's a little harder to test
		$state = $this->_generateState();
		CakeSession::write('Auth.redirect', $this->store->getAuthorizationUrl(compact('state')));
		CakeSession::write('Auth.OAuth.state', $state);
		return false;
    }

/**
 * Returns a random string.
 *
 * This is not cryptographically secure. It could be, but it isn't.
 * If you need that, see this stackoverflow post:
 * @link http://tinyurl.com/k7y8hq7
 */
    protected function _generateState() {
    	return md5(uniqid(rand(), true));
    }
 }
