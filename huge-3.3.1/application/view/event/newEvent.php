<div class="container">
    <h1>EventController/newEvent</h1>
    <div class="box">

        <!-- echo out the system feedback (error and success messages) -->
        <?php $this->renderFeedbackMessages(); ?>

        <h3>What happens here ?</h3>
        <div>
            This controller/action/view allows the user to create a new event
        </div>
        <div>
            <br>
            <form method="POST" action="<?= config::get("URL"); ?>event/createEvent">
                <label for="name">Name</label>
                <input name="name" maxlength="50" placeholder="Event name" required>
                </input><br><br>
                <label for="description">Description</label><br>
                <textarea name="description" maxlength="255" placeholder="Short description"></textarea><br><br>
                <label for="date">Event Date</label>
                <input name="date" type="date" required>
                </input><br><br>
                <label for="limit">max Participants</label>
                <input type="number" name="limit" type="date" value="0" min="0" max="65535" step="1" required>
                </input>
                <label>0 for unlimited</label><br><br>
                <button type="Submit">Submit</button>
            </form>
        </div>
    </div>
</div>
