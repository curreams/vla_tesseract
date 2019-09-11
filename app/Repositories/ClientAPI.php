<?php

namespace App\Repositories;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use Exception;

/**
 * Set a Repository for the Booking Models.
 *
 * @author Christian Arevalo, Sebastian Currea
 * @version 1.0.0
 */

const LOGIN_URL = '/api/auth/login';
const LOGOUT_URL = '/api/auth/logout';
class ClientAPI
{

    private $client, $email, $password, $remember_me;
    /**
     * Constructor. Initialize client and basic auth
     */
    function __construct()
    {
        $this->client = new Client(['base_uri' => env( 'CLIENT_BASE_URL' ), 
                                    'verify' => env( 'CLIENT_VERIFY_PATH', false ),
                                    'auth' => [env('CLIENT_DOMAIN').'\\'.env('CLIENT_USER'), env('CLIENT_PASSWORD')]
                                    ]);
        $this->user         = env( 'CLIENT_USER' );
        $this->password     = env( 'CLIENT_PASSWORD' );
        $this->domain       = env( 'CLIENT_DOMAIN' );
    }


    /**
     * Get request to API
     *
     * @param string $url Url to access through the method
     * @return array
     */
    public function get($url, $parameters)
    {
        try{
            $headers = [
                'content-type' => 'application/x-www-form-urlencoded',
                'APIKey' => env('CLIENT_KEY'),
            ];
            $response = $this->client->get(
                                        $url,
                                        [
                                            'headers' => $headers,
                                            'query'   => $parameters
                                            
                                        ]
                                    )->getBody();
            $data = json_decode($response);
            return $data;
        }catch (Exception $exception) {
            return $exception->getMessage();
        }
    }    

    /**
     * Post request to API
     *
     * @param array $form_params Form Information
     * @param array $tokens Tokens returned on login
     * @param string $url Url to access through the method
     * @return array
     */
    public function post($form_params, $tokens, $url)
    {
            $headers = [
                'content-type' => 'application/x-www-form-urlencoded',
                'Authorization' => $tokens['token_type'] . ' ' . $tokens['access_token'],
            ];
            $response = $this->client->post(
                                        $url,
                                        [
                                            'headers' => $headers,
                                            'form_params' => $form_params
                                        ]
                                    )->getBody();
            $data = json_decode($response);
            return $data;
    }
    /**
     * Patch request to API
     *
     * @param array $form_params Form Information
     * @param array $tokens Tokens returned on login
     * @param string $url Url to access through the method
     * @return void
     */
    public function patch($form_params, $tokens, $url)
    {
        $headers = [
            'content-type' => 'application/x-www-form-urlencoded',
            'Authorization' => $tokens['token_type'] . ' ' . $tokens['access_token'],
        ];
        $response = $this->client->patch(
                                        $url,
                                        [
                                            'headers' => $headers,
                                            'form_params' => $form_params
                                        ]
                                        )->getBody();
        $data = json_decode($response);
        return $data;

    }

    /**
     * Delete the requested entity
     *
     * @param array $tokens Tokens returned on login
     * @param string $url Url to access through the method
     * @return array
     */
    public function delete($tokens, $url)
    {
            $headers = [
                'content-type' => 'application/x-www-form-urlencoded',
                'Authorization' => $tokens['token_type'] . ' ' . $tokens['access_token'],
            ];
            $response = $this->client->delete(
                                        $url,
                                        [
                                            'headers' => $headers
                                        ]
                                    )->getBody();
            $data = json_decode($response);
            return $data;
    }

    /**
     * Login to get tokens
     *
     * @return array tokens
     */
    public function login()
    {
        $response = $this->client->post(
                                        LOGIN_URL,
                                        [
                                            RequestOptions::JSON => [
                                                                        "email" => $this->email,
                                                                        "password" => $this->password,
                                                                        "remember_me" => $this->remember_me
                                                                    ]
                                        ]
                                    )->getBody();
        $data = json_decode($response);
        return  [
                    'token_type' => $data->token_type,
                    'access_token' => $data->access_token
                ];

    }

    /**
     * Logout destroying current token/session
     *
     * @return array
     */
    public function logout()
    {
        try{
            $headers = [
                'content-type' => 'application/x-www-form-urlencoded',
                'Authorization' => $tokens['token_type'] . ' ' . $tokens['access_token'],
            ];
            $response = $this->client->get(
                                            LOGOUT_URL,
                                            [
                                                'headers' => $headers
                                            ]
                                        )->getBody();
            $data = json_decode($response);
            return  $data;
        }catch (Exception $exception) {
            return $exception->getMessage();
        }
    }
}