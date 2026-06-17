<?php

class EventController extends Controller
{
    /**
     * Construct this object by extending the basic Controller class
     */
    public function __construct()
    {
        parent::__construct();
        Auth::checkAuthentication();
    }

    public function show($eventID){
        $event = EventModel::getEvent($eventID);
        if($event->creator == Session::get('user_id'))
            $this->View->render('event/index', array(
                "event" => $event,
                "reservations" => EventModel::getEventReservations($eventID),
            ));
        else
            $this->View->render('event/viewEvent', array(
                "event" => $event
            ));
        
    }

    public function newEvent()
    {
        $this->View->render('event/newEvent', array(
            
        ));
    }

    public function createEvent()
    {
        if(!isset($_POST["name"])||
        !isset($_POST["description"])||
        !isset($_POST["date"])||
        !isset($_POST["limit"])){
            Redirect::to("event/newEvent");
            return;
        }

        $name = $_POST["name"];
        $description = $_POST["description"];
        $date = $_POST["date"];
        $limit = $_POST["limit"];
        $newEventID = EventModel::createEvent($name, $date, $description, $limit);
        Redirect::to("event/show/" . $newEventID);
    }

    public function editEvent()
    {
        if(!isset($_POST["eventID"])||
        !isset($_POST["name"])||
        !isset($_POST["description"])||
        !isset($_POST["date"])||
        !isset($_POST["limit"])){
            Redirect::to("event/newEvent");
            return;
        }

        $eventID = $_POST["eventID"];
        $name = $_POST["name"];
        $description = $_POST["description"];
        $date = $_POST["date"];
        $limit = $_POST["limit"];
        EventModel::editEvent($eventID, $name, $date, $description, $limit);
        Redirect::to("event/show/" . $eventID);
    }

    public function addReservation()
    {

    }

    public function deleteEvent()
    {
        
    }

    public function acceptReservation($reservationID, $eventID)
    {
        EventModel::acceptReservation($reservationID);
        Redirect::to("event/show/" . $eventID);
    }

    public function unacceptReservation($reservationID, $eventID)
    {
        EventModel::unacceptReservation($reservationID);
        Redirect::to("event/show/" . $eventID);
    }

    public function multiAcceptReservation($eventID){
        //Implement next
    }

}