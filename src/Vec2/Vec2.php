<?php
    namespace Vec2;
    use \Exception;
    use \CURLFile;

    use Vec2\Objects\Response as R;
    use Vec2\Objects\Profile;
    use Vec2\Objects\User;
    use Vec2\Objects\Vector;
    use Vec2\Objects\Tag;
    use Vec2\Objects\Customer;
    use Vec2\Objects\Sale;
    use Vec2\Objects\Payout;
    use Vec2\Objects\Log;

    use anlutro\cURL\cURL;
    use anlutro\cURL\Response;
    use anlutro\cURL\Request;
    use Lcobucci\JWT\Parser;
    use Lcobucci\JWT\Builder;
    use Lcobucci\JWT\Signer\Hmac\Sha256;

    class Vec2 {
        const SCOPE_PROFILE = 'profile';
        const SCOPE_USER = 'user';

        const GET = 'GET';
        const POST = 'POST';
        const PATCH = 'PATCH';
        const DELETE = 'DELETE';

        /** @var array log paths */
        protected $_logs;

        /** @var boolean Whether session storage is enabled */
        protected $_session_enabled;
        /** @var boolean Whether the cookie storage is enabled */
        protected $_cookie_enabled;

        /** @var cURL Contains the cURL client */
        protected $_curl;
        /** @var string Contains the Vec2 application client id */
        protected $_client_id;
        /** @var string Contains the Vec2 application secret */
        protected $_secret;
        /** @var string Contains the Vec2 application key */
        protected $_key;
        /** @var string Contains the Vec2 API url */
        protected $_url;

        /** @var string Contains the access token */
        protected $_access_token;
        /** @var string Contains the refresh token */
        protected $_refresh_token;

        /** @var string Contains the current method */
        protected $_method;
        /** @var array Contains the files */
        protected $_files;

        /**
         * Builds a URL
         *
         * @param array|string $path The path to join with the API url
         */
        protected function build($path, array $query = []) : string {
            $url = trim($this->_url, '/');
            // normalize to array
            if(!is_array($path)) {
                $path = [$path];
            }
            foreach($path as $p) {
                $url .= '/' . trim($p, '/');
            }
            // chekc query params
            if(!empty($query)) {
                $url .= '?'.http_build_query($query);
            }
            return $url;
        }

        /**
         * Creates a jwt
         *
         * @param array $payload
         * @return string
         */
        protected function jwt(array $payload) : string {
            $builder = (new Builder())->setIssuedAt(time())->setExpiration(time() + 3600);
            foreach($payload as $k => $v) {
                $builder->set($k, $v);
            }
            return $builder->sign(new Sha256(), $this->_secret)->getToken()->__toString();
        }

        /**
         * Builds a new Vec2 API
         *
         * @param string $client_id
         * @param string $secret
         * @param string $url
         */
        public function __construct(string $client_id, string $secret, string $key, string $url = 'https://api.vec2.design') {
            $this->_curl = new cURL();
            $this->_client_id = $client_id;
            $this->_secret = $secret;
            $this->_key = $key;
            $this->_url = $url;
            $this->_method = Vec2::GET;
            $this->_files = [];
            $this->_logs = [];
            $this->_session_enabled = false;
            $this->_cookie_enabled = false;
        }

        /**
         * Adds a log path
         * Can add multiple for logging in multiple files
         * The condition will be called with following parameters:
         * METHOD, URL, DATA, AUTH_ENDPOINT FLAG
         *
         * @param string $file
         * @param [array] $logThese
         * @param [callable] $condition
         * @return Vec2
         */
        public function enableSqliteLog(string $file, array $log_these = [], $condition = null) : Vec2 {
            $this->_logs[] = new Log($file, $log_these, $condition);
            return $this;
        }

        /**
         * Enables the session storage
         *
         * @return Vec2
         */
        public function enableSessionStorage() : Vec2 {
            // start session if not up
            if(session_status() == \PHP_SESSION_NONE) {
                session_start();
            }
            $this->_session_enabled = true;
            return $this;
        }

        /**
         * Enables cookie storage
         *
         * @return Vec2
         */
        public function enableCookieStorage() : Vec2 {
            $this->_cookie_enabled = true;
            return $this;
        }

        /**
         * Stores a value in an enabled storage
         *
         * @param string $name
         * @param mixed $value
         */
        public function store(string $name, $value) {
            if($this->_session_enabled) {
                $_SESSION['vec2_'.$name] = $value;
            } else if($this->_cookie_enabled) {
                setcookie('vec2_'.$name, $value, time() + 3600 * 24, '/');
            }
        }

        /**
         * Fetches a value
         *
         * @param string $name
         * @return mixed
         */
        public function fetch(string $name) {
            if($this->_session_enabled && isset($_SESSION['vec2_'.$name])) {
                return $_SESSION['vec2_'.$name];
            } else if($this->_cookie_enabled && isset($_COOKIE['vec2_'.$name])) {
                return $_COOKIE['vec2_'.$name];
            }
            return '';
        }

        /**
         * Sets the method to GET
         *
         * @return Vec2
         */
        public function get() : Vec2 {
            $this->_method = Vec2::GET;
            return $this;
        }

        /**
         * Sets the method to POST
         *
         * @return Vec2
         */
        public function post() : Vec2 {
            $this->_method = Vec2::POST;
            return $this;
        }

        /**
         * Sets the method to PATCH
         *
         * @return Vec2
         */
        public function patch() : Vec2 {
            $this->_method = Vec2::PATCH;
            return $this;
        }

        /**
         * Sets the method to DELETE
         *
         * @return Vec2
         */
        public function delete() : Vec2 {
            $this->_method = Vec2::DELETE;
            return $this;
        }

        /**
         * Adds a file to the request
         *
         * @param string $filename
         * @param string $filetype
         * @return Vec2
         */
        public function file(string $postname, string $filename, string $filetype) : Vec2 {
            $this->_files[$postname] = new CURLFile($filename, $filetype, $postname);
            return $this;
        }

        /**
         * Sets the current access token
         *
         * @param string $token
         */
        public function setAccessToken(string $token) {
            $this->store('accessToken', $token);
            $this->_access_token = $token;
        }

        /**
         * Sets the current refresh token
         *
         * @param string $token
         */
        public function setRefreshToken(string $token) {
            $this->store('refreshToken', $token);
            $this->_refresh_token = $token;
        }

        /**
         * Gets the access token
         *
         * @return string
         */
        public function getAccessToken() : string {
            if(is_null($this->_access_token)) {
                $this->_access_token = $this->fetch('accessToken');
            }
            return $this->_access_token;
        }

        /**
         * Gets the refresh token
         *
         * @return string
         */
        public function getRefreshToken() : string {
            if(is_null($this->_refresh_token)) {
                $this->_refresh_token = $this->fetch('refreshToken');
            }
            return $this->_refresh_token;
        }

        /**
         * Calls for a login
         *
         * @param string $redirect URL to redirect to afterwards
         */
        public function login(string $redirect = '') {
            $url = $this->build(['authenticate', 'try'], [
                'client' => $this->_client_id,
                'key' => $this->_key,
                'redirect' => $redirect
            ]);
            header('Location: '.$url);
            exit();
        }

        /**
         * Used to process auth callback
         */
        public function auth() {
            // check for token
            if(isset($_GET['token'])) {
                $token = $_GET['token'];
                // try parsing the token
                try {
                    $token = (new Parser())->parse($token);
                    // verify token
                    if($token->verify(new Sha256(), $this->_secret)) {
                        $this->setAccessToken($token->getClaim('accessToken'));
                        $this->setRefreshToken($token->getClaim('refreshToken'));
                    }
                } catch(Exception $e) {
                    /* ignore */
                }
            }
        }

        /**
         * Calls an API endpoint
         *
         * @return mixed
         */
        public function call(string $url, array $data = [], bool $auth_endpoint = false) {
            // build query URL if necessary
            if($this->_method==Vec2::GET && !empty($data)) {
                $url = $url . '?' . http_build_query($data);
                $data = [];
            } else {
                // append the files
                foreach($this->_files as $name => $file) {
                    $data[$name] = $file;
                }
                $this->_files = [];
            }
            // request
            $req = $this->_curl->newRequest($this->_method, $url, $data)
                    ->setEncoding(Request::ENCODING_RAW)
                    ->setHeader('From', $this->_client_id.':'.$this->_key)
                    ->setHeader('Content-Type', 'multipart/form-data');
            // check for auth endpoint
            $refreshToken = $this->getRefreshToken();
            if($auth_endpoint && !empty($refreshToken) && !empty($this->getAccessToken())) {
                $jwt = $this->jwt([
                    'refreshToken' => $refreshToken,
                    'accessToken' => $this->getAccessToken()
                ]);
                $req->setHeader('Authorization', 'Bearer '.$jwt);
            }
            $response = $req->send();
            // check for Authorization update
            if($auth_endpoint && isset($response->headers['authorization'])) {
                $token = preg_replace('/Bearer\s*/', '', $response->headers['authorization']);
                try {
                    $token = (new Parser())->parse($token);
                    // try to verify
                    if($token->verify(new Sha256(), $this->_secret)) {
                        $this->setRefreshToken($refreshToken);
                        $this->setAccessToken($token->getClaim('accessToken'));
                    }
                } catch(Exception $e) {
                    // something went wrong here
                    // maybe MITM
                    return [
                        'status' => false
                    ];
                }
            }
            // put in log files
            foreach($this->_logs as $log) {
                if($log->condition($this->_method, $url, $data, $auth_endpoint)) {
                    // call log
                    $log->log($this->_method, $url, [
                        'data' => $data,
                        'response' => $response->body,
                        'headers' => $response->headers
                    ]);
                }
            }
            if($response->headers['content-type']=='application/json') {
                return json_decode($response->body , true);
            }
            return $response->body;
        }

        /**
         * Gets profile information by username
         * Should only be used with GET
         *
         * @param string $username
         * @return R
         */
        public function profile(string $username) : R {
            $url = $this->build([ 'profile', $username ]);
            $resp = R::from($this->call($url));
            if($resp->status) {
                $resp->profile = Profile::from($resp->profile);
            }
            return $resp;
        }

        /**
         * Searches for profiles by username
         * Should only be used with GET
         *
         * @param string $query The query to search
         * @return R
         */
        public function profileSearch(string $query) : R {
            $url = $this->build([ 'profile', 'search', $query ]);
            $resp = R::from($this->call($url));
            if($resp->status) {
                $resp->profiles = array_map(function($p) {
                    return Profile::from($p);
                }, $resp->profiles);
            }
            return $resp;
        }

        /**
         * Searches for vectors by tag/title
         * Should only be used with GET
         *
         * @param string $query The query to search for
         * @return R
         */
        public function vectorSearch(string $query) : R {
            $url = $this->build([ 'vector', 'search', $query ]);
            $resp = R::from($this->call($url));
            if($resp->status) {
                $resp->vectors = array_map(function($v) {
                    return Vector::from($v);
                }, $resp->vectors);
            }
            return $resp;
        }

        /**
         * Searches for vectors by tag/title for the current user
         *
         * @param string $query The query to search for
         * @return R
         */
        public function userVectorSearch(string $query) : R {
            $url = $this->build([ 'user', 'vector', 'search', $query ]);
            $resp = R::from($this->call($url, [], true));
            if($resp->status) {
                $resp->vectors = array_map(function($v) {
                    return Vector::from($v);
                }, $resp->vectors);
            }
            return $resp;
        }

        /**
         * Gets or Patches a user
         *
         * @param array $data
         * @return array
         */
        public function user(array $data = []) : R {
            $url = $this->build([ 'user' ]);
            $resp = R::from($this->call($url, $data, true));
            if($resp->status && $this->_method==Vec2::GET) {
                $resp->user = User::from($resp->user);
            }
            return $resp;
        }

        /**
         * Fetches the publicly available vectors for a profile or the current user
         * if no username is passed, will try to fetch from current user
         *
         * @param string $username
         * @return R
         */
         public function vectors(string $username = '') : R {
            $url_build = [];
            if(!empty($username)) {
                $url_build = [ 'profile', $username, 'vectors' ];
            } else { $url_build = [ 'user', 'vectors' ]; }
            $url = $this->build($url_build);
            // url built, call API
            $resp = R::from($this->call($url, [], empty($username)));
            if($resp->status) {
                $resp->vectors = array_map(function($v) {
                    return Vector::from($v);
                }, $resp->vectors);
            }
            return $resp;
        }

        /**
         * Tries to GET vector information
         * Tries to POST one
         * Tries to PATCH one
         *
         * @param string|array $vector_or_data
         * @param array $data Data only used for PATCH
         * @return R
         */
        public function vector($vector_or_data, array $data = []) : R {
            $url_build = $this->_method==Vec2::GET ? [ 'vector' ] : [ 'user', 'vector' ];
            if(is_string($vector_or_data)) {
                $url_build[] = $vector_or_data;
            }
            $url = $this->build($url_build);
            // url built, send API request
            $resp = R::from($this->call($url, is_array($vector_or_data) ? $vector_or_data : $data, true));
            if($resp->status) {
                $resp->vector = Vector::from($resp->vector);
            }
            return $resp;
            /*// if array -> no raw data sent
            if(is_array($resp)) {
                return R::from($resp);
            }
            // raw data sent => vector found and downloaded
            $response = new R(true);
            $response->vector = $resp;
            return $response;*/
        }

        /**
         * Tries to GET download a vector
         */
        public function vectorDownload(string $vector) : R {
            $url = $this->build([ 'vector', 'download', $vector ]);
            // url built send API request
            $resp = $this->call($url, [], true);
            // if array -> no raw data sent
            if(is_array($resp)) {
                return R::from($resp);
            }
            // raw data sent -> vector found and downloaded
            $response = new R(true);
            $response->vector = $resp;
            return $response;
        }

        /**
         * Tries to GET the public image for the vector
         *
         * @param string $vector
         * @param array $data
         * @return R
         */
        public function vectorPublic(string $vector) : R {
            $url = $this->build([ 'vector', 'public', $vector ]);
            // url built send API request
            $resp = $this->call($url, [], true);
            // if array -> no raw data sent
            if(is_array($resp)) {
                return R::from($resp);
            }
            // raw data sent => vector found and downloaded
            $response = new R(true);
            $response->vector = $resp;
            return $response;
        }

        /**
         * Fetches the user tags
         *
         * @return R
         */
        public function tags() : R {
            $url = $this->build([ 'user', 'tags' ]);
            $resp = R::from($this->call($url, [], true));
            if($resp->status) {
                $resp->tags = array_map(function($t) {
                    return Tag::from($t);
                }, $resp->tags);
            }
            return $resp;
        }

        /**
         * For posting a new tag to the user table
         * Should be used with POST
         *
         * @param string $tag
         * @return R
         */
        public function tag(string $tag) : R {
            $url = $this->build([ 'user', 'tag' ]);
            $resp = R::from($this->call($url, [ 'tag' => $tag ], true));
            return $resp;
        }

        /**
         * GET customer info
         * POST new customer info
         * PATCH existing customer info
         *
         * @param array $data
         * @return R
         */
        public function customer(array $data = []) : R {
            $url = $this->build([ 'user', 'customer' ]);
            $resp = R::from($this->call($url, $data, true));
            if($resp->status && $this->_method==Vec2::GET) {
                $resp->customer = Customer::from($resp->customer);
            }
            return $resp;
        }

        /**
         * Tries to buy a vector
         * Should be used with POST
         *
         * @param string $vector
         * @return R
         */
        public function buy(string $vector) : R {
            $url = $this->build([ 'user', 'buy' ]);
            return R::from($this->call($url, [ 'vector' => $vector ], true));
        }

        /**
         * Gets the users' purchases
         *
         * @return R
         */
        public function purchases() : R {
            $url = $this->build([ 'user', 'purchases' ]);
            $resp = R::from($this->call($url, [], true));
            if($resp->status) {
                $resp->purchases = array_map(function($s) {
                    return Sale::from($s);
                }, $resp->purchases);
            }
            return $resp;
        }

        /**
         * Gets the users' sales
         *
         * @return R
         */
        public function sales() : R {
            $url = $this->build([ 'user', 'sales' ]);
            $resp = R::from($this->call($url, [], true));
            if($resp->status) {
                $resp->sales = array_map(function($s) {
                    return Sale::from($s);
                }, $resp->sales);
            }
            return $resp;
        }

        /**
         * Gets the user's payouts
         *
         * @return R
         */
        public function payouts() : R {
            $url = $this->build([ 'user', 'payouts' ]);
            $resp = R::from($this->call($url, [], true));
            if($resp->status) {
                $resp->payouts = array_map(function($p) {
                    return Payout::from($p);
                }, $resp->payouts);
            }
            return $resp;
        }

        /**
         * Requests a payout
         * Should be used with POST
         *
         * @return R
         */
        public function payout() : R {
            $url = $this->build([ 'user', 'payout' ]);
            return R::from($this->call($url, [], true));
        }

        /**
         * POST a like to a vector
         * DELETE a like to a vector
         *
         * @param string $vector
         * @return R
         */
        public function like(string $vector) : R {
            $data = [];
            $url_build = [ 'user', 'like' ];
            if($this->_method==Vec2::DELETE) {
                $url_build[] = $vector;
            } else if($this->_method==Vec2::POST) {
                $data['vector'] = $vector;
            }
            $url = $this->build($url_build);
            // url built, call API endpoint
            return R::from($this->call($url, $data, true));
        }
    }