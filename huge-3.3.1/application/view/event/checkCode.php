<div class="container">
    <h1>EventController/index</h1>
    <div class="box">

        <!-- echo out the system feedback (error and success messages) -->
        <?php $this->renderFeedbackMessages(); ?>

        <h3>What happens here ?</h3>
        <div>
            This controller/action/view shows you wether a
            registration code for an event is valid
        </div>
        <div>
            <br>
            <a href="<?= config::get("URL"); ?>event/show/<?= $this->eventID ?>">Back to event page</a>
        </div>
    </div>
</div>
