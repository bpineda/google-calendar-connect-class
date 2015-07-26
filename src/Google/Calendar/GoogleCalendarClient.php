<?php

namespace Google\Calendar;
require_once('vendor/google/apiclient/src/Google/autoload.php');
class GoogleCalendarClient extends \Google_Client
{

    const APPLICATION_NAME = 'Google Calendar Class API Test';
    const CREDENTIALS_PATH = './credentials/calendar-api-quickstart.json';
    const CLIENT_SECRET_PATH = 'client_secret.json';
    const CLIENT_ACCESS_TYPE = 'offline';
    private $google_client;
    private $scopes;
    private $error_message = '';

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
    }

    public function config()
    {
        $this->setApplicationName(self::APPLICATION_NAME);
        $this->setScopes($this->scopes);
        $this->setAuthConfigFile(self::CLIENT_SECRET_PATH);
        $this->setAccessType(self::CLIENT_ACCESS_TYPE);

        $credential_file = realpath(self::CREDENTIALS_PATH);

        if (file_exists($credential_file)) {
            $accessToken = file_get_contents($credential_file);
        } else {
            // Request authorization from the user.
            $authUrl = $this->createAuthUrl();
            $this->error_message = 'App not verified. Open the following url: <a href="' . $authUrl . '">Verify App</a>"';
            return false;

        }
        $this->setAccessToken($accessToken);

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

    public function saveCredentials($verification_code)
    {
        $authCode = $verification_code;

        // Exchange authorization code for an access token.
        $accessToken = $this->authenticate($authCode);

        // Store the credentials to disk.
        if(!file_exists(dirname($credential_file))) {
            mkdir(dirname($credential_file), 0700, true);
        }
        file_put_contents($credential_file, $accessToken);
        printf("Credentials saved to %s\n", $credential_file);
    }

    public function getCalendarEvents()
    {

    }

    public function addCalendarEvent()
    {

    }

}