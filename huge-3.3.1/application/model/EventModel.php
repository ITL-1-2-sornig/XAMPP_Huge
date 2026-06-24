<?php

/**
 * Handles all data manipulation of the admin part
 */
class EventModel
{
    /**
     * Creates a new Event hosted by the logged in user
     *
     * @param $eventName
     * @param $eventDate
     * @param $eventDescription
     * @param $participantLimit
     * 
     * @return int new Event ID
     */
    public static function createEvent($eventName, $eventDate, $eventDescription, $participantLimit)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $query = $database->prepare("INSERT INTO events (name, description, date, participant_limit, user_creator)
        VALUES (:name, :description, :date, :participant_limit, :user_creator)");

        $query->execute(array(
            ":name"                 => $eventName,
            ":description"          => $eventDescription,
            ":date"                 => $eventDate,
            ":participant_limit"    => $participantLimit,
            ":user_creator"         => Session::get("user_id")
        ));
        if($query->rowCount()==0){
            Session::add('feedback_negative', Text::get('FEEDBACK_EVENT_CREATED_FAILURE'));
            return 0;
        }
        else{
            Session::add('feedback_positive', Text::get('FEEDBACK_EVENT_CREATED_SUCCESS'));
            return $database->lastInsertId();
        }
            
    }

    /**
     * Returns wether the event belongs to the logged in user
     *
     * @param $eventID
     * 
     * @return bool
     */
    public static function eventBelongsToLoggedInUser($eventID)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $query = $database->prepare("SELECT user_creator FROM events WHERE ID=:eventID");
        $query->execute(array(
            ":eventID" => $eventID,
        ));

