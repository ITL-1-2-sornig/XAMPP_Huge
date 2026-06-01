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
        $query = $database->prepare("SELECT * FROM messages
        WHERE (user_sender = :user_1_id AND user_reciever = :user_2_id)
        OR (user_sender = :user_2_id AND user_reciever = :user_1_id)
        ORDER BY message_timestamp;");
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
        $query = $database->prepare("INSERT INTO messages (user_sender, user_reciever, message_text)
            VALUES (:sender, :reciever, :text)");
        $query->execute(array(
                ':sender'   => $senderID,
                ':reciever' => $recieverID,
                'text'      => $text
        ));

    }

    /**
     * Gets number of unread Messages between to logged in user
     * 
     * @return array Returns arrray with all user_ids and the number of unread messages
     * and a boolean indicating if any messages have been sent between them
     */
    public static function getNumUnreadMessages(){
        $database = DatabaseFactory::getFactory()->getConnection();
        $queryusers = $database->prepare("SELECT user_id FROM users");
        $queryusers->execute(array());
        $queryMessages = $database->prepare("SELECT user_sender, user_reciever, message_seen
        FROM messages WHERE user_sender = :user_id OR user_reciever = :user_id");

        $tally = array();

        foreach ($queryusers->fetchAll() as $user) {
            $tally[$user->user_id] = new stdClass();
            $tally[$user->user_id]->unread = 0;
            $tally[$user->user_id]->hasChat = false;
        }

        $queryMessages->execute(array(
            ':user_id' => Session::get('user_id')
        ));

        foreach ($queryMessages->fetchAll() as $message) {
            if($message->user_sender != Session::get('user_id')){
                $tally[$message->user_sender]->hasChat = true;
                if(!$message->message_seen)
                    $tally[$message->user_sender]->unread ++;
            }
        }
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
        $query = $database->prepare("UPDATE messages SET message_seen = '1'
        WHERE user_sender=:sender AND user_reciever=:reciever");
        $query->execute(array(
            ':sender'   => $senderID,
            ':reciever' => $recieverID
        ));
    }
}