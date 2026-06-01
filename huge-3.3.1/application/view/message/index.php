<div class="container">
    <h1>Message/index</h1>

    <div class="box">

        <h3>What happens here ?</h3>

        <div>
            This controller/action/view shows a list of all messages between you and <?= $this->reciever; ?>
        </div>
        <div id="chat" class="chat-window">
            <div>
                <?php foreach($this->messages as $message) {?>
                    <?php if($message->sender != Session::get('user_id')) { ?>
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
                <?php } ?>
            </div>
        </div>
            <form method="POST"
            action="<?= Config::get('URL') . 'message/write/' . $this->reciever_id ; ?>"
            class="message-Input-Form">
                <textarea calss="message-Input" name="text" required="True"></textarea>
                <br>
                <button type="Submit">
                    Senden
                </button>
            </form>
    </div>
</div>
<script>
    
</script>