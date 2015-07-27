<?php

namespace Google\Calendar;
require_once('vendor/google/apiclient/src/Google/autoload.php');
class GoogleCalendarClient extends \Google_Client
{

    const APPLICATION_NAME = 'Google Calendar Class API Test';
    const CREDENTIALS_PATH = './credentials/calendar-api-access-token.json';
    const CLIENT_SECRET_PATH = 'client_secret.json';
    const CLIENT_ACCESS_TYPE = 'offline';
    const CALENDAR_ID = '';
    const TIME_ZONE = 'America/Mexico_City';
    private $google_client;
    private $scopes;
    private $error_message = '';
    private $app_message = '';
    private $google_service_calendar;
    private $event_attribute_names = array('summary','location','description','start','end','attendees','reminders');
    private $date_attribute_names = array('start','end');
    private $event_attributes;
    private $event_attributes_;

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

        $optParams = array(
            'maxResults' => 10,
            'orderBy' => 'startTime',
            'singleEvents' => TRUE,
            'timeMin' => date('c'),
        );

        $results = $this->google_service_calendar->events->listEvents(self::CALENDAR_ID, $optParams);

        if (count($results->getItems()) == 0) {
            return array();
        }

        return $this->createEventsArray($results);

    }

    private function createEventsArray($events_result)
    {
        $events_array = [];

        foreach ($events_result->getItems() as $event) {
            $start = $event->start->dateTime;
            $end = $event->end->dateTime;
            if (empty($start)) {
                $start = $event->start->date;
            }
            if (empty($end)) {
                $end = $event->end->date;
            }
            $events_array[] = [ 'start' => $start,
                                'end' => $end,
                                'summary' => $event->getSummary(),
                                'location' => $event->getLocation(),
                                'description' => $event->getDescription(),
                                'attendees' => $event->getAttendees(),
            ];
        }
        return $events_array;
    }

    /**
     * We add an event to the calendar. The array that we pass to the method should be like this:
     * array(
        'summary' => 'Event name',
        'location' => 'Event address',
        'description' => 'Event description',
        'start' => '2015-05-28T09:00:00',
        'end' => '2015-05-28T17:00:00-07:00',
        'attendees' => array(
                                array('email' => 'attendee1@example.com'),
                                array('email' => 'attendee2@example.com'),
        ),
        'reminders' => array(
            'useDefault' => FALSE,
            'overrides' => array(
                array('method' => 'email', 'minutes' => 24 * 60),
                array('method' => 'popup', 'minutes' => 10),
        ),
        ),
        )
     * @param $event_attributes
     * @return Google_Service_Calendar_Event
     */
    public function addCalendarEvent($event_attributes)
    {

        $this->event_attributes = $event_attributes;
        $this->discardEventAttributes();
        $event = new Google_Service_Calendar_Event($this->event_attributes_);

        $event = $this->google_service_calendar->events->insert(self::CALENDAR_ID, $event);
        return $event;

    }

    private function discardEventAttributes()
    {
        $this->event_attributes_ = [];

        foreach( $this->event_attributes as $attribute_name => $attribute_value )
        {
            if(in_array($attribute_name,$this->event_attribute_names)){
                $this->event_attributes_[$attribute_name] = $this->setDateAttributes($attribute_name,$attribute_value);
            }
        }

    }

    private function setDateAttributes($attribute_name, $attribute_value)
    {
        if(in_array($attribute_name,$this->date_attribute_names))
        {
            return array(
                'dateTime' => $attribute_value,
                'timeZone' => self::TIME_ZONE,
            );
        }

        return $attribute_value;
    }

}
