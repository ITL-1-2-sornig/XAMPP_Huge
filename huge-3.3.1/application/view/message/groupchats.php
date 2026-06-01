<div class="container">
    <h1>Message/groupchats</h1>

    <div class="box">

        <!-- echo out the system feedback (error and success messages) -->
        <?php $this->renderFeedbackMessages(); ?>

        <h3>What happens here ?</h3>

        <div>
            This controller/action/view shows a list of all groupchats you are a part of and allows you to create new ones
        </div>
        <table id="groups_table">
            <thead>
                <tr>
                    <td>Groupchat Name</td>
                    <td>Messages</td>
                </tr>
            <div>
                <?php foreach($this->groups as $group) {?>
                    <tr>
                        <td><?= $group->name ?></td>
                        <td>
                            <form action="<?php echo Config::get('URL') . 'message/index_group/' . $group->id; ?>">
                                    <Button type="Submit" class="<?php echo $this->unread[$group->id]->unread==0? "message-counter-no-new":"message-counter-new" ?>">
                                        <?= $this->unread[$group->id]->unread . " new messages";
                                        ?>
                                    </Button>
                                </form>
                        </td>
                <?php } ?>
            </div>
        </table>
            <form method="POST"
            action="<?= Config::get('URL') . 'message/create_groupchat'; ?>">
                <button type="Submit">
                    new groupchat
                </button>
            </form>
    </div>
</div>
<script>
    
</script>