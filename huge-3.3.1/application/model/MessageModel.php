<?php

/**
 * Handles all message Data
 */
class MessageModel
{
    /**
     * Gets all Messages between logged in user and another user
     *
     * @param $userID
     * 
     * @return array Returns arrray with all messages between logged in user and the given user ordered by timestamp
     */
    public static function getAllMessagesBetweenUsersLoggedIn($userID)
    {
        $user1ID = Session::get('user_id');
        return MessageModel::getAllMessagesBetweenUsers($user1ID, $userID);
    }

    /**
     * Gets all Messages between 2 users
     *
     * @param $user1ID
     * @param $user2ID
     * 
     * @return array Returns arrray with all messages between the users ordered by timestamp
     */
    public static function getAllMessagesBetweenUsers($user1ID, $user2ID)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $query = $database->prepare("CALL getAllMessagesBetweenUsers(:user_1_id, :user_2_id)");
        $query->execute(array(
                ':user_1_id' => $user1ID,
                ':user_2_id' => $user2ID
        ));

        //sender.user_name AS sender_name, reciever.user_name AS reciever_name,
        //JOIN users AS sender ON sender.user_id = message.user_sender
        //JOIN users AS reciever ON  reciever.user_id = message.user_reciever

        $messages = array();

        foreach ($query->fetchAll() as $message) {

            // all elements of array passed to Filter::XSSFilter for XSS sanitation, have a look into
            // application/core/Filter.php for more info on how to use. Removes (possibly bad) JavaScript etc from
            // the message's values
            array_walk_recursive($message, 'Filter::XSSFilter');

            $messages[$message->message_id] = new stdClass();
            $messages[$message->message_id]->id = $message->message_id;
            $messages[$message->message_id]->sender = $message->user_sender;
            $messages[$message->message_id]->reciever = $message->user_reciever;
            $messages[$message->message_id]->seen = $message->message_seen;
            $messages[$message->message_id]->timestamp = $message->message_timestamp;
            $messages[$message->message_id]->text = str_replace("\n", "<br>", $message->message_text);
        }
        $query->closeCursor();

