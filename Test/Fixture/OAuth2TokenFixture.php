<?php

class OAuth2TokenFixture extends CakeTestFixture {

	var $name = "OAuth2Token";
	var $table = 'oauth2_tokens';
	var $fields = array(
		'id' => array(
			'type' => 'integer',
			'key' => 'primary'
		),
		'user_id' => array(
			'type' => 'integer',
			'key' => 'index',
			'null' => false
		),
		'access_token' => array(
			'type' => 'string',
			'null' => false
		),
		'refresh_token' => array(
			'type' => 'string',
			'null' => true
		),
		'modified' => 'datetime',
		'provider' => array(
			'type' => 'string',
			'null' => false
		),
		'expires' => array('type' => 'string', 'null' => true),
	);
	var $records = array(
		array(
			'id' => '2',
			'user_id' => '1',
			'access_token' => 'ya29.AHES6ZTopEd2PaRCaLZDd0B9TKNqdt857DYrlC-Welo9d84LaElzAg',
			'modified' => '2012-11-07 23:10:18',
			'refresh_token' => '1/jr6xd0f83uXDh-sBE3eO_lo8qMr11pOQXalzfTAYXGk',
			'provider' => 'testprovider',
			'expires' => '3600'
		),
		array(
			'id' => '3',
			'user_id' => '2',
			'access_token' => 'ya29.AHES6ZTopEd2PaRCaLZDd0B9TKNqdt857DYrlC-Welo9d84LaElzAg',
			'modified' => '2012-11-07 23:10:18',
			'provider' => 'testprovider',
			'refresh_token' => null,
			'expires' => null
		),
		array(
			'id' => '4',
			'user_id' => '3',
			'access_token' => 'ya29.AHES6ZTopEd2PaRCaLZDd0B9TKNqdt857DYrlC-Welo9d84LaElzAg',
			'modified' => '2012-11-07 23:10:18',
			'refresh_token' => '',
			'provider' => 'cloudprint',
			'expires' => '3600'
		)
	);

	public function init() {
		$this->records[] = array(
			'id' => '5',
			'user_id' => '6',
			'access_token' => 'ya29.AHES6ZTopEd2PaRCaLZDd0B9TKNqdt857DYrlC-Welo9d84LaElzAg',
			'modified' => (string) date('Y-m-d H:i:s'),
			'refresh_token' =>'',
			'provider' => 'cloudprint',
			'expires' => '3600',
		);
	}

}

?>