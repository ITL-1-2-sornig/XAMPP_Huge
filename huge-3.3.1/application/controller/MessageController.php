<?php

class MessageController extends Controller
{
    /**
     * Construct this object by extending the basic Controller class
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * This method controls what happens when you move to /message/user
     */
    public function index($user)
    {
        $this->View->render('message/index', array(
                'messages'      => MessageModel::getAllMessagesBetweenUsersLoggedIn($user),
                'reciever'      => UserModel::getUserNameByID($user),
                'reciever_id'   => $user)
        );
        MessageModel::setStatusSeen($user, Session::get('user_id'));
    }

    /**
     * This method controls what happens when you write a message to a user
     */
    public function write($reciever){
        $text = $_POST['text'];
        MessageModel::newMessage(Session::get('user_id'), $reciever, $text);
        Redirect::to("message/index/$reciever");
    }

}