        return $messages;
    }

    /**
     * Gets all Messages in a groupchat
     *
     * @param $groupID
     * 
     * @return array Returns arrray with all messages in the groupchat ordered by timestamp
     */
    public static function getAllMessagesInGroupchat($groupID)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $query = $database->prepare("CALL getAllMessagesInGroupchat(:thread_id, :user_id)");
        
        $query->execute(array(
            ':thread_id' => $groupID,
            ':user_id' => Session::get('user_id')
        ));

        $messages = array();
        $lastReadMessageID = 0;
        foreach ($query->fetchAll() as $message) {

            // all elements of array passed to Filter::XSSFilter for XSS sanitation, have a look into
            // application/core/Filter.php for more info on how to use. Removes (possibly bad) JavaScript etc from
            // the message's values
            array_walk_recursive($message, 'Filter::XSSFilter');

            $messages[$message->message_id] = new stdClass();
            $messages[$message->message_id]->id = $message->message_id;

            $messages[$message->message_id]->sender_name = $message->sender_name;
            $messages[$message->message_id]->sender = $message->sender_id;
            $messages[$message->message_id]->seen = $message->seen;
            $messages[$message->message_id]->timestamp = $message->message_timestamp;
            $messages[$message->message_id]->text = str_replace("\n", "<br>", $message->message_text);
            $lastReadMessageID = $message->message_id;
        }
        $query->closeCursor();
        if($lastReadMessageID > 0)
            MessageModel::setStatusSeenGroup($groupID, Session::get('user_id'), $lastReadMessageID);
        
        return $messages;
    }

    /**
     * Adds new message with specified text from one user to another
     *
     * @param $senderID
     * @param $recieverID
     * @param $text
     * 
     */
    public static function newMessage($senderID, $recieverID, $text){
        $database = DatabaseFactory::getFactory()->getConnection();
        $query = $database->prepare("CALL newMessage(:sender, :reciever, :text)");
        $query->execute(array(
                ':sender'   => $senderID,
                ':reciever' => $recieverID,
                ':text'      => $text
        ));
        $query->closeCursor();
    }

    /**
     * Adds new message with specified text from one user to a group
     *
     * @param $senderID
     * @param $groupID
     * @param $text
     * 
     */
    public static function newMessageGroup($senderID, $groupID, $text){
        $database = DatabaseFactory::getFactory()->getConnection();
        $query = $database->prepare("CALL newMessageGroup(:sender_user_id, :thread_id, :message_text)");
        $query->execute(array(
                ':thread_id' => $groupID,
                ':sender_user_id' => $senderID,
                ':message_text' => $text
        ));
        $messageID = $database->lastInsertId();
        MessageModel::setStatusSeenGroup($groupID, Session::get('user_id'), $messageID);
        $query->closeCursor();
    }

    /**
     * Gets number of unread Messages between to logged in user
     * 
     * @return array Returns arrray with all user_ids and the number of unread messages
     * and a boolean indicating if any messages have been sent between them
     */
    public static function getNumUnreadMessages(){
        $database = DatabaseFactory::getFactory()->getConnection();
        $queryusers = $database->prepare("CALL getAllUserIDs()");
        $queryusers->execute(array());
        $queryMessages = $database->prepare("CALL getallMessages(:user_id)");

        $tally = array();

        foreach ($queryusers->fetchAll() as $user) {
            $tally[$user->user_id] = new stdClass();
            $tally[$user->user_id]->unread = 0;
            $tally[$user->user_id]->hasChat = false;
        }

        $queryusers->closeCursor();

        $queryMessages->execute(array(
            ':user_id' => Session::get('user_id')
        ));

        foreach ($queryMessages->fetchAll() as $message) {
            if($message->user_sender != Session::get('user_id')){
                $tally[$message->user_sender]->hasChat = true;
                if(!$message->message_seen)
                    $tally[$message->user_sender]->unread ++;
            }
            elseif($message->user_reciever)
                $tally[$message->user_reciever]->hasChat = true;
        }
        $queryMessages->closeCursor();
        return $tally;
    }

    /**
     * Gets number of unread Messages in groupchats of the logged in user
     * 
     * @return array Returns arrray with all user_ids and the number of unread messages
     * and a boolean indicating if any messages have been sent between them
     */
    public static function getNumUnreadGroupMessages(){
        $database = DatabaseFactory::getFactory()->getConnection();
        $query = $database->prepare("CALL getNumUnreadGroupMessages(:user_id)");
        $query->execute(array(
            ':user_id' => Session::get('user_id')
        ));

        $tally = array();

        foreach ($query->fetchAll() as $groupchat) {
            $tally[$groupchat->group_id] = new stdClass();
            $tally[$groupchat->group_id]->thread_id = $groupchat->group_id;
            $tally[$groupchat->group_id]->unread = $groupchat->unread;
        }

        $query->closeCursor();
        return $tally;
    }

    /**
     * Sets Status of all messages from one user to another to seen
     *
     * @param $senderID
     * @param $recieverID
     * 
     */
    public static function setStatusSeen($senderID, $recieverID){
        $database = DatabaseFactory::getFactory()->getConnection();
        $query = $database->prepare("CALL setStatusSeen(:sender, :reciever)");
        $query->execute(array(
            ':sender'   => $senderID,
            ':reciever' => $recieverID
        ));
        $query->closeCursor();
    }

    /**
     * Sets Status of messages in a groupchat to seen for a user to a certain message
     *
     * @param $groupID
     * @param $userID
     * @param $lastReadMessageID
     * 
     */
    public static function setStatusSeenGroup($groupID, $userID, $lastReadMessageID){
        $database = DatabaseFactory::getFactory()->getConnection();
        $query = $database->prepare("CALL setStatusSeenGroup(:user_id, :group_id, :last_read_message_id)");
        $query->execute(array(
            ':last_read_message_id' => $lastReadMessageID,
            ':group_id' => $groupID,
            ':user_id' => $userID
        ));
        $query->closeCursor();
    }

    /**
     * Returns all groupchats a user is a member of
     *
     * @param $userID
     * 
     * @return array Returns arrray with all groupchats the user is a member of
     */
    public static function getAllGroupChatsForUser($userID){
        $database = DatabaseFactory::getFactory()->getConnection();
        $query = $database->prepare("CALL getAllGroupChatsForUser(:user_id)");
        $query->execute(array(
            ':user_id' => $userID
        ));

        $groupchats = array();

        foreach ($query->fetchAll() as $groupchat) {
            array_walk_recursive($groupchat, 'Filter::XSSFilter');

            $groupchats[$groupchat->thread_id] = new stdClass();
            $groupchats[$groupchat->thread_id]->id = $groupchat->thread_id;
            $groupchats[$groupchat->thread_id]->name = $groupchat->thread_name;
        }
        $query->closeCursor();
        return $groupchats;
    }

    /**
     * Creates a new groupchat with the given name and members
     *
     * @param $name
     * @param $members
     * 
     */
    public static function createGroupChat($name, $members){
        $database = DatabaseFactory::getFactory()->getConnection();
        $query = $database->prepare("CALL createGroupChat(:name, :created_by)");
        $query->execute(array(
            ':name' => $name,
            ':created_by' => Session::get('user_id')
        ));
        $threadID = $query->fetch()->newID;
        $query->closeCursor();
        $query = $database->prepare("CALL addMemberToGroupchat(:user_id, :thread_id)");
        foreach($members as $member){
            $query->execute(array(
                ':thread_id' => $threadID,
                ':user_id' => $member
            ));
        }
        Session::add('feedback_positive', Text::get('FEEDBACK_GROUPCHAT_CREATION_SUCCESSFUL'));
        $query->closeCursor();
    }

    /**
     * Returns the name of a chat by its ID
     *
     * @param $id
     * @return string
     */
    public static function getChatNameByID($id){
        $database = DatabaseFactory::getFactory()->getConnection();
        $query = $database->prepare("CALL getChatNameByID(:thread_id)");
        $query->execute(array(
            ':thread_id' => $id
        ));
        return $query->fetch()->thread_name;
    }

    /**
     * Checks if the logged-in user is a member of a specific group chat
     *
     * @param $groupID
     * @return bool
     */
    public static function LoggedInuserIsMember($groupID){
        $database = DatabaseFactory::getFactory()->getConnection();
        $query = $database->prepare("CALL getMemberInGroup(:user_id, :thread_id)");
        $query->execute(array(
            ':thread_id' => $groupID,
            ':user_id' => Session::get('user_id')
        ));
        return $query->rowCount() > 0;
    }

}