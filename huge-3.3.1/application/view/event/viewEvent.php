<div class="container">
    <h1>EventController/viewEvent</h1>
    <div class="box">

        <!-- echo out the system feedback (error and success messages) -->
        <?php $this->renderFeedbackMessages(); ?>

        <h3>What happens here ?</h3>
        <div>
            This controller/action/view shows an overview of an
            event created by another user and allows you to make
            a reservation or check the status of your reservation
        </div>
        <?= phpversion();?>
        <div>
            <br>
            <h2><?= $this->event->name ?></h2>
            <?= $this->event->description ?><br><br>
            <?= $this->event->date ?><br><br>
            <div>
                <?php if($this->reservation){ ?>
                    <?php if($this->reservation->confirmed){ ?>
                        Your reservation has been accepted by the events host!
                    <?php } else { ?>
                        You have made a reservation, but it has not yet been accepted by the events host
                    <?php }} else { ?>
                        You have not yet made a reservation for this event
                    <form action="<?= config::get("URL"); ?>event/addReservation/<?= $this->event->ID ?>">
                        <Button Type="Submit">Reserve your spot</Button>
                    </form>
                <?php } ?>
            </div>
            
        </div>
    </div>
</div>
