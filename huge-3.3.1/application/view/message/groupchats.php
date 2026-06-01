<div class="container">
    <h1>Message/groupchats</h1>

    <div class="box">

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
                        <td><a href="<?= Config::get('URL') . 'message/group/' . $group->id; ?>"> unread messages: <?= $group->unread ?></a></td>
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