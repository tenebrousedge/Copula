<?php

class ExampleController extends CopulaAppController {

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

    public function getData() {
        // attempts to access this function will trigger oauth authorization
    }

    public function oauth2callback() {
        /*
          This function will also trigger authorization
          But it's probably a good idea to have a URL specifically for responding to the OAuth callback
        */
        $this->Session->setFlash('OAuth authorization obtained');
        $this->redirect('/');
    }
}
