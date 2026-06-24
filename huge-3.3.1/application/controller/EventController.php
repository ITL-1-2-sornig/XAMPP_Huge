<?php

include(dirname(__DIR__).'../core/phpqrcode/qrlib.php');

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

    public function index(){
        $year = (int)date("Y");
        $month = (int)date("m");
        if(isset($_POST["year"]) && isset($_POST["year"])){
            $year = (int)$_POST["year"];
            $month = (int)$_POST["month"];
        }
        
        $this->View->render('event/index', array(
            "days"  => EventModel::GetDaysWithEvents($year, $month),
            "year"  => $year,
            "month" => $month,
        ));

    }

    public function show($eventID){
        $event = EventModel::getEvent($eventID);
        if(!$event){
            Redirect::to("event/index");
            return;
        }

        if($event->creator == Session::get('user_id'))
            $this->View->render('event/editEvent', array(
                "event" => $event,
                "reservations" => EventModel::getEventReservations($eventID),
            ));
        else
            $this->View->render('event/viewEvent', array(
                "event" => $event,
                "reservation" => EventModel::getUserEventReservation($eventID),
            ));
    }

    public function checkCode($eventID){
        if(!isset($_GET["code"])){
            Redirect::to("event/show/".$eventID);
            return;
        }
        $code = $_GET["code"];

        EventModel::checkReservation($eventID, $code);
        // Message to show wether code is valid gets handled in Model,
        // View just recieves eventID for the link back
        $this->View->render('event/checkCode', array(
            "eventID" => $eventID
        ));
    }

    public function newEvent()
    {
        $this->View->render('event/newEvent');
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

    public function addReservation($eventID)
    {
        EventModel::addReservation($eventID);
        Redirect::to("event/show/" . $eventID);
    }

    public function cancelReservation($eventID)
    {
        EventModel::cancelReservation($eventID);
        Redirect::to("event/show/" . $eventID);
    }

    public function deleteEvent($eventID)
    {
        EventModel::deleteEvent($eventID);
        Redirect::to("event/index");
    }

    public function acceptReservation($reservationID, $eventID)
    {
        EventModel::acceptReservation($reservationID, $eventID);
        Redirect::to("event/show/" . $eventID);
    }

    public function unacceptReservation($reservationID, $eventID)
    {
        EventModel::unacceptReservation($reservationID, $eventID);
        Redirect::to("event/show/" . $eventID);
    }

    public function multiAcceptReservation($eventID){
        $registrationIDs = array();
        foreach($_POST as $key=>$value)
            if(preg_match("/^confirm_[\d]+/", $key)&&$value=="on")
                array_push($registrationIDs, substr($key, 8));

        EventModel::multiAcceptReservation($registrationIDs, $eventID);
        Redirect::to("event/show/" . $eventID);
    }

    public function qrCode($eventID, $code){
        $url = $_SERVER["REMOTE_ADDR"].'/event/checkCode/'.$eventID."?code=".$code;
        QRcode::png($url);
    }

}