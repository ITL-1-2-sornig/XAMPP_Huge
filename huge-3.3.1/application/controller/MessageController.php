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
     * This method controls what happens when you move to /message/index
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
     * This method controls what happens when you move to /message/index_group
     */
    public function index_group($group)
    {
        if(!MessageModel::LoggedInuserIsMember($group)){
            Redirect::to("message/groupchats");
        }
        $this->View->render('message/index', array(
                'messages'      => MessageModel::getAllMessagesInGroupchat($group),
                'reciever'      => MessageModel::getChatNameByID($group),
                'group_id'      => $group
        ));
        MessageModel::setStatusSeen($group, Session::get('user_id'));
    }

    /**
     * This method controls what happens when you write a message to a user
     */
    public function write($reciever){
        $text = $_POST['text'];
        MessageModel::newMessage(Session::get('user_id'), $reciever, $text);
        Redirect::to("message/index/$reciever");
    }
    /**
     * This method controls what happens when you write a message to a group
     */
    public function writeGroup($group){
        $text = $_POST['text'];
        MessageModel::newMessageGroup(Session::get('user_id'), $group, $text);
        Redirect::to("message/index_group/$group");
    }
    /**
     * This method controls what happens when you move to /message/groupchats
     */
    public function groupchats(){
        $this->View->render('message/groupchats', array(
                'groups' => MessageModel::getAllGroupChatsForUser(Session::get('user_id')),
                'unread' => MessageModel::getNumUnreadGroupMessages()
            )
        );
    }
    /**
     * This method controls what happens when you move to /message/create_groupchat
     */
    public function create_groupchat(){
        $this->View->render('message/create_groupchat', array(
                'users' => UserModel::getPublicProfilesOfAllUsers()));
    }
    /**
     * This method controls what happens when create a new groupchat
     */
    public function newGroupchat(){
        $users = $_POST['users'];
        if(count($users) < 2){
            Session::add('feedback_negative', Text::get('FEEDBACK_GROUPCHAT_CREATION_MISSING_MEMBERS'));
            Redirect::to("message/create_groupchat");
        } else {
            MessageModel::createGroupChat($_POST['group_name'], $users);
        }
        $this->groupchats();
    }


}