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
        <div>
            <br>
            <h2><?= $this->event->name ?></h2>
            <?= $this->event->description ?><br><br>
            <?= $this->event->date ?><br><br>
            <div>
                <?php
                    $date = new DateTime($this->event->date);
                    $now = new DateTime();
                    if($date < $now){ ?>
                    <div class="past_event">
                        This event is in the past.
                        You can no longer submit a reservation fo it.
                    </div> 
                <?php } else if($this->reservation){ ?>
                    <?php if($this->reservation->confirmed){ ?>
                        <div class="reservation_accepted">
                            Your reservation has been accepted by the events host!<br>
                            Your reservation code: <?= $this->reservation->code ?>
                        </div>
                        <?='<img src="'.config::get("URL")."event/qrCode/".$this->event->ID."/".$this->reservation->code.'"/>'; ?>
                    <?php } else { ?>
                        <div class="reservation_waiting">
                            You have made a reservation, but it has not yet been accepted by the events host
                        </div> 
                    <?php } ?>
                        <br>
                        <form action="<?= config::get("URL"); ?>event/cancelReservation/<?= $this->event->ID ?>">
                            <Button class="btn_cancel_reservation" Type="Submit">Cancel my Reservation</Button>
                        </form>
                    <?php } else { ?>
                        <div class="no_reservation">
                            You have not yet made a reservation for this event
                        </div>
                        <?php if($this->event->taken>=$this->event->limit && $this->event->limit!=0) { ?>
                            <div class="no_spaces_left">
                                All spaces for this event are currently taken.
                                You can still make a reservation hoping that one of them will become availavle again.
                            </div>
                        <?php } ?>
                    <br>
                    <form action="<?= config::get("URL"); ?>event/addReservation/<?= $this->event->ID ?>">
                        <Button Type="Submit">Reserve your spot</Button>
                    </form>
                <?php } ?>
            </div>
            
        </div>
    </div>
</div>
