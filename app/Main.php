<?php

include_once 'Utils/Auth.php';
include_once 'Utils/Request.php';

use App\Auth;
use App\Request;

class Main
{
    private static $headers = [];
    private static $profileUrl = '/v1/users/me';
    private static $statics = '/v1/users/me/statistic';
    private static $username;
    private static $password;
    private static $cookie;

    /**
     * Main constructor.
     * It will also check if the DEBUG parameter is passed through the ENV
     * If is passed and true then it will set the curl option [CURLOPT_VERBOSE] to true
     * so that we can see the request and response meta information in console
     */
    public function __construct()
    {
        if (isset($_ENV['DEBUG'])) {
            Request::$debug = $_ENV['DEBUG'];
        }
    }

    /**
     * This method can be called to login the user in the system
     */
    private function attemptLogin()
    {
        self::$cookie = Auth::init(self::$username, self::$password);
        if (!Request::$useCookieJar) {
            self::$headers[] = 'Cookie: '.implode('; ', self::$cookie);
        }
    }


    /**
     * This method can be called to initialize the scrapper
     *
     * @param null $username
     * @param null $password
     *
     * @return array
     */
    public function init($username, $password)
    {
        /*
         * Setting the static variable username & password
         * so that login can be attempted from anywhere in the class without passing it.
         */
        self::$username = $username;
        self::$password = $password;

        $this->attemptLogin();

        $data = [];
        $userInfo = $this->getLoggedInUserInfo();
        if (array_key_exists('accountType', $userInfo)) {
            $data['accountType'] = $userInfo['accountType'];
        }

        $profile = $this->getStats();
        if (array_key_exists('dailyTraffic', $profile)) {
            if (array_key_exists('total', $profile['dailyTraffic'])) {
                $data['Traffic left today for viewing/downloading'] = $profile['dailyTraffic']['total'];
            }
            if (array_key_exists('used', $profile['dailyTraffic'])) {
                $data['Used traffic today'] = $profile['dailyTraffic']['used'];
            }
        }
        $data['Cookies'] = self::$cookie;
        return $data;
    }


    /**
     * This method returns the profile stats
     * Which will contains the information like total traffic today and used traffic today etc.
     *
     * @return mixed
     */
    private function getStats()
    {
        $response = Request::get(self::$statics, self::$headers);
        if ($response->getStatusCode() !== 200) {
            $this->attemptLogin();
            $response = Request::get(self::$statics, self::$headers);
        }
        return json_decode($response->getResponse(), true);
    }


    /**
     * This methods returns the account type of the user
     * Which will contain the information like user account type and other user information.
     *
     * @return mixed
     */
    private function getLoggedInUserInfo()
    {
        $response = Request::get(self::$profileUrl, self::$headers);
        if ($response->getStatusCode() !== 200) {
            $this->attemptLogin();
            $response = Request::get(self::$profileUrl, self::$headers);
        }
        return json_decode($response->getResponse(), true);
    }
}

// Set the username password here
$username = null;
$password = null;

// You can pass username and password using the docker env
if (isset($_ENV['USERNAME'])) {
    $username = $_ENV['USERNAME'];
}

if (isset($_ENV['PASSWORD'])) {
    $password = $_ENV['PASSWORD'];
}


$app = new Main();
$data = $app->init($username, $password);

/*
 * Above $data variable contains all the information request in the assignment
 * Below code will just print all the information as key-value pair
 * It will also print the cookie used in the request
 */
echo json_encode($data);
