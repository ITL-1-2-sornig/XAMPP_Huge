<div class="container">
    <h1>Message/create_groupchat</h1>

    <div class="box">

        <h3>What happens here ?</h3>

        <div>
            This controller/action/view allows you to create a new groupchat
        </div>
        
        <form method="POST"
        action="<?= Config::get('URL') . 'message/newGroupchat'; ?>">
            <?php foreach ($this->users as $user) { ?>
                <input type="checkbox" name="users[]" value="<?= $user->user_id; ?>"
                <?php if($user->user_id == Session::get('user_id')) { ?> checked disabled<?php } ?>><?= $user->user_name; ?><br>
            <?php } ?>
            <br>
            <input type="text" name="group_name" placeholder="Groupchat Name" required="True"><br>
            <br>
            <button type="Submit">
                create
            </button>
        </form>
    </div>
</div>
<script>
    
</script>