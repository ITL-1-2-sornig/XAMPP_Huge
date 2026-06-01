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
     * Gets all Messages in a groupchat
     *
     * @param $groupID
     * 
     * @return array Returns arrray with all messages in the groupchat ordered by timestamp
     */
    public static function getAllMessagesInGroupchat($groupID)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $query = $database->prepare("SELECT u.user_name AS sender_name,
        u.user_id AS sender_id,
        m.chat_message_id AS message_id,
        m.message_timestamp AS message_timestamp,
        m.message_text AS message_text
        FROM chat_messages AS m
        JOIN users AS u
        ON m.sender_user_id = u.user_id
        WHERE m.thread_id = :thread_id
        ORDER BY message_timestamp;");
        $query->execute(array(
                ':thread_id' => $groupID
        ));

        $queryLastSeen = $database->prepare("SELECT last_read_message_id FROM chat_members
        WHERE thread_id = :thread_id AND user_id = :user_id");
        $queryLastSeen->execute(array(
            ':thread_id' => $groupID,
            ':user_id' => Session::get('user_id')
        ));
        $lastReadMessageID = $queryLastSeen->fetch()->last_read_message_id;

        //sender.user_name AS sender_name, reciever.user_name AS reciever_name,
        //JOIN users AS sender ON sender.user_id = message.user_sender
        //JOIN users AS reciever ON  reciever.user_id = message.user_reciever

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
            $messages[$message->message_id]->seen = $message->message_id <= $lastReadMessageID;
            $messages[$message->message_id]->timestamp = $message->message_timestamp;
            $messages[$message->message_id]->text = str_replace("\n", "<br>", $message->message_text);
            $lastReadMessageID = $message->message_id;
        }
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
        $query = $database->prepare("INSERT INTO messages (user_sender, user_reciever, message_text)
            VALUES (:sender, :reciever, :text)");
        $query->execute(array(
                ':sender'   => $senderID,
                ':reciever' => $recieverID,
                'text'      => $text
        ));

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
        $query = $database->prepare("INSERT INTO chat_messages (thread_id, sender_user_id, message_text)
            VALUES (:thread_id, :sender_user_id, :message_text)");
        $query->execute(array(
                ':thread_id' => $groupID,
                ':sender_user_id' => $senderID,
                ':message_text' => $text
        ));
        $messageID = $database->lastInsertId();
        MessageModel::setStatusSeenGroup($groupID, Session::get('user_id'), $messageID);
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
            elseif($message->user_reciever)
                $tally[$message->user_reciever]->hasChat = true;
        }
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
        $query = $database->prepare(
        "SELECT chat_message_id AS chat_message_id, chat_threads.thread_id AS thread_id, chat_members.last_read_message_id AS last_read_message_id FROM chat_threads
        JOIN chat_members ON chat_threads.thread_id = chat_members.thread_id
        JOIN chat_messages ON chat_threads.thread_id = chat_messages.thread_id
        WHERE chat_members.user_id = :user_id");
        $query_groupchats = $database->prepare("SELECT chat_threads.thread_id FROM chat_threads
        JOIN chat_members ON chat_threads.thread_id = chat_members.thread_id
        WHERE chat_members.user_id = :user_id");
        $query_groupchats->execute(array(
            ':user_id' => Session::get('user_id')
        ));
        $query->execute(array(
            ':user_id' => Session::get('user_id')
        ));
        $tally = array();

        foreach ($query_groupchats->fetchAll() as $groupchat) {
            $tally[$groupchat->thread_id] = new stdClass();
            $tally[$groupchat->thread_id]->unread = 0;
        }

        foreach ($query->fetchAll() as $group) {
            if($group->chat_message_id > $group->last_read_message_id)
                $tally[$group->thread_id]->unread ++;
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
        $query = $database->prepare("UPDATE chat_members SET last_read_message_id = :last_read_message_id
        WHERE thread_id = :group_id AND user_id = :user_id");
        $query->execute(array(
            ':last_read_message_id' => $lastReadMessageID,
            ':group_id' => $groupID,
            ':user_id' => $userID
        ));
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
        $query = $database->prepare("SELECT chat_threads.thread_name, chat_threads.thread_id FROM chat_threads
        JOIN chat_members ON chat_threads.thread_id = chat_members.thread_id
        AND chat_members.user_id = :user_id");
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
        $query = $database->prepare("INSERT INTO chat_threads (thread_name, created_by) VALUES (:name, :created_by)");
        $query->execute(array(
            ':name' => $name,
            ':created_by' => Session::get('user_id')
        ));
        $threadID = $database->lastInsertId();
        $query = $database->prepare("INSERT INTO chat_members (thread_id, user_id) VALUES (:thread_id, :user_id)");
        foreach($members as $member){
            $query->execute(array(
                ':thread_id' => $threadID,
                ':user_id' => $member
            ));
        }
        Session::add('feedback_positive', Text::get('FEEDBACK_GROUPCHAT_CREATION_SUCCESSFUL'));
    }

    /**
     * Returns the name of a chat by its ID
     *
     * @param $id
     * @return string
     */
    public static function getChatNameByID($id){
        $database = DatabaseFactory::getFactory()->getConnection();
        $query = $database->prepare("SELECT thread_name FROM chat_threads WHERE thread_id = :thread_id");
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
        $query = $database->prepare("SELECT * FROM chat_members WHERE thread_id = :thread_id AND user_id = :user_id");
        $query->execute(array(
            ':thread_id' => $groupID,
            ':user_id' => Session::get('user_id')
        ));
        return $query->rowCount() > 0;
    }

}