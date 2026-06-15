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
        if($participantLimit && $participants<$participantLimit){
            Session::add('feedback_negative', Text::get('FEEDBACK_EVENT_EDIT_PARTICIPATION_LIMIT_TO_LOW'));
            return;
        }

        $database = DatabaseFactory::getFactory()->getConnection();
        $query = $database->prepare("UPDATE events
        Set name=:name, description=:description, date=:date, participant_limit=:participant_limit
        WHERE ID=:eventID");

        $query->execute(array(
            ":name"                 => $eventName,
            ":description"          => $eventDescription,
            ":date"                 => $eventDate,
            ":participant_limit"    => $participantLimit,
            ":eventID"              => $eventID
        ));
    }

    /**
     * Delete Event as long as it belongs to the logged in user
     *
     * @param $eventID
     */
    public static function deleteEvent($eventID)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $query = $database->prepare("DELETE FROM events
        WHERE ID=:eventID AND user_creator=:user_creator");

        $query->execute(array(
            ":eventID"              => $eventID,
            ":user_creator"         => Session::get("user_id"),
        ));
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
    
}
