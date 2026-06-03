DELIMITER /
CREATE DEFINER=`root`@`localhost` PROCEDURE `addMemberToGroupchat`(
	IN `userID` INT,
	IN `groupID` INT
)
LANGUAGE SQL
NOT DETERMINISTIC
CONTAINS SQL
SQL SECURITY DEFINER
COMMENT ''
BEGIN
	INSERT INTO chat_members (thread_id, user_id) VALUES (groupID, userID);
END /

CREATE DEFINER=`root`@`localhost` PROCEDURE `createGroupChat`(
	IN `chatName` VARCHAR(255),
	IN `createdBy` INT
)
LANGUAGE SQL
NOT DETERMINISTIC
CONTAINS SQL
SQL SECURITY DEFINER
COMMENT ''
BEGIN
	INSERT INTO chat_threads (thread_name, created_by) VALUES (chatName, createdBy);
	SELECT LAST_INSERT_ID() AS newID;
END /

CREATE DEFINER=`root`@`localhost` PROCEDURE `getAllGroupChatsForUser`(
	IN `userID` INT
)
LANGUAGE SQL
NOT DETERMINISTIC
CONTAINS SQL
SQL SECURITY DEFINER
COMMENT ''
BEGIN
	SELECT chat_threads.thread_name, chat_threads.thread_id FROM chat_threads
      JOIN chat_members ON chat_threads.thread_id = chat_members.thread_id
      AND chat_members.user_id = userID;
END/

CREATE DEFINER=`root`@`localhost` PROCEDURE `getallMessages`(
	IN `userID` INT
)
LANGUAGE SQL
NOT DETERMINISTIC
READS SQL DATA
SQL SECURITY DEFINER
COMMENT ''
BEGIN
	SELECT user_sender, user_reciever, message_seen
        FROM messages WHERE user_sender = userID OR user_reciever = userID;
END/

CREATE DEFINER=`root`@`localhost` PROCEDURE `getAllMessagesBetweenUsers`(
	IN `user_1_id` INT,
	IN `user_2_id` INT
)
LANGUAGE SQL
NOT DETERMINISTIC
READS SQL DATA
SQL SECURITY DEFINER
COMMENT ''
BEGIN
	SELECT * FROM messages
        WHERE (user_sender = user_1_id AND user_reciever = user_2_id)
        OR (user_sender = user_2_id AND user_reciever = user_1_id)
        ORDER BY message_timestamp;
END/

CREATE DEFINER=`root`@`localhost` PROCEDURE `getAllMessagesInGroupchat`(
	IN `groupID` INT,
	IN `userID` INT
)
LANGUAGE SQL
NOT DETERMINISTIC
READS SQL DATA
SQL SECURITY DEFINER
COMMENT ''
BEGIN
	DECLARE lastSeen INT;
	SELECT last_read_message_id INTO lastSeen FROM chat_members
      WHERE thread_id = groupID AND user_id = userID;
	SELECT u.user_name AS sender_name,
     u.user_id AS sender_id,
     m.chat_message_id AS message_id,
     m.message_timestamp AS message_timestamp,
     m.message_text AS message_text,
     (m.chat_message_id <= lastSeen) AS seen
     FROM chat_messages AS m
     JOIN users AS u
     ON m.sender_user_id = u.user_id
     WHERE m.thread_id = groupID
     ORDER BY message_timestamp;
        
END/

CREATE DEFINER=`root`@`localhost` PROCEDURE `getAllUserIDs`()
LANGUAGE SQL
NOT DETERMINISTIC
READS SQL DATA
SQL SECURITY DEFINER
COMMENT ''
BEGIN
	SELECT user_id FROM users;
END/

CREATE DEFINER=`root`@`localhost` PROCEDURE `getChatNameByID`(
	IN `groupID` INT
)
LANGUAGE SQL
NOT DETERMINISTIC
CONTAINS SQL
SQL SECURITY DEFINER
COMMENT ''
BEGIN
	SELECT thread_name FROM chat_threads WHERE thread_id = groupID;
END/

CREATE DEFINER=`root`@`localhost` PROCEDURE `getMemberInGroup`(
	IN `userID` INT,
	IN `groupID` INT
)
LANGUAGE SQL
NOT DETERMINISTIC
CONTAINS SQL
SQL SECURITY DEFINER
COMMENT ''
BEGIN
	SELECT * FROM chat_members WHERE thread_id = groupID AND user_id = userID;
END/

CREATE DEFINER=`root`@`localhost` PROCEDURE `getNumUnreadGroupMessages`(
	IN `userID` INT
)
LANGUAGE SQL
NOT DETERMINISTIC
CONTAINS SQL
SQL SECURITY DEFINER
COMMENT ''
BEGIN
	SELECT COUNT(m.chat_message_id) AS unread, t.thread_id AS group_id
	FROM chat_messages AS m
	JOIN chat_threads AS t
	ON t.thread_id = m.thread_id
	JOIN chat_members AS u
	ON u.thread_id = m.thread_id AND m.sender_user_id = u.user_id AND u.user_id <> UserID
	JOIN chat_members AS u2
	ON u2.thread_id = t.thread_id AND u2.user_id = UserID
	WHERE u2.last_read_message_id < m.chat_message_id;
END/

CREATE DEFINER=`root`@`localhost` PROCEDURE `newMessage`(
	IN `senderID` INT,
	IN `recieverID` INT,
	IN `messageText` VARCHAR(10000)
)
LANGUAGE SQL
NOT DETERMINISTIC
CONTAINS SQL
SQL SECURITY DEFINER
COMMENT ''
BEGIN
	INSERT INTO messages (user_sender, user_reciever, message_text)
      VALUES (senderID, recieverID, messageText);
END/

CREATE DEFINER=`root`@`localhost` PROCEDURE `newMessageGroup`(
	IN `senderID` INT,
	IN `groupID` INT,
	IN `messageText` VARCHAR(10000)
)
LANGUAGE SQL
NOT DETERMINISTIC
CONTAINS SQL
SQL SECURITY DEFINER
COMMENT ''
BEGIN
	INSERT INTO chat_messages (thread_id, sender_user_id, message_text)
      VALUES (groupID, senderID, messageText);
END/

CREATE DEFINER=`root`@`localhost` PROCEDURE `setStatusSeen`(
	IN `senderID` INT,
	IN `recieverID` INT
)
LANGUAGE SQL
NOT DETERMINISTIC
CONTAINS SQL
SQL SECURITY DEFINER
COMMENT ''
BEGIN
	UPDATE messages SET message_seen = '1'
      WHERE user_sender=senderID AND user_reciever=recieverID;
END/

CREATE DEFINER=`root`@`localhost` PROCEDURE `setStatusSeenGroup`(
	IN `userID` INT,
	IN `groupID` INT,
	IN `lastReadMessageID` INT
)
LANGUAGE SQL
NOT DETERMINISTIC
CONTAINS SQL
SQL SECURITY DEFINER
COMMENT ''
BEGIN
	UPDATE chat_members SET last_read_message_id = lastReadMessageID
      WHERE thread_id = groupID AND user_id = userID;
END/
DELIMITER ;