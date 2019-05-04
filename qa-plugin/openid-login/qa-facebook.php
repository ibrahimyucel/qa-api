<?php
	
	class BaseFacebook
	{
		const VERSION = '3.2.2';
		const SIGNED_REQUEST_ALGORITHM = 'HMAC-SHA256';

		public static $CURL_OPTS = array(
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT        => 60,
			CURLOPT_USERAGENT      => 'facebook-php-3.2',
		);

		protected static $DROP_QUERY_PARAMS = array( 'code', 'state', 'signed_request' );

		public static $DOMAIN_MAP = array(
			'api'         => 'https://api.facebook.com/',
			'api_video'   => 'https://api-video.facebook.com/',
			'api_read'    => 'https://api-read.facebook.com/',
			'graph'       => 'https://graph.facebook.com/',
			'graph_video' => 'https://graph-video.facebook.com/',
			'www'         => 'https://www.facebook.com/',
		);

		protected $appId;
		protected $appSecret;
		protected $user;
		protected $signedRequest;
		protected $state;
		protected $accessToken = null;
		protected $fileUploadSupport = false;
		protected $trustForwarded = false;

		public function __construct($config) {
			$this->setAppId($config['appId']);
			$this->setAppSecret($config['secret']);
			if (isset($config['fileUpload'])) $this->setFileUploadSupport($config['fileUpload']);
			if (isset($config['trustForwarded']) && $config['trustForwarded']) $this->trustForwarded = true;
			$state = $this->getPersistentData('state');
			if (!empty($state)) $this->state = $state;
		}

		public function setAppId($appId) {
			$this->appId = $appId;
			return $this;
		}

		public function getAppId() {
			return $this->appId;
		}

		public function setApiSecret($apiSecret) {
			$this->setAppSecret($apiSecret);
			return $this;
		}

		public function setAppSecret($appSecret) {
			$this->appSecret = $appSecret;
			return $this;
		}

		public function getApiSecret() {
			return $this->getAppSecret();
		}

		public function getAppSecret() {
			return $this->appSecret;
		}

		public function setFileUploadSupport($fileUploadSupport) {
			$this->fileUploadSupport = $fileUploadSupport;
			return $this;
		}

		public function getFileUploadSupport() {
			return $this->fileUploadSupport;
		}

		public function useFileUploadSupport() {
			return $this->getFileUploadSupport();
		}

		public function setAccessToken($access_token) {
			$this->accessToken = $access_token;
			return $this;
		}

		public function setExtendedAccessToken() {
			try { // need to circumvent json_decode by calling _oauthRequest directly, since response isn't JSON format.
				$access_token_response = $this->_oauthRequest(
					$this->getUrl('graph', '/oauth/access_token'),
					$params = array(
						'client_id' => $this->getAppId(),
						'client_secret' => $this->getAppSecret(),
						'grant_type' => 'fb_exchange_token',
						'fb_exchange_token' => $this->getAccessToken(),
					)
				);
			}
			catch (FacebookApiException $e) { return false; }

			if (empty($access_token_response)) return false;

			$response = json_decode($access_token_response);
			if (!isset($response->access_token)) return false;

			$this->destroySession();
			$this->setPersistentData('access_token', $response->access_token);
		}

		public function getAccessToken() {
			if ($this->accessToken !== null) {
				return $this->accessToken;
			}

			$this->setAccessToken($this->getApplicationAccessToken());
			$user_access_token = $this->getUserAccessToken();
			if ($user_access_token) {
				$this->setAccessToken($user_access_token);
			}
			return $this->accessToken;
		}

		protected function getUserAccessToken() {
			$signed_request = $this->getSignedRequest();
			if ($signed_request) {
				// apps.facebook.com hands the access_token in the signed_request
				if (array_key_exists('oauth_token', $signed_request)) {
					$access_token = $signed_request['oauth_token'];
					$this->setPersistentData('access_token', $access_token);
					return $access_token;
				}

				// the JS SDK puts a code in with the redirect_uri of ''
				if (array_key_exists('code', $signed_request)) {
					$code = $signed_request['code'];
					if ($code && $code == $this->getPersistentData('code')) {
						// short-circuit if the code we have is the same as the one presented
						return $this->getPersistentData('access_token');
					}

					$access_token = $this->getAccessTokenFromCode($code, '');
					if ($access_token) {
						$this->setPersistentData('code', $code);
						$this->setPersistentData('access_token', $access_token);
						return $access_token;
					}
				}
				$this->clearAllPersistentData();
				return false;
			}

			$code = $this->getCode();
			if ($code && $code != $this->getPersistentData('code')) {
				$access_token = $this->getAccessTokenFromCode($code);
				if ($access_token) {
					$this->setPersistentData('code', $code);
					$this->setPersistentData('access_token', $access_token);
					return $access_token;
				}
				// code was bogus, so everything based on it should be invalidated.
				$this->clearAllPersistentData();
				return false;
			}

			// as a fallback, just return whatever is in the persistent  store, knowing nothing explicit (signed request, authorization
			// code, etc.) was present to shadow it (or we saw a code in $_REQUEST,  but it's the same as what's in the persistent store)
			return $this->getPersistentData('access_token');
		}

		public function getSignedRequest() {
			if (!$this->signedRequest) {
				if (!empty($_REQUEST['signed_request'])) {
					$this->signedRequest = $this->parseSignedRequest(
					$_REQUEST['signed_request']);
				} else if (!empty($_COOKIE[$this->getSignedRequestCookieName()])) {
					$this->signedRequest = $this->parseSignedRequest(
					$_COOKIE[$this->getSignedRequestCookieName()]);
				}
			}
			return $this->signedRequest;
		}

		public function getUser() {
			if ($this->user !== null) {
				return $this->user;
			}
			return $this->user = $this->getUserFromAvailableData();
		}

		protected function getUserFromAvailableData() {
			$signed_request = $this->getSignedRequest();
			if ($signed_request) {
				if (array_key_exists('user_id', $signed_request)) {
					$user = $signed_request['user_id'];

					if($user != $this->getPersistentData('user_id')){
						$this->clearAllPersistentData();
					}
					$this->setPersistentData('user_id', $signed_request['user_id']);
					return $user;
				}
				$this->clearAllPersistentData();
				return 0;
			}

			$user = $this->getPersistentData('user_id', $default = 0);
			$persisted_access_token = $this->getPersistentData('access_token');

			$access_token = $this->getAccessToken();
			if ($access_token && $access_token != $this->getApplicationAccessToken() && !($user && $persisted_access_token == $access_token)) {
				$user = $this->getUserFromAccessToken();
				if ($user) $this->setPersistentData('user_id', $user);
				else $this->clearAllPersistentData();
			}

			return $user;
		}

		public function getLoginUrl($params=array()) {
			$this->establishCSRFTokenState();
			$currentUrl = $this->getCurrentUrl();

			// if 'scope' is passed as an array, convert to comma separated list
			$scopeParams = isset($params['scope']) ? $params['scope'] : null;
			if ($scopeParams && is_array($scopeParams)) $params['scope'] = implode(',', $scopeParams);

			return $this->getUrl( 'www', 'dialog/oauth',
				array_merge(array( 'client_id' => $this->getAppId(), 'redirect_uri' => $currentUrl, /* possibly overwritten */ 
				'state' => $this->state), $params));
		}

		public function getLogoutUrl($params=array()) {
			return $this->getUrl( 'www', 'logout.php',
				array_merge(array( 'next' => $this->getCurrentUrl(), 'access_token' => $this->getUserAccessToken() ), $params) );
		}

		public function getLoginStatusUrl($params=array()) {
			return $this->getUrl( 'www', 'extern/login_status.php',
				array_merge(array( 'api_key' => $this->getAppId(), 'no_session' => $this->getCurrentUrl(),
				'no_user' => $this->getCurrentUrl(), 'ok_session' => $this->getCurrentUrl(), 'session_version' => 3 ), $params)
			);
		}

		public function api(/* polymorphic */) {
			$args = func_get_args();
			if (is_array($args[0])) return $this->_restserver($args[0]);
			else return call_user_func_array(array($this, '_graph'), $args);
		}

		protected function getSignedRequestCookieName() {
			return 'fbsr_'.$this->getAppId();
		}

		protected function getMetadataCookieName() {
			return 'fbm_'.$this->getAppId();
		}

		protected function getCode() {
			if (isset($_REQUEST['code'])) {
				if ($this->state !== null && isset($_REQUEST['state']) && $this->state === $_REQUEST['state']) {
					$this->state = null;
					$this->clearPersistentData('state');
					return $_REQUEST['code'];
				} else {
					self::errorLog('CSRF state token does not match one provided.');
					return false;
				}
			}
			return false;
		}

		protected function getUserFromAccessToken() {
			try {
				$user_info = $this->api('/me');
				return $user_info['id'];
			} catch (FacebookApiException $e) {
				return 0;
			}
		}

		protected function getApplicationAccessToken() {
			return $this->appId.'|'.$this->appSecret;
		}

		protected function establishCSRFTokenState() {
			if ($this->state === null) {
				$this->state = md5(uniqid(mt_rand(), true));
				$this->setPersistentData('state', $this->state);
			}
		}

		protected function getAccessTokenFromCode($code, $redirect_uri = null) {
			if (empty($code)) return false;
			if ($redirect_uri === null) $redirect_uri = $this->getCurrentUrl();

			try {
				// need to circumvent json_decode by calling _oauthRequest directly, since response isn't JSON format.
				$access_token_response =
				$this->_oauthRequest(
				$this->getUrl('graph', '/oauth/access_token'), $params = array('client_id' => $this->getAppId(),
					'client_secret' => $this->getAppSecret(), 'redirect_uri' => $redirect_uri, 'code' => $code));
			} catch (FacebookApiException $e) {
				// most likely that user very recently revoked authorization. In any event, we don't have an access token, so say so.
				return false;
			}

			if (empty($access_token_response)) return false;

			$response = json_decode($access_token_response);
			if (!isset($response->access_token)) return false;
			return $response->access_token;
		}

		protected function _restserver($params) {
			// generic application level parameters
			$params['api_key'] = $this->getAppId();
			$params['format'] = 'json-strings';

			$result = json_decode($this->_oauthRequest( $this->getApiUrl($params['method']), $params ), true);

			// results are returned, errors are thrown
			if (is_array($result) && isset($result['error_code'])) {
				$this->throwAPIException($result); // @codeCoverageIgnoreStart
			}
			// @codeCoverageIgnoreEnd
			$method = strtolower($params['method']);
			if ($method === 'auth.expiresession' || $method === 'auth.revokeauthorization') $this->destroySession();
			return $result;
		}

		protected function isVideoPost($path, $method = 'GET') {
			if ($method == 'POST' && preg_match("/^(\/)(.+)(\/)(videos)$/", $path)) return true;
			return false;
		}

		protected function _graph($path, $method = 'GET', $params = array()) {
			if (is_array($method) && empty($params)) {
				$params = $method;
				$method = 'GET';
			}
			$params['method'] = $method; // method override as we always do a POST

			if ($this->isVideoPost($path, $method)) $domainKey = 'graph_video';
			else $domainKey = 'graph';

			$result = json_decode($this->_oauthRequest( $this->getUrl($domainKey, $path), $params ), true);

			// results are returned, errors are thrown
			if (is_array($result) && isset($result['error'])) {
				$this->throwAPIException($result); // @codeCoverageIgnoreStart
			}
			return $result; // @codeCoverageIgnoreEnd
		}

		protected function _oauthRequest($url, $params) {
			if (!isset($params['access_token'])) $params['access_token'] = $this->getAccessToken();
			if (isset($params['access_token'])) $params['appsecret_proof'] = $this->getAppSecretProof($params['access_token']);

			// json_encode all params values that are not strings
			foreach ($params as $key => $value) {
				if (!is_string($value)) $params[$key] = json_encode($value);
			}
			return $this->makeRequest($url, $params);
		}

		protected function getAppSecretProof($access_token) {
			return hash_hmac('sha256', $access_token, $this->getAppSecret());
		}

		protected function makeRequest($url, $params, $ch=null) {
			if (!$ch) $ch = curl_init();

			$opts = self::$CURL_OPTS;
			if ($this->getFileUploadSupport()) $opts[CURLOPT_POSTFIELDS] = $params;
			else $opts[CURLOPT_POSTFIELDS] = http_build_query($params, null, '&');
			$opts[CURLOPT_URL] = $url;

			// disable the 'Expect: 100-continue' behaviour. This causes CURL to wait for 2 seconds if the server does not support this header.
			if (isset($opts[CURLOPT_HTTPHEADER])) {
				$existing_headers = $opts[CURLOPT_HTTPHEADER];
				$existing_headers[] = 'Expect:';
				$opts[CURLOPT_HTTPHEADER] = $existing_headers;
			} else $opts[CURLOPT_HTTPHEADER] = array('Expect:');

			curl_setopt_array($ch, $opts);
			$result = curl_exec($ch);

			if ($result === false && empty($opts[CURLOPT_IPRESOLVE])) {
				$matches = array();
				$regex = '/Failed to connect to ([^:].*): Network is unreachable/';
				if (preg_match($regex, curl_error($ch), $matches)) {
					if (strlen(@inet_pton($matches[1])) === 16) {
						self::errorLog('Invalid IPv6 configuration on server, '.
						'Please disable or get native IPv6 on your server.');
						self::$CURL_OPTS[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
						curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
						$result = curl_exec($ch);
					}
				}
			}

			if ($result === false) {
				$e = new FacebookApiException(array( 'error_code' => curl_errno($ch), 'error' => array( 'message' => curl_error($ch), 
					'type' => 'CurlException', ), ));
				curl_close($ch);
				throw $e;
			}
			curl_close($ch);
			return $result;
		}

		protected function parseSignedRequest($signed_request) {
			list($encoded_sig, $payload) = explode('.', $signed_request, 2);

			// decode the data
			$sig = self::base64UrlDecode($encoded_sig);
			$data = json_decode(self::base64UrlDecode($payload), true);

			if (strtoupper($data['algorithm']) !== self::SIGNED_REQUEST_ALGORITHM) {
				self::errorLog( 'Unknown algorithm. Expected ' . self::SIGNED_REQUEST_ALGORITHM);
				return null;
			}

			// check sig
			$expected_sig = hash_hmac('sha256', $payload,
			$this->getAppSecret(), $raw = true);
			if ($sig !== $expected_sig) {
				self::errorLog('Bad Signed JSON signature!');
				return null;
			}

			return $data;
		}

		protected function makeSignedRequest($data) {
			if (!is_array($data)) throw new InvalidArgumentException( 'makeSignedRequest expects an array. Got: ' . print_r($data, true));
			$data['algorithm'] = self::SIGNED_REQUEST_ALGORITHM;
			$data['issued_at'] = time();
			$json = json_encode($data);
			$b64 = self::base64UrlEncode($json);
			$raw_sig = hash_hmac('sha256', $b64, $this->getAppSecret(), $raw = true);
			$sig = self::base64UrlEncode($raw_sig);
			return $sig.'.'.$b64;
		}

		protected function getApiUrl($method) {
			static $READ_ONLY_CALLS =
				array('admin.getallocation' => 1,
				'admin.getappproperties' => 1,
				'admin.getbannedusers' => 1,
				'admin.getlivestreamvialink' => 1,
				'admin.getmetrics' => 1,
				'admin.getrestrictioninfo' => 1,
				'application.getpublicinfo' => 1,
				'auth.getapppublickey' => 1,
				'auth.getsession' => 1,
				'auth.getsignedpublicsessiondata' => 1,
				'comments.get' => 1,
				'connect.getunconnectedfriendscount' => 1,
				'dashboard.getactivity' => 1,
				'dashboard.getcount' => 1,
				'dashboard.getglobalnews' => 1,
				'dashboard.getnews' => 1,
				'dashboard.multigetcount' => 1,
				'dashboard.multigetnews' => 1,
				'data.getcookies' => 1,
				'events.get' => 1,
				'events.getmembers' => 1,
				'fbml.getcustomtags' => 1,
				'feed.getappfriendstories' => 1,
				'feed.getregisteredtemplatebundlebyid' => 1,
				'feed.getregisteredtemplatebundles' => 1,
				'fql.multiquery' => 1,
				'fql.query' => 1,
				'friends.arefriends' => 1,
				'friends.get' => 1,
				'friends.getappusers' => 1,
				'friends.getlists' => 1,
				'friends.getmutualfriends' => 1,
				'gifts.get' => 1,
				'groups.get' => 1,
				'groups.getmembers' => 1,
				'intl.gettranslations' => 1,
				'links.get' => 1,
				'notes.get' => 1,
				'notifications.get' => 1,
				'pages.getinfo' => 1,
				'pages.isadmin' => 1,
				'pages.isappadded' => 1,
				'pages.isfan' => 1,
				'permissions.checkavailableapiaccess' => 1,
				'permissions.checkgrantedapiaccess' => 1,
				'photos.get' => 1,
				'photos.getalbums' => 1,
				'photos.gettags' => 1,
				'profile.getinfo' => 1,
				'profile.getinfooptions' => 1,
				'stream.get' => 1,
				'stream.getcomments' => 1,
				'stream.getfilters' => 1,
				'users.getinfo' => 1,
				'users.getloggedinuser' => 1,
				'users.getstandardinfo' => 1,
				'users.hasapppermission' => 1,
				'users.isappuser' => 1,
				'users.isverified' => 1,
				'video.getuploadlimits' => 1);
			$name = 'api';
			
			if (isset($READ_ONLY_CALLS[strtolower($method)])) $name = 'api_read';
			else if (strtolower($method) == 'video.upload') $name = 'api_video';
			return self::getUrl($name, 'restserver.php');
		}

		protected function getUrl($name, $path='', $params=array()) {
			$url = self::$DOMAIN_MAP[$name];
			if ($path) {
				if ($path[0] === '/') $path = substr($path, 1);
				$url .= $path;
			}
			if ($params) $url .= '?' . http_build_query($params, null, '&');
			return $url;
		}

		protected function getHttpHost() {
			if ($this->trustForwarded && isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
				return $_SERVER['HTTP_X_FORWARDED_HOST'];
			}
			return $_SERVER['HTTP_HOST'];
		}

		protected function getHttpProtocol() {
			if ($this->trustForwarded && isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
				if ($_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') return 'https';
				return 'http';
			}
			/*apache + variants specific way of checking for https*/
			if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] == 1)) return 'https';			
			
			if (isset($_SERVER['SERVER_PORT']) && ($_SERVER['SERVER_PORT'] === '443')) return 'https'; /*nginx way of checking for https*/
			return 'http';
		}

		protected function getBaseDomain() {
			$metadata = $this->getMetadataCookie();
			if (array_key_exists('base_domain', $metadata) && !empty($metadata['base_domain'])) {
				return trim($metadata['base_domain'], '.');
			}
			return $this->getHttpHost();
		}

		protected function getCurrentUrl() {
			$protocol = $this->getHttpProtocol() . '://';
			$host = $this->getHttpHost();
			$currentUrl = $protocol.$host.$_SERVER['REQUEST_URI'];
			$parts = parse_url($currentUrl);

			$query = '';
			if (!empty($parts['query'])) {				
				$params = explode('&', $parts['query']); // drop known fb params
				$retained_params = array();
				foreach ($params as $param) {
					if ($this->shouldRetainParam($param)) $retained_params[] = $param;
				}
				if (!empty($retained_params)) $query = '?'.implode($retained_params, '&');
			}

			// use port if non default
			$port = isset($parts['port']) && (($protocol === 'http://' && $parts['port'] !== 80) || ($protocol === 'https://' && $parts['port'] !== 443))
				? ':' . $parts['port'] : '';
			
			return $protocol . $parts['host'] . $port . $parts['path'] . $query; // rebuild
		}

		protected function shouldRetainParam($param) {
			foreach (self::$DROP_QUERY_PARAMS as $drop_query_param) {
				if (strpos($param, $drop_query_param.'=') === 0) return false;
			}
			return true;
		}

		protected function throwAPIException($result) {
			$e = new FacebookApiException($result);
			switch ($e->getType()) {					
				case 'OAuthException': // OAuth 2.0 Draft 00 style					
				case 'invalid_token': // OAuth 2.0 Draft 10 style					
				case 'Exception': // REST server errors are just Exceptions
					$message = $e->getMessage();
					if ((strpos($message, 'Error validating access token') !== false) || (strpos($message, 'Invalid OAuth access token') !== false) ||
					(strpos($message, 'An active access token must be used') !== false) ) {
						$this->destroySession();
					}
					break;
			}

			throw $e;
		}

		protected static function errorLog($msg) {
			// disable error log if we are running in a CLI environment @codeCoverageIgnoreStart
			if (php_sapi_name() != 'cli') error_log($msg);
		}

		protected static function base64UrlDecode($input) {
			return base64_decode(strtr($input, '-_', '+/'));
		}

		protected static function base64UrlEncode($input) {
			$str = strtr(base64_encode($input), '+/', '-_');
			$str = str_replace('=', '', $str);
			return $str;
		}

		// Destroy the current session
		public function destroySession() {
			$this->accessToken = null;
			$this->signedRequest = null;
			$this->user = null;
			$this->clearAllPersistentData();

			// Javascript sets a cookie that will be used in getSignedRequest that we need to clear if we can
			$cookie_name = $this->getSignedRequestCookieName();
			if (array_key_exists($cookie_name, $_COOKIE)) {
				unset($_COOKIE[$cookie_name]);
				if (!headers_sent()) {
					$base_domain = $this->getBaseDomain();
					setcookie($cookie_name, '', 1, '/', '.'.$base_domain);
				} else { // @codeCoverageIgnoreStart
					self::errorLog( 'There exists a cookie that we wanted to clear that we couldn\'t clear because headers was already sent. Make sure to do the first API call before outputing anything.');
					// @codeCoverageIgnoreEnd
				}
			}
		}

		protected function getMetadataCookie() {
			$cookie_name = $this->getMetadataCookieName();
			if (!array_key_exists($cookie_name, $_COOKIE)) return array();

			// The cookie value can be wrapped in "-characters so remove them
			$cookie_value = trim($_COOKIE[$cookie_name], '"');

			if (empty($cookie_value)) return array();

			$parts = explode('&', $cookie_value);
			$metadata = array();
			foreach ($parts as $part) {
				$pair = explode('=', $part, 2);
				if (!empty($pair[0])) {
					$metadata[urldecode($pair[0])] = (count($pair) > 1) ? urldecode($pair[1]) : '';
				}
			}
			return $metadata;
		}

		protected static function isAllowedDomain($big, $small) {
			if ($big === $small) return true;
			return self::endsWith($big, '.'.$small);
		}

		protected static function endsWith($big, $small) {
			$len = strlen($small);
			if ($len === 0) return true;
			return substr($big, -$len) === $small;
		}

		abstract protected function setPersistentData($key, $value);
		abstract protected function getPersistentData($key, $default = false);
		abstract protected function clearPersistentData($key);
		abstract protected function clearAllPersistentData();
	}