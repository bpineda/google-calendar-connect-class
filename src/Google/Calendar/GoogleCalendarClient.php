<?php

namespace Google\Calendar;
require_once('vendor/google/apiclient/src/Google/autoload.php');
class GoogleCalendarClient extends \Google_Client
{

    const APPLICATION_NAME = 'Google Calendar Class API Test';
    const CREDENTIALS_PATH = './credentials/calendar-api-quickstart.json';
    const CLIENT_SECRET_PATH = 'client_secret.json';
    private $google_client;
    private $scopes;

    function __construct()
    {
        $this->google_client = parent::__construct();
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

    }

    public function getCalendarEvents()
    {

    }

    public function addCalendarEvent()
    {

    }

}