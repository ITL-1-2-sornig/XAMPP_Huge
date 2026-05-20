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
                'messages' => MessageModel::getAllMessagesBetweenUsersLoggedIn($user),
                'reciever' => UserModel::getUserNameByID($user))
        );
    }


}