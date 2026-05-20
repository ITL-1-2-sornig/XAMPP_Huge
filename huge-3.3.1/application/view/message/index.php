<div class="container">
    <h1>Message/index</h1>

    <div class="box">

        <h3>What happens here ?</h3>

        <div>
            This controller/action/view shows a list of all messages between you and <?= $this->reciever; ?>
        </div>
        <div>
            <table class="overview-table" id="table_messages">
                <thead>
                <tr>
                    <td><?= $this->reciever ?></td>
                    <td><?= Session::get('user_name'); ?></td>
                </tr>
                <?php foreach($this->messages as $message) {?>
                    <td><?php if($message->sender != Session::get('user_id')) echo $message->text; ?></td>
                    <td><?php if($message->sender == Session::get('user_id')) echo $message->text; ?></td>
                <?php } ?>
                </thead>
            </table>
        </div>
    </div>
</div>
<script>
    //new DataTable('#table_users');
</script>