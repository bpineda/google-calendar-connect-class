<?php

namespace Google\Calendar;
require_once 'vendor/google/apiclient/src/Google/autoload.php';

/**
 * Class GoogleCalendarClient
 * @category GoogleCalendar
 * @package Google\Calendar
 * @author Bernardo Pineda <_@bernardopineda.mx>
 * @license http://opensource.org/licenses/MIT MIT
 * @link https://github.com/bpineda/google-calendar-connect-class
 */
class GoogleCalendarClient extends \Google_Client
{

    const APPLICATION_NAME = 'Google Calendar Class API Test';
    const CREDENTIALS_PATH = './credentials/calendar-api-access-token.json';
    const CLIENT_SECRET_PATH = 'client_secret.json';
    const CLIENT_ACCESS_TYPE = 'offline';
    const CALENDAR_ID
        = 'bernardopineda.mx_ro49eep2p25rrhe2t0k4jflros@group.calendar.google.com';
    const TIME_ZONE = 'America/Mexico_City';
    private $_scopes;
    private $_error_message = '';
    private $_app_message = '';
    private $_google_service_calendar;
    private $_event_attribute_names = array( 'summary',
                                            'location',
                                            'description',
                                            'start',
                                            'end',
                                            'attendees',
                                            'reminders'
    );
    private $_date_attribute_names = array(  'start',
                                            'end'
    );
    private $_event_attributes;
    private $_event_attributes_;

    /**
     * Constructor for the class. We call the parent constructor, set
     * scopes array and service calendar instance that we will use
     */
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

        $this->_scopes = implode(' ', array( \Google_Service_Calendar::CALENDAR));
        $this->_google_service_calendar = new \Google_Service_Calendar($this);
    }

    /**
     * Class configurator
     * @param null $verification_code Verification code we will use
     * to create our authentication credentials
     * @return bool
     */
    public function config($verification_code = null)
    {
        $this->setApplicationName(self::APPLICATION_NAME);
        $this->setScopes($this->_scopes);
        $this->setAuthConfigFile(self::CLIENT_SECRET_PATH);
        $this->setAccessType(self::CLIENT_ACCESS_TYPE);

        $credential_file = realpath(self::CREDENTIALS_PATH);

        if (file_exists($credential_file)) {
            $access_token = file_get_contents($credential_file);
        } else {

            if (empty($verification_code)) {
                $authUrl = $this->createAuthUrl();
                $this->_error_message
                    = 'App not verified. Open the following url: <a href="' .
                    $authUrl . '">Verify App</a>"';
                return false;
            }

            $access_token = $this->_verifyCode($verification_code);
            $this->_saveCredentials($access_token);

        }

        $this->setAccessToken($access_token);

        // Refresh the token if it's expired.
        if ($this->isAccessTokenExpired()) {
            $this->refreshToken($this->getRefreshToken());
            file_put_contents($credential_file, $this->getAccessToken());
        }
    }

    /**
     * Get error message string
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->_error_message;
    }

    /**
     * Get application message string
     * @return string
     */
    public function getAppMessage()
    {
        return $this->_app_message;
    }

    /**
     * Verify our code with google
     * @param string $verification_code verification_code obtained from the URL
     * @return mixed
     */
    private function _verifyCode($verification_code)
    {
        $authCode = $verification_code;

        // Exchange authorization code for an access token.
        return $this->authenticate($authCode);

    }

    /**
     * Save json credentials to a file
     * @param string $access_token json string with the access token
     * @return bool
     */
    private function _saveCredentials($access_token)
    {

        // Store the credentials to disk.
        $credential_file = self::CREDENTIALS_PATH;
        if (!file_exists(dirname($credential_file))) {
            mkdir(dirname($credential_file), 0700, true);
        }
        file_put_contents($credential_file, $access_token);
        $this->_app_message = 'Credentials saved to ' . $credential_file;
        return true;
    }

    /**
     * Verify that the calendar id is not an emtpy string
     * @return bool
     */
    private function _verifyCalendarId()
    {
        if ('' != self::CALENDAR_ID) {
            return true;
        }
        $this->_error_message
            = 'Calendar ID not set. Please set the CALENDAR_ID constant';
        return false;
    }

    /**
     * Get our selected calendar events as an array or false if it's empty
     * @return array|bool
     */
    public function getCalendarEvents()
    {
        if (!$this->_verifyCalendarId()) {
            echo $this->getErrorMessage();
            return false;
        }

        $optParams = array(
            'maxResults' => 10,
            'orderBy' => 'startTime',
            'singleEvents' => TRUE,
            'timeMin' => date('c'),
        );

        $results = $this->_google_service_calendar
            ->events->listEvents(self::CALENDAR_ID, $optParams);

        if (count($results->getItems()) == 0) {
            return array();
        }

        return $this->_createEventsArray($results);

    }

    /**
     * Creates the events array
     * @param array $events_result events result array
     * @return array
     */
    private function _createEventsArray($events_result)
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
     * We add an event to the calendar.
     * The array that we pass to the method should be like this:
     * array(
     *   'summary' => 'Event name',
     *   'location' => 'Event address',
     *   'description' => 'Event description',
     *   'start' => '2015-05-28T09:00:00',
     *   'end' => '2015-05-28T17:00:00-07:00',
     *   'attendees' => array(
     *                           array('email' => 'attendee1@example.com'),
     *                           array('email' => 'attendee2@example.com'),
     *   ),
     *   'reminders' => array(
     *       'useDefault' => FALSE,
     *       'overrides' => array(
     *           array('method' => 'email', 'minutes' => 24 * 60),
     *           array('method' => 'popup', 'minutes' => 10),
     *   ),
     *   ),
     *   )
     * @param array $event_attributes Event attributes array
     * @return Google_Service_Calendar_Event
     */
    public function addCalendarEvent($event_attributes)
    {

        $this->_event_attributes = $event_attributes;
        $this->_discardEventAttributes();
        $event = new \Google_Service_Calendar_Event($this->_event_attributes_);

        $event = $this->_google_service_calendar
            ->events->insert(self::CALENDAR_ID, $event);
        return $event;

    }

    /**
     * If an attribute is in the array but it's not useful, we discard it
     * @return null
     */
    private function _discardEventAttributes()
    {
        $this->_event_attributes_ = [];

        foreach ( $this->_event_attributes as $attribute_name => $attribute_value ) {
            if (in_array($attribute_name, $this->_event_attribute_names)) {
                $this->_event_attributes_[$attribute_name]
                    = $this->_setDateAttributes($attribute_name, $attribute_value);
            }
        }

    }

    /**
     * We set the date attributes as an array
     * so the user only has to input the date_time value
     * @param string $attribute_name string attribute names
     * @param mixed $attribute_value attribute_value 
     * @return array
     */
    private function _setDateAttributes($attribute_name, $attribute_value)
    {
        if (in_array($attribute_name, $this->_date_attribute_names)) {
            return array(
                'dateTime' => $attribute_value,
                'timeZone' => self::TIME_ZONE,
            );
        }

        return $attribute_value;
    }

}
