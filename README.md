# Apis Plugin

Since I started going through several restful apis things started to become repetitive. I decided to layout my code in a more 'proper ' fashion then.

## Installation

### Step 1: Clone or download to `Plugin/Apis`

### Step 2: Add your configuration to `database.php` and set it to the model

```
:: database.php ::
var $myapi = array(
	'datasource' => 'MyPlugin.MyPlugin', // Example: 'Github.Github'
	
	// These are only required for authenticated requests (write-access)
	'login' => '--Your API Key--',
	'password' => '--Your API Secret--',
);

:: MyModel.php ::
var $useDbConfig = 'myapi';
```

## Expanding functionality

### Creating a configuration map

_[MyPlugin]/Config/[MyPlugin].php_

REST paths must be ordered from most specific conditions to least (or none). This is because the map is iterated through
until the first path which has all of its required conditions met is found. If a path has no required conditions, it will
be used. Optional conditions aren't checked, but are added when building the request.

```
$config['Apis']['MyPlugin']['hosts'] = array(
	'oauth' => 'api.myplugin.com/login/oauth',
	'rest' => 'api.myplugin.com/v1',
);
$config['Apis']['MyPlugin']['oauth'] = array(
	'authorize' => 'authorize', // Example URI: api.linkedin.com/uas/oauth/authorize
	'request' => 'requestToken',
	'access' => 'accessToken',
	'login' => 'authenticate', // Like authorize, just auto-redirects
	'logout' => 'invalidateToken',
);
$config['Apis']['MyPlugin']['read'] = array(
	// field
	'people' => array(
		// api url
		'people/id=' => array(
			// required conditions
			'id',
		),
		'people/url=' => array(
			'url',
		),
		'people/~' => array(),
	),
	'people-search' => array(
		'people-search' => array(
		// optional conditions the api call can take
			'optional' => array(
				'keywords',
			),
		),
	),
);
$config['Apis']['MyPlugin']['write'] = array(
);
$config['Apis']['MyPlugin']['update'] = array(
);
$config['Apis']['MyPlugin']['delete'] = array(
);
```

### Creating a custom datasource 

Try browsing the apis datasource and seeing what automagic functionality you can hook into!

_[MyPlugin]/Model/Datasource/[MyPlugin].php_

```
Class MyPlugin extends ApisSource {
	// Examples of overriding methods & attributes:
	public $options = array(
		'format'    => 'json',
		'ps'		=> '&', // param separator
		'kvs'		=> '=', // key-value separator
	);
	// Key => Values substitutions in the uri-path right before the request is made. Scans uri-path for :keyname
	public $tokens = array();
	// Enable OAuth for the api
	public function __construct($config) {
		$config['method'] = 'OAuth'; // or 'OAuthV2'
		parent::__construct($config);
	}
	// Last minute tweaks
	public function beforeRequest(&$model, $request) {
		$request['header']['x-li-format'] = $this->options['format'];
		return $request;
	}
}
```

### Creating a custom oauth component (recommended approach)

_[MyPlugin]/Controller/Component/[MyPlugin].php_

```
App::uses('Oauth', 'Apis.Component/Component');
Class MyPluginComponent extends OauthComponent {
	// Override & supplement your methods & attributes
}
```

### On-the-fly customization
Lets say you don't feel like bothering to make a new plugin just to support your api, or the existing plugin doesn't cover
enough of the features. Good news! The plugin degrades gracefully and allows you to manually manipulate the request (thanks
to NeilCrookes' RESTful plugin).

Simply populate Model->request with any request params you wish and then fire off the related action. You can even continue
using the `$data` & `$this->data` for `save()` and `update()` or pass a `'path'` key to `find()` and it will automagically
be injected into your request object.

## Adding OAuth Authentication (requires a configuration map)

```
MyController extends AppController {
	var $components = array(
		'Apis.Oauth' => 'linkedin',
	);
	
	function connect() {
		$this->Oauth->connect();
	}
	
	function linkedin_callback() {
		$this->Oauth->callback();
	}
}
```

You can also use multiple database configurations

```
var $components = array(
	'Apis.Oauth' => array(
		'linkedin',
		'github',
		'flickr',
	);
);
```

However this requires you to specify which config to use before calling authentication methods

```
function beforeFilter() {
	$this->Oauth->useDbConfig = 'github';
}
```

## Roadmap / Concerns

**I'm eager to hear any recommendations or possible solutions.**

* **More automagic**
* **Better map scanning:**
  I'm not sure of a good way to add map scanning to `save()`, `update()` and `delete()` methods yet since I have little control
  over the arguments passed to the datasource. It is easy to supplement `find()` with information and utilize it for processing.
* **Complex query-building versatility:**
  Some APIs have multiple different ways of passing query params. Sometimes within the same request! I still need to flesh
  out param-building functions and options in the driver so that people extending the datasource have less work.
* **OAuth v2.0 Confirmed support:**
  Github uses v2.0. I updated the component to work accordingly, however I have to test that the HttpSocketOauth can use it.
