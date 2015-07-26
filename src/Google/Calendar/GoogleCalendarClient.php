<?php

namespace Google\Calendar;
require_once('vendor/google/apiclient/src/Google/autoload.php');
class GoogleCalendarClient extends \Google_Client
{

    const APPLICATION_NAME = 'Google Calendar API Quickstart';
    const CREDENTIALS_PATH = './credentials/calendar-api-quickstart.json';
    const CLIENT_SECRET_PATH = 'client_secret.json';
    private $google_client;
    private $scopes;

    function __construct()
    {
        $this->google_client = new Google_Client();
        $this->scopes = implode(' ', array( Google_Service_Calendar::CALENDAR_READONLY));
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