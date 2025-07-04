<?php

namespace PerfexApiSdk\Controllers;

use PerfexApiSdk\Controllers\API_Controller;

use PerfexApiSdk\Libraries\Authorization_Token;

defined('BASEPATH') or exit('No direct script access allowed');

class Login extends API_Controller {
    protected $authorization_token;

    public function __construct() {
        parent::__construct();
        
        $this->authorization_token = new Authorization_Token();
    }

    public function login_api() {
        header("Access-Control-Allow-Origin: *");
        // API Configuration
        $this->_apiConfig(['methods' => ['POST'], ]);
        // you user authentication code will go here, you can compare the user with the database or whatever
        $payload = ['id' => "Your User's ID", 'other' => "Some other data"];
        // Load Authorization Library or Load in autoload config file
        // generate a token
        $token = $this->authorization_token->generateToken($payload);
        // return data
        $this->api_return(['status' => true, "result" => ['token' => $token, ], ], 200);
    }

    /**
     * view method
     *
     * @link [api/user/view]
     * @method POST
     * @return Response|void
     */
    public function view() {
        header("Access-Control-Allow-Origin: *");
        // API Configuration [Return Array: User Token Data]
        $user_data = $this->_apiConfig(['methods' => ['POST'], 'requireAuthorization' => true, ]);
        // return data
        $this->api_return(['status' => true, "result" => ['user_data' => $user_data['token_data']], ], 200);
    }
    
    public function api_key() {
        $this->_APIConfig(['methods' => ['POST'], 'key' => ['header', 'Set API Key'], ]);
    }
}