        return $query->fetch()->user_creator == Session::get("user_id");

    }
    /**
     * Edit Event
     *
     * @param $eventID
     * @param $eventName
     * @param $eventDate
     * @param $eventDescription
     * @param $participantLimit
     */
    public static function editEvent($eventID, $eventName, $eventDate, $eventDescription, $participantLimit)
    {
        $participants = EventModel::getEventParticipants($eventID);
        //If a participant-Limit is set and it is lower than the number of accepted reservation, return an error
        if($participantLimit && $participants>$participantLimit){
            Session::add('feedback_negative', Text::get('FEEDBACK_EVENT_EDIT_PARTICIPATION_LIMIT_TO_LOW'));
            return;
        }

        $database = DatabaseFactory::getFactory()->getConnection();
        $query = $database->prepare("UPDATE events
        Set name=:name, description=:description, date=:date, participant_limit=:participant_limit
        WHERE ID=:eventID AND user_creator=:userID");

        $query->execute(array(
            ":name"                 => $eventName,
            ":description"          => $eventDescription,
            ":date"                 => $eventDate,
            ":participant_limit"    => $participantLimit,
            ":eventID"              => $eventID,
            ":userID"               => Session::get("user_id"),
        ));
        if($query->rowCount()==0)
            Session::add('feedback_negative', Text::get('FEEDBACK_EVENT_EDITED_FAILURE'));
        else
            Session::add('feedback_positive', Text::get('FEEDBACK_EVENT_EDITED_SUCCESS'));
    }

    /**
     * Delete Event as long as it belongs to the logged in user
     *
     * @param $eventID
     */
    public static function deleteEvent($eventID)
    {
        if(!EventModel::eventBelongsToLoggedInUser($eventID)){
            Session::add('feedback_negative', Text::get('FEEDBACK_EVENT_DELETED_NOT_CREATOR'));
            return;
        }
        $database = DatabaseFactory::getFactory()->getConnection();
        $query = $database->prepare("DELETE FROM events
        WHERE ID=:eventID AND user_creator=:user_creator");

        $query->execute(array(
            ":eventID"              => $eventID,
            ":user_creator"         => Session::get("user_id"),
        ));

        if($query->rowCount()==0)
            Session::add('feedback_negative', Text::get('FEEDBACK_EVENT_DELETED_FAILURE'));
        else
            Session::add('feedback_positive', Text::get('FEEDBACK_EVENT_DELETED_SUCCESS'));
    }

    /**
     * Get all data for specific event
     *
     * @param $eventID
     * 
     * @return stdClass event
     */
    public static function getEvent($eventID)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $query = $database->prepare("SELECT
            e.ID AS ID,
            e.name AS name,
            e.description AS description,
            e.date AS date,
            e.participant_limit AS `limit`,
            e.user_creator AS creator,
            COUNT(r.code) AS reserved
            FROM events AS e LEFT OUTER JOIN reservations AS r
            ON e.ID = r.event && r.confirmed = TRUE
            WHERE e.ID = :eventID");
        
        $query->execute(array(
            ":eventID" => $eventID
        ));

        if($query->rowcount()==0){
            Session::add('feedback_negative', Text::get('FEEDBACK_EVENT_DOESNT_EXIST'));
            return null;
        }

        $result = $query->fetch();
        array_walk_recursive($result, 'Filter::XSSFilter');
        $result->taken = EventModel::getEventParticipants($eventID);

        return $result;
    }

    /**
     * Get all data for events in a timespan
     *
     * @param $startDate
     * @param $endDate
     * 
     * @return array events
     */
    public static function GetEventsInBetween($startDate, $endDate)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $query = $database->prepare("SELECT
            e.ID AS ID,
            e.name AS name,
            e.description AS description,
            e.date AS date,
            e.participant_limit AS `limit`,
            e.user_creator AS creator,
            COUNT(r.code) AS reserved
            FROM events AS e LEFT OUTER JOIN reservations AS r
            ON e.ID = r.event && r.confirmed = TRUE
            WHERE e.date BETWEEN :startDate AND :endDate
            GROUP BY ID");
        
        $query->execute(array(
            ":startDate" => $startDate,
            ":endDate" => $endDate
        ));

        $events = array();

        foreach($query->fetchAll() as $event){
            array_walk_recursive($event, 'Filter::XSSFilter');

            $events[$event->ID] = new stdClass();
            $events[$event->ID]->ID = $event->ID;
            $events[$event->ID]->name = $event->name;
            $events[$event->ID]->description = $event->description;
            $events[$event->ID]->date = $event->date;
            $events[$event->ID]->limit = $event->limit;
            $events[$event->ID]->creator = $event->creator;
            $events[$event->ID]->reserved = $event->reserved;
        }

        return $events;
    }

    /**
     * Get all days in a month with an array of events on that day
     *
     * @param $year
     * @param $month
     * 
     * @return array with array of events and array of days
     */
    public static function GetDaysWithEvents($year, $month){
        //number of days in month
        $lastDay = cal_days_in_month(CAL_GREGORIAN,$month,$year);
        //Weekday of the first day in the month (0=mon, 6=sun)
        $firstWeekDay = (jddayofweek((gregoriantojd($month, 1, $year))) + 6)%7;
        $start=$year."-".$month."-1";
        $end=$year."-".$month."-".$lastDay;
        $events = EventModel::GetEventsInBetween($start, $end);
        $days = array();
        foreach(range(1,$lastDay) as $day){
            $days[$day] = new stdClass();
            $days[$day]->day = $day;
            $days[$day]->events = array();
            $days[$day]->wDay = ($firstWeekDay +6 + $day)%7; 
        }
        foreach($events as $event){
            $day = (int)explode("-", $event->date)[2];
            array_push($days[$day]->events, $event);
        }

        return $days;
    }

    /**
     * Counts the number of confirmed reservations for an Event and returns that number
     *
     * @param $eventID
     * 
     * @return int amount
     */
    public static function getEventParticipants($eventID){
        $database = DatabaseFactory::getFactory()->getConnection();
        $query = $database->prepare("SELECT COUNT(ID) AS amount
        FROM reservations WHERE event=:eventID AND confirmed=TRUE");
        $query->execute(array(
            ":eventID"=>$eventID
        ));
        return $query->fetch()->amount;
    }

    /**
     * Returns all registrations for an event with the name of the user who made it
     *
     * @param $eventID
     * 
     * @return array reservations
     */
    public static function getEventReservations($eventID)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $query = $database->prepare("SELECT r.ID AS reservationID,
        r.user_participant AS userID,
        r.confirmed AS confirmed,
        r.code AS code,
        u.user_name AS user_name
        FROM reservations AS r
        JOIN users AS u
        ON u.user_id = r.user_participant
        WHERE r.event=:eventID");

        $query->execute(array(
            ":eventID" => $eventID
        ));

        $reservations = array();

        foreach($query->fetchAll() as $reservation){
            $reservations[$reservation->reservationID] = new stdClass();
            $reservations[$reservation->reservationID]->reservationID = $reservation->reservationID;
            $reservations[$reservation->reservationID]->userID = $reservation->userID;
            $reservations[$reservation->reservationID]->confirmed = $reservation->confirmed;
            $reservations[$reservation->reservationID]->code = $reservation->code;
            $reservations[$reservation->reservationID]->user_name = $reservation->user_name;
        }
        
        return $reservations;
    }

    /**
     * Returns registration for an event by the logged in user, if one exists
     *
     * @param $eventID
     * 
     * @return stdClass reservation
     */
    public static function getUserEventReservation($eventID)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $query = $database->prepare("SELECT ID AS reservationID,
        confirmed,
        code
        FROM reservations
        WHERE event=:eventID AND user_participant=:userID");

        $query->execute(array(
            ":eventID"  => $eventID,
            ":userID"   => Session::get("user_id")
        ));
        
        return $query->fetch();
    }

    /**
     * Add Reservation for Event
     *
     * @param $eventID
     */
    public static function addReservation($eventID){
        $database = DatabaseFactory::getFactory()->getConnection();
        $participants = EventModel::getEventParticipants($eventID);
        $queryLimit = $database->prepare("SELECT participant_limit, user_creator FROM events WHERE ID=:eventID");
        $queryLimit->execute(array(
            ":eventID" => $eventID,
        ));
        $event = $queryLimit->fetch();
        $limit = $event->participant_limit;
        $creatorID = $event->user_creator;
        if($limit>0 && $participants>=$limit){
            Session::add('feedback_negative', Text::get('FEEDBACK_RESERVATION_PARTICIPATION_LIMIT_TO_LOW'));
            return;
        }
        if($creatorID == Session::get("user_id")){
            Session::add('feedback_negative', Text::get('FEEDBACK_OWN_EVENT_RESERVATION'));
            return;
        }

        $query = $database->prepare("INSERT INTO reservations (user_participant, event)
        VALUES (:user, :event)");
        $query->execute(array(
            ":user"     => Session::get("user_id"),
            ":event"    => $eventID
        ));
        Session::add('feedback_positive', Text::get('FEEDBACK_RESERVATION_SUCCESS'));
    }

    /**
     * Cancel logged in users Reservation for this Event
     *
     * @param $eventID
     */
    public static function cancelReservation($eventID){
        $database = DatabaseFactory::getFactory()->getConnection();
        $query = $database->prepare("DELETE FROM reservations
        WHERE event=:eventID AND user_participant=:userID");
        $query->execute(array(
            ":userID"   => Session::get("user_id"),
            ":eventID"  => $eventID
        ));

        if($query->rowCount()==0)
            Session::add('feedback_negative', Text::get('FEEDBACK_RESERVATION_DELETE_FAILURE'));
        else
            Session::add('feedback_positive', Text::get('FEEDBACK_RESERVATION_DELETE_SUCCESS'));
    }

    /**
     * Accept Reservation for Event hosted by logged in user
     *
     * @param $reservationID
     */
    public static function acceptReservation($reservationID, $eventID){
        $database = DatabaseFactory::getFactory()->getConnection();
        $participants = EventModel::getEventParticipants($eventID);
        $queryLimit = $database->prepare("SELECT e.participant_limit AS `limit`, e.user_creator AS creator
        FROM events AS e
        JOIN reservations AS r
        ON e.ID=r.event
        WHERE r.ID=:reservationID");
        $queryLimit->execute(array(
            ":reservationID" => $reservationID,
        ));
        $event = $queryLimit->fetch();
        $limit = $event->limit;
        $creatorID = $event->creator;
        if($limit>0 && $limit - $participants < 1){
            Session::add('feedback_negative', Text::get('FEEDBACK_CONFIRM_RESERVATION_PARTICIPATION_LIMIT_TO_LOW'));
            return;
        }
        if($creatorID != Session::get("user_id")){
            Session::add('feedback_negative', Text::get('FEEDBACK_CONFIRM_RESERVATION_NOT_CREATOR'));
            return;
        }

        EventModel::setReservationConfirmed($reservationID);

        Session::add('feedback_positive', Text::get('FEEDBACK_RESERVATION_CONFIRMATION_SUCCESS'));
    }

    /**
     * Remove the Accepted Status from Reservation for Event hosted by logged in user
     *
     * @param $reservationID
     */
    public static function unacceptReservation($reservationID, $eventID){
        $database = DatabaseFactory::getFactory()->getConnection();
        $participants = EventModel::getEventParticipants($eventID);
        $queryLimit = $database->prepare("SELECT e.user_creator AS creator
        FROM events AS e
        JOIN reservations AS r 
        ON e.ID=r.event
        WHERE r.ID=:reservationID");
        $queryLimit->execute(array(
            ":reservationID" => $reservationID,
        ));
        $event = $queryLimit->fetch();
        $creatorID = $event->creator;
        if($creatorID != Session::get("user_id")){
            Session::add('feedback_negative', Text::get('FEEDBACK_UNCONFIRM_RESERVATION_NOT_CREATOR'));
            return;
        }

        EventModel::setReservationNotConfirmed($reservationID);

        Session::add('feedback_positive', Text::get('FEEDBACK_RESERVATION_UNCONFIRM_SUCCESS'));
    }

    /**
     * Accept all registration in the list for the given event and unaccept all
     * other registrations, as long as the logged in user is the events host
     *
     * @param $registrationIDs
     * @param $eventID
     * 
     */
    public static function multiAcceptReservation($reservationIDs, $eventID){
        if(!EventModel::eventBelongsToLoggedInUser($eventID)){
            Session::add('feedback_negative', Text::get('FEEDBACK_CONFIRM_RESERVATION_NOT_CREATOR'));
            return;
        }    
        $database = DatabaseFactory::getFactory()->getConnection();
        $queryLimit = $database->prepare("SELECT participant_limit AS `limit` FROM events
            WHERE ID=:eventID");
        $queryLimit->execute(array(
            ":eventID" => $eventID,
        ));
        $limit = $queryLimit->fetch()->limit;
        if($limit>0 && $limit<count($reservationIDs)) {
            Session::add('feedback_negative', Text::get('FEEDBACK_CONFIRM_RESERVATION_PARTICIPATION_LIMIT_TO_LOW'));
            return;
        }
        $queryReservations = $database->prepare("SELECT ID FROM reservations
            WHERE event=:eventID");

        $queryReservations->execute(array(
            ":eventID" => $eventID,
        ));

        foreach($queryReservations->fetchALL() as $reservation){
            if(in_array($reservation->ID, $reservationIDs))
                EventModel::setReservationConfirmed($reservation->ID);
            else
                EventModel::setReservationNotConfirmed($reservation->ID);
        }
    }


    /**
     * Set Reservation to confirmed and create confirmation code
     *
     * @param $reservationID
     */
    public static function setReservationConfirmed($reservationID){
        $database = DatabaseFactory::getFactory()->getConnection();
        //Hash the id + salt and use the first 8 characters as the confirm-code
        $confirmCode = substr(md5($reservationID . "73havb2r"), 0, 8);
        //Confirmation Code only gets changed if there was no code before
        $query = $database->prepare("UPDATE reservations SET confirmed=TRUE,
            code=COALESCE(code, :code)
            WHERE ID=:reservationID");
        $query->execute(array(
            ":code" => $confirmCode,
            ":reservationID" => $reservationID
        )); 
    }

    /**
     * Set Reservation to not confirmed and remove confirmation code
     *
     * @param $reservationID
     */
    public static function setReservationNotConfirmed($reservationID){
        $database = DatabaseFactory::getFactory()->getConnection();
        $query = $database->prepare("UPDATE reservations SET confirmed=FALSE,
            code=NULL
            WHERE ID=:reservationID");
        $query->execute(array(
            ":reservationID" => $reservationID
        )); 
    }

    /**
     * Check if confirmation code is valid for a registration for this event
     *
     * @param $eventID
     * @param $code
     * 
     */
    public static function checkReservation($eventID, $code){
        if(!EventModel::eventBelongsToLoggedInUser($eventID)){
            Session::add('feedback_negative', Text::get('FEEDBACK_CHECK_RESERVATION_NOT_CREATOR'));
            return;
        }
        $database = DatabaseFactory::getFactory()->getConnection();
        $query = $database->prepare("SELECT u.user_name AS name FROM reservations AS r
        JOIN events AS e ON e.ID=r.event
        JOIN users AS u ON u.user_id = r.user_participant
        WHERE r.code=:code AND e.ID=:eventID AND r.confirmed=TRUE");
        $query->execute(array(
            ":code"     => $code,
            ":eventID"  => $eventID
        ));
        if($query->rowCount()==0)
            Session::add('feedback_negative', Text::get('FEEDBACK_CHECK_RESERVATION_NOT_VALID'));
        else{
            $name = $query->fetch()->name;
            Session::add('feedback_positive', "Valid registration code for user $name");
        }
    }
    
}
