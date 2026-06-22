<div class="container">
    <h1>EventController/index</h1>
    <div class="box">

        <!-- echo out the system feedback (error and success messages) -->
        <?php $this->renderFeedbackMessages(); ?>

        <h3>What happens here ?</h3>
        <div>
            This controller/action/view shows an overview of an
            event created by you and a list of reservations created
            by other users. It allows editing the event and
            accepting reservations
        </div>
        <div>
            <br>
            <form method="POST" action="<?= config::get("URL"); ?>event/editEvent">
                <input type="number" name="eventID"
                value="<?= $this->event->ID ?>" hidden required></input>
                <label for="name">Name</label>
                <input name="name"
                value="<?= $this->event->name ?>" maxlength="50" placeholder="Event name" required>
                </input><br><br>
                <label for="description">Description</label><br>
                <textarea name="description" maxlength="255" placeholder="Short description"
                ><?= $this->event->description ?></textarea><br><br>
                <label for="date">Event Date</label>
                <input name="date"
                value="<?= $this->event->date ?>" type="date" required>
                </input><br><br>
                <label for="limit">max Participants</label>
                <input type="number" name="limit"
                value="<?= $this->event->limit ?>" type="date" min="0" max="65535" step="1" required>
                </input>
                <label>0 for unlimited</label><br><br>
                <button type="Submit">Submit</button>
            </form>
            <br><br>
            <?= $this->event->taken ?>
            <!-- display limit only if there is one -->
            <?php if($this->event->limit!=0) {?>/ <?= $this->event->limit ?> <?php } ?>
            spaces taken
            <?php if(count($this->reservations)!=0) { ?>
            <form method="POST"
                action="<?= config::get("URL"); ?>event/multiAcceptReservation/<?= $this->event->ID ?>">
            <table id="table_reservations">
                <thead>
                    <tr>
                        <td>User</td>
                        <td>accepted</td>
                        <td>code</td>
                        <td>accept/unaccept</td>
                    </tr>
                </thead>
                    <?php foreach($this->reservations as $reservation){ ?>
                        <tr>
                            <td><?= $reservation->user_name ?></td>
                            <td>
                                <input type="checkbox" name="confirm_<?= $reservation->reservationID ?>"
                                <?php if($reservation->confirmed) echo "checked" ?>>
                            </td>
                            <td><?= $reservation->code ?></td>
                            <td><a href=
                            "<?= config::get("URL"); ?>event/<?= $reservation->confirmed? "unaccept":"accept" ?>Reservation/<?= $reservation->reservationID?>/<?= $this->event->ID ?>">
                                <?= $reservation->confirmed? "Unaccept":"Accept" ?>
                            </a></td>
                        </tr>
                    
                    <?php } ?>
            </table>
                <Button type="Submit">
                    Apply Accept/Unaccept Checkboxes
                </Button>
            </form>
            <?php } ?>
        </div>
    </div>
</div>

<script>
    if(document.getElementById("table_reservations")){
        new DataTable('#table_reservations');
    }
</script>