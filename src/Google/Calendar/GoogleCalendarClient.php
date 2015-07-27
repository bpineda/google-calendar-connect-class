<?php

namespace Google\Calendar;
require_once('vendor/google/apiclient/src/Google/autoload.php');
class GoogleCalendarClient extends \Google_Client
{

    const APPLICATION_NAME = 'Google Calendar Class API Test';
    const CREDENTIALS_PATH = './credentials/calendar-api-quickstart.json';
    const CLIENT_SECRET_PATH = 'client_secret.json';
    const CLIENT_ACCESS_TYPE = 'offline';
    const CALENDAR_ID = '';
    private $google_client;
    private $scopes;
    private $error_message = '';
    private $app_message = '';
    private $google_service_calendar;

    function __construct()
    {
        parent::__construct();
        /**
         * Google calendar service may have the options:
         * Google_Service_Calendar::CALENDAR_READONLY
         * if you want read only access
         * or
         * Google_Service_Calendar::CALENDAR
         * if you want read/write access to the calendar
         */

        $this->scopes = implode(' ', array( \Google_Service_Calendar::CALENDAR));
        $this->google_service_calendar = new \Google_Service_Calendar($this);
    }

    public function config($verification_code = null)
    {
        $this->setApplicationName(self::APPLICATION_NAME);
        $this->setScopes($this->scopes);
        $this->setAuthConfigFile(self::CLIENT_SECRET_PATH);
        $this->setAccessType(self::CLIENT_ACCESS_TYPE);

        $credential_file = realpath(self::CREDENTIALS_PATH);

        if (file_exists($credential_file)) {
            $access_token = file_get_contents($credential_file);
        } else {

            if(empty($verification_code))
            {
                $authUrl = $this->createAuthUrl();
                $this->error_message = 'App not verified. Open the following url: <a href="' . $authUrl . '">Verify App</a>"';
                return false;
            }

            $access_token = $this->verifyCode($verification_code);
            $this->saveCredentials($access_token);

        }

        //Set access token
        $this->setAccessToken($access_token);

        // Refresh the token if it's expired.
        if ($this->isAccessTokenExpired()) {
            $this->refreshToken($this->getRefreshToken());
            file_put_contents($credential_file, $this->getAccessToken());
        }
    }

    public function getErrorMessage()
    {
        return $this->error_message;
    }

    public function getAppMessage()
    {
        return $this->app_message;
    }

    private function verifyCode($verification_code)
    {
        $authCode = $verification_code;

        // Exchange authorization code for an access token.
        return $this->authenticate($authCode);

    }

    private function saveCredentials($access_token)
    {

        // Store the credentials to disk.
        $credential_file = self::CREDENTIALS_PATH;
        if(!file_exists(dirname($credential_file))) {
            mkdir(dirname($credential_file), 0700, true);
        }
        file_put_contents($credential_file, $access_token);
        $this->app_message = 'Credentials saved to ' . $credential_file;
        return true;
    }

    private function verifyCalendarId()
    {
        if('' != self::CALENDAR_ID)
        {
            return true;
        }
        $this->error_message = 'Calendar ID not set. Please set the CALENDAR_ID constant';
        return false;
    }

    public function getCalendarEvents()
    {
        if(!$this->verifyCalendarId())
        {
            echo $this->getErrorMessage();
            return false;
        }
    }

    public function addCalendarEvent()
    {

    }

}
