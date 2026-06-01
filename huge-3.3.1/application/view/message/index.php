<div class="container">
    <h1>Message/index</h1>

    <div class="box">
        <?php
        $isGroup = isset($this->group_id);
        $lastSender = null;
        ?>

        <h3>What happens here ?</h3>

        <div>
            This controller/action/view shows a list of all messages between you and
            <?php echo $isGroup ? "the group " : "user "; ?><?= $this->reciever; ?>
        </div>
        
        <div id="chat" class="chat-window">
            <div>
                <?php foreach($this->messages as $message) {?>
                    <?php if($message->sender != Session::get('user_id')) { ?>
                        <?php if($isGroup && $lastSender != $message->sender) { ?>
                                <div class="message-sender">
                                    <?= $message->sender_name ?>
                                </div>
                                <br>
                            <?php } ?>
                        <div class="chat-bubble-container-left">
                            <div
                            class="chat-bubble <?php echo $message->seen? "chat-seen": "chat-new"?>">
                                <?= $message->text ?>
                            </div>
                            <div class="message-date">
                                <?= $message->timestamp ?>
                            </div>
                        </div>
                    <?php } else { ?>
                        <div class="chat-bubble-container-right">
                            <div class="message-date">
                                <?= $message->timestamp ?>
                            </div>
                            <div class="chat-bubble chat-sent">
                                <?= $message->text ?>
                            </div>
                        </div>
                    <?php } ?>
               <?php $lastSender = $message->sender; } ?>
            </div>
        </div>
            <form method="POST"
            <?php if($isGroup) { ?>
            action="<?= Config::get('URL') . 'message/writeGroup/' . $this->group_id; ?>"
            <?php }else { ?>
            action="<?= Config::get('URL') . 'message/write/' . $this->reciever_id ; ?>"
            <?php } ?>
            class="message-Input-Form">
                <textarea class="message-Input" name="text" required="True"></textarea>
                <br>
                <button type="Submit">
                    Send
                </button>
            </form>
    </div>
</div>
<script>
    
</script>