<?php

App::uses('OAuth2Authorize', 'Copula.Controller/Component/Auth');
App::uses('CakeRequest', 'Network');
App::uses('AuthComponent', 'Controller/Component');
App::uses('OAuth2Token', 'Copula.Model');
App::uses('Controller', 'Controller');

class FakeController extends Controller {

	public $components = array(
        'Session',
            'Auth' => array(
                'authorize' => array(
                    'Copula.OAuth2' => array(
                        'provider' => 'Google'
                )
            )
        )
    );

}

/**
 * @property OAuth2Authorize $auth
 */
class OAuth2AuthorizeTestCase extends CakeTestCase {

	var $fixtures = array('plugin.copula.o_auth2_token');

	public function setUp() {
		parent::setUp();
		$this->request = new CakeRequest();
		$this->controller = $this->getMock('FakeController');
		$this->components = $this->getMock('ComponentCollection');
		$this->components->expects($this->any())
				->method('getController')
				->will($this->returnValue($this->controller));
		$this->auth = new OAuth2Authorize($this->components);
	}

	public function tearDown() {
		unset($this->components, $this->auth, $this->request, $this->controller);
		parent::tearDown();
	}

	public function testConstructor() {
		$this->assertNotEmpty($this->auth->settings['provider']);
	}

	public function testTokenExists() {
		$user = array('id' => '1');
		$this->auth->store = $this->getMock('OAuth2Token', array('checkToken'));
		$this->auth->store->expects($this->once())
				->method('checkToken')
				->with('Google', '1')
				->will($this->returnValue(true));
		$this->assertTrue($this->auth->authorize($user, $this->request));
	}

	public function testTokenNotExists() {
		$user = array('id' => '1');
		$this->auth->store = $this->getMock('OAuth2Token', array('checkToken'));
		$this->auth->store->expects($this->once())
				->method('checkToken')
				->with('Google', '1')
				->will($this->returnValue(false));
		$this->assertTrue($this->auth->authorize($user, $this->request));
	}

	public function testCode() {
		$user = array('id' => '1');
		$code = md5(uniqid(rand(), true));
		$this->request->query['code'] = $code;
		$this->auth->store = $this->getMock('OAuth2Token', array('getAccessToken'));
		$tokenData = array(
			'access_token' => 'access',
			'refresh_token' => 'refresh',
			'expires_in' => '3600',
			'uid' => '1',
		);
		$token = new \League\OAuth2\Client\Token\AccessToken($tokenData);
		$this->auth->store->expects($this->once())
				->method('getAccessToken')
				->with($code, 'Google')
				->will($this->returnValue($token));
		$result = $this->auth->authorize($user, $this->request);
		$this->assertTrue($result);
	}

	public function testFallThrough() {
		$user = array('id' => '1');
		$result = $this->auth->authorize($user, $this->request);
		$this->assertTrue(CakeSession::check('Auth.redirect'));
		$this->assertTrue(CakeSession::check('Auth.OAuth.state'));
		$this->assertFalse($result);
	}
}
