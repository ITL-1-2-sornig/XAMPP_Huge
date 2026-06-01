<div class="container">
    <h1>ProfileController/index</h1>
    <div class="box">

        <!-- echo out the system feedback (error and success messages) -->
        <?php $this->renderFeedbackMessages(); ?>

        <h3>What happens here ?</h3>
        <div>
            This controller/action/view shows a list of all users in the system. You could use the underlying code to
            build things that use profile information of one or multiple/all users.
        </div>
        <div>
            <table id="table_users" class="overview-table">
                <thead>
                <tr>
                    <td>Id</td>
                    <td>Avatar</td>
                    <td>Username</td>
                    <td>User's email</td>
                    <td>Activated ?</td>
                    <td>Link to user's profile</td>
                    <td>Role</td>
                    <td>Chat</td>
                </tr>
                </thead>
                <?php foreach ($this->users as $user) { ?>
                    <tr class="<?= ($user->user_active == 0 ? 'inactive' : 'active'); ?>">
                        <td><?= $user->user_id; ?></td>
                        <td class="avatar">
                            <?php if (isset($user->user_avatar_link)) { ?>
                                <img src="<?= $user->user_avatar_link; ?>" />
                            <?php } ?>
                        </td>
                        <td><?= $user->user_name; ?></td>
                        <td><?= $user->user_email; ?></td>
                        <td><?= ($user->user_active == 0 ? 'No' : 'Yes'); ?></td>
                        <td>
                            <a href="<?= Config::get('URL') . 'profile/showProfile/' . $user->user_id; ?>">Profile</a>
                        </td>
                        <td><?= $user->user_role_name; ?></td>
                        <td>
                            <?php if(Session::userIsLoggedIn()){
                                if ($user->user_id!=Session::get('user_id')) { ?>
                                <form action="<?= Config::get('URL') . 'message/index/' . $user->user_id; ?>">
                                    <Button type="Submit" class="<?php echo $this->unread[$user->user_id]->unread==0? "message-counter-no-new":"message-counter-new" ?>">
                                        <?php
                                        if(!$this->unread[$user->user_id]->hasChat)
                                            echo "Start Chat";
                                        else
                                            echo $this->unread[$user->user_id]->unread . " new messages";
                                        ?>
                                    </Button>
                                </form>
                            <?php }} else { ?>
                                logged out
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    </div>
</div>
<script>
    new DataTable('#table_users');
</script>
