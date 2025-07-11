<?php

namespace PerfexApiSdk\Libraries;

include_once __DIR__ . '/../third-party/firebase-jwt/JWTExceptionWithPayloadInterface.php';
include_once __DIR__ . '/../third-party/firebase-jwt/BeforeValidException.php';
include_once __DIR__ . '/../third-party/firebase-jwt/ExpiredException.php';
include_once __DIR__ . '/../third-party/firebase-jwt/SignatureInvalidException.php';
include_once __DIR__ . '/../third-party/firebase-jwt/JWT.php';
include_once __DIR__ . '/../third-party/firebase-jwt/Key.php';

use Firebase\JWT\JWT as api_JWT;
use Firebase\JWT\Key as api_Key;

defined('BASEPATH') OR exit('No direct script access allowed');

#[\AllowDynamicProperties]
class Authorization_Token 
{
    /**
     * Token Key
     */
    protected $token_key;

    /**
     * Token algorithm
     */
    protected $token_algorithm;

    /**
     * Token Request Header Name
     */
    protected $token_header;

    /**
     * Token Expire Time
     * ------------------
     * (1 day) : 60 * 60 * 24 = 86400
     * (1 hour) : 60 * 60 = 3600
     */
    protected $token_expire_time = 315569260; 

    public function __construct()
	{
        $this->CI =& get_instance();

        $config = array();
        include (__DIR__ . "/../config/jwt.php");
        foreach ($config AS $key => $value) {
            if ($key == 'jwt_key') {
                $this->token_key           = $value;
            } else if ($key == 'jwt_algorithm') {
                $this->token_algorithm     = $value;
            } else if ($key == 'token_header') {
                $this->token_header        = $value;
            } else if ($key == 'token_expire_time') {
                $this->token_expire_time        = $value;
            }
        }
    }

    /**
     * Generate Token
     * @param: {array} data
     */
    public function generateToken($data = null)
    {
        if ($data AND is_array($data))
        {
            // add api time key in user array()
            $data['API_TIME'] = time();

            try {
                return api_JWT::encode($data, $this->token_key, $this->token_algorithm);
            }
            catch(Exception $e) {
                return 'Message: ' .$e->getMessage();
            }
        } else {
            return "Token Data Undefined!";
        }
    }

    public function get_token()
    {
        /**
         * Request All Headers
         */
        $headers = $this->CI->input->request_headers();
        
        /**
         * Authorization Header Exists
         */
        return $this->token($headers);
    }
    
    /**
     * Validate Token with Header
     * @return : user informations
     */
    public function validateToken()
    {
        /**
         * Request All Headers
         */
        $headers = $this->CI->input->request_headers();
        
        /**
         * Authorization Header Exists
         */
        $token_data = $this->tokenIsExist($headers);
        if ($token_data['status'] === TRUE)
        {
            try
            {
                /**
                 * Token Decode
                 */
                try {
                    $token_decode = api_JWT::decode($token_data['token'], new api_Key($this->token_key, $this->token_algorithm));
                }
                catch(Exception $e) {
                    return ['status' => FALSE, 'message' => $e->getMessage()];
                }

                if (!empty($token_decode) AND is_object($token_decode))
                {
                    // Check Token API Time [API_TIME]
                    if (empty($token_decode->API_TIME OR !is_numeric($token_decode->API_TIME))) {
                        
                        return ['status' => FALSE, 'message' => 'Token Time Not Define!'];
                    }
                    else
                    {
                        /**
                         * Check Token Time Valid 
                         */
                        $time_difference = strtotime('now') - $token_decode->API_TIME;
                        if ( $time_difference >= $this->token_expire_time )
                        {
                            return ['status' => FALSE, 'message' => 'Token Time Expire.'];

                        } else
                        {
                            /**
                             * All Validation False Return Data
                             */
                            return ['status' => TRUE, 'data' => $token_decode];
                        }
                    }
                    
                } else {
                    return ['status' => FALSE, 'message' => 'Forbidden'];
                }
            }
            catch(Exception $e) {
                return ['status' => FALSE, 'message' => $e->getMessage()];
            }
        }else
        {
            // Authorization Header Not Found!
            return ['status' => FALSE, 'message' => $token_data['message'] ];
        }
    }

    /**
     * Token Header Check
     * @param: request headers
     */
    private function tokenIsExist($headers)
    {
        if (!empty($headers) AND is_array($headers)) {
            foreach ($headers as $header_name => $header_value) {
                if (strtolower(trim($header_name)) == strtolower(trim($this->token_header)))
                    return ['status' => TRUE, 'token' => $header_value];
            }
        }
        return ['status' => FALSE, 'message' => 'Token is not defined.'];
    }

    private function token($headers)
    {
        if (!empty($headers) AND is_array($headers)) {
            foreach ($headers as $header_name => $header_value) {
                if (strtolower(trim($header_name)) == strtolower(trim($this->token_header)))
                    return $header_value;
            }
        }
        return 'Token is not defined.';
    }
}