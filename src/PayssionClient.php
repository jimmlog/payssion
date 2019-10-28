<?php

namespace jimmlog\payssion;

use Exception;

/**
 * Client library for Payssion API.
 */
class PayssionClient
{
    /**
     * @const string
     */
    const VERSION = '1.3.0.160612';
    /**
     * @var string
     */
    protected $apiUrl;
    /**
     * @var string
     */
    protected $apiKey = ''; //your api key
    /**
     * @var string
     */
    protected $secretKey = ''; //your secret key

    /**
     * @var array
     */
    protected static $sigKeys = [
        'create' => [
            'api_key', 'pm_id', 'amount', 'currency', 'order_id', 'secret_key'
        ],
        'details' => [
            'api_key', 'transaction_id', 'order_id', 'secret_key'
        ]
    ];

    /**
     * @var array
     */
    protected $httpErrors = [
        400 => '400 Bad Request',
        401 => '401 Unauthorized',
        500 => '500 Internal Server Error',
        501 => '501 Not Implemented',
        502 => '502 Bad Gateway',
        503 => '503 Service Unavailable',
        504 => '504 Gateway Timeout',
    ];

    /**
     * @var bool
     */
    protected $isSuccess = false;

    /**
     * @var array
     */
    protected $allowedRequestMethods = [
        'get',
        'put',
        'post',
        'delete',
    ];

    /**
     * @var boolean
     */
    protected $sslVerify = true;

    /**
     * Constructor
     *
     * @param string $apiKey Payssion App api_key
     * @param string $secretKey Payssion App secret_key
     * @param bool $isLiveMode false if you use sandbox api_key and true for live mode
     * @throws Exception
     */
    public function __construct($apiKey, $secretKey, $isLiveMode = true)
    {
        $this->apiKey = $apiKey;
        $this->secretKey = $secretKey;

        $validateParams = [
            empty($this->apiKey) => 'api_key is not set!',
            empty($this->secretKey) => 'secret_key is not set!',
        ];
        $this->checkForErrors($validateParams);

        $this->setLiveMode($isLiveMode);
    }

    /**
     * Set LiveMode
     *
     * @param bool $isLiveMode
     */
    public function setLiveMode($isLiveMode)
    {
        if ($isLiveMode) {
            $this->apiUrl = 'https://www.payssion.com/api/v1/payment/';
        } else {
            $this->apiUrl = 'http://sandbox.payssion.com/api/v1/payment/';
        }
    }

    /**
     * Set Api URL
     *
     * @param string $url Api URL
     */
    public function setUrl($url)
    {
        $this->apiUrl = $url;
    }

    /**
     * Sets SSL verify
     *
     * @param bool $sslVerify SSL verify
     */
    public function setSSLVerify($sslVerify)
    {
        $this->sslVerify = $sslVerify;
    }

    /**
     * Request state getter
     *
     * @return bool
     */
    public function isSuccess()
    {
        return $this->isSuccess;
    }

    /**
     * create payment order
     *
     * @param array $params create Params
     * @return array
     * @throws Exception
     */
    public function create($params)
    {
        return $this->call(
            'create',
            'post',
            $params
        );
    }

    /**
     * get payment details
     *
     * @param array $params query Params
     * @return array
     * @throws Exception
     */
    public function getDetails($params)
    {
        return $this->call(
            'details',
            'post',
            $params
        );
    }

    /**
     * Method responsible for preparing, setting state and returning answer from rest server
     *
     * @param string $method
     * @param string $request
     * @param array $params
     * @return array
     * @throws Exception
     */
    protected function call($method, $request, $params)
    {
        $this->isSuccess = false;

        $validateParams = [
            false === is_string($method) => 'Method name must be string',
            false === $this->checkRequestMethod($request) => 'Not allowed request method type',
            true === empty($params) => 'params is null',
        ];

        $this->checkForErrors($validateParams);

        $params['api_key'] = $this->apiKey;
        $params['api_sig'] = $this->getSig($params, self::$sigKeys[$method]);

        $response = $this->pushData($method, $request, $params);

        $response = json_decode($response, true);

        if (isset($response['result_code']) && 200 == $response['result_code']) {
            $this->isSuccess = true;
        }

        return $response;
    }

    /**
     * Checking error mechanism
     *
     * @param array $params
     * @param array $sigKeys
     * @return string
     * @throws Exception
     */
    protected function getSig(&$params, $sigKeys)
    {
        $messages = [];
        foreach ($sigKeys as $key) {
            $messages[$key] = isset($params[$key]) ? $params[$key] : '';
        }
        $messages['secret_key'] = $this->secretKey;

        $msg = implode('|', $messages);
        $sig = md5($msg);
        return $sig;
    }

    /**
     * Checking error mechanism
     *
     * @param array $validateParams
     * @throws Exception
     */
    protected function checkForErrors(&$validateParams)
    {
        foreach ($validateParams as $key => $error) {
            if ($key) {
                throw new Exception($error, -1);
            }
        }
    }

    /**
     * Check if method is allowed
     *
     * @param string $methodType
     * @return bool
     */
    protected function checkRequestMethod($methodType)
    {
        $requestMethod = strtolower($methodType);

        if (in_array($requestMethod, $this->allowedRequestMethods)) {
            return true;
        }

        return false;
    }

    /**
     * Method responsible for pushing data to server
     *
     * @param string $method
     * @param string $methodType Only post
     * @param array|string $vars
     * @return string|bool
     * @throws Exception
     */
    protected function pushData($method, $methodType, $vars)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->apiUrl . $method);
        curl_setopt($ch, CURLOPT_POST, true);

        if (is_array($vars)) {
            $vars = http_build_query($vars, '', '&');
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);

        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeaders());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->sslVerify);

        $response = curl_exec($ch);

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (isset($this->httpErrors[$code])) {
            throw new Exception('Response Http Error - ' . $this->httpErrors[$code], $code);
        }

        $code = curl_errno($ch);
        if (0 < $code) {
            throw new Exception('Unable to connect to ' . $this->apiUrl . ' Error: ' . "$code :" . curl_error($ch), $code);
        }

        curl_close($ch);

        return $response;
    }

    protected function &getHeaders()
    {
        $langVersion = phpversion();
        $uName = php_uname();
        $ua = [
            'version' => self::VERSION,
            'lang' => 'php',
            'lang_version' => $langVersion,
            'publisher' => 'payssion',
            'uname' => $uName,
        ];
        $headers = [
            'X-Payssion-Client-User-Agent: ' . json_encode($ua),
            "User-Agent: Payssion/php/$langVersion/" . self::VERSION,
            'Content-Type: application/x-www-form-urlencoded',
        ];

        return $headers;
    }
}
