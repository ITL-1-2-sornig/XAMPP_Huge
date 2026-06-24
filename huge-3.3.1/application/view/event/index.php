<div class="container">
    <h1>EventController/index</h1>
    <div class="box">

        <!-- echo out the system feedback (error and success messages) -->
        <?php $this->renderFeedbackMessages(); ?>

        <h3>What happens here ?</h3>
        <div>
            This controller/action/view shows all events in a calender
        </div>
            <?php 
                $dateObj   = DateTime::createFromFormat('!m', $this->month);
                $monthName = $dateObj->format('F');
                $nextMonthYear = $this->year;
                $prevMonthYear = $this->year;
                $nextMonth = $this->month +1;
                if($nextMonth==13){
                    $nextMonth = 1;
                    $nextMonthYear++;
                }
                $prevMonth = $this->month -1;
                if($prevMonth==0){
                    $prevMonth = 12;
                    $prevMonthYear--;
                }

            ?>
            <h2><?= $monthName." ".$this->year ?></h2>
            <div class="table_calender">
            <table id="table_calender">
                <thead>
                    <tr>
                        <td>Mon</td>
                        <td>Tue</td>
                        <td>Wed</td>
                        <td>Thu</td>
                        <td>Fri</td>
                        <td>Sat</td>
                        <td>Sun</td>
                    </tr>
                </thead>
                <tr>
                <?php for($i=0;$i<$this->days["1"]->wDay;$i++) { ?>
                    <td></td>
                    <?php } foreach($this->days as $day) {
                        if($day->wDay==0 && $day->day!=1) { ?>
                        </tr><tr>
                        <?php } ?>
                        <td>
                            <Button class="<?= count($day->events)>0? "btn_event":"btn_no_event" ?>"
                            onclick="showEvents(<?= $day->day ?>)">
                                <!-- Always 2 digits for symmetry -->
                                <?= str_pad($day->day, 2, "0", STR_PAD_LEFT) ?>
                            </Button>
                        </td>
                    <?php } ?>
                </tr>
            </table>
            <div>
                <form class="calender_previous_month" action="<?= config::get("URL"); ?>event/index" method="POST">
                    <input name="year" value="<?= $prevMonthYear ?>" hidden></input>
                    <input name="month" value="<?= $prevMonth ?>" hidden></input>
                    <button type="submit">previous month</button>
                </form>
                <form class="calender_next_month" action="<?= config::get("URL"); ?>event/index" method="POST">
                    <input name="year" value="<?= $nextMonthYear ?>" hidden></input>
                    <input name="month" value="<?= $nextMonth ?>" hidden></input>
                    <button type="submit">next month</button>
                </form>
            </div>
            </div>
            <br>
            <div id="event_descriptions">
            <?php foreach($this->days as $day) {
                if(count($day->events)>0) {?>
                    <div id="events_<?= $day->day ?>" class="calender_event_desc">
                    <h2>Events on <?= $day->day.".".$this->month.".".$this->year ?></h2>
                    <?php foreach($day->events as $event) { ?>
                        <div id="desc_<?= $event->ID ?>">
                            <a href="<?= config::get("URL"); ?>event/show/<?= $event->ID ?>">
                                <h2><?= $event->name ?></h2>
                            </a>
                            <div><?= $event->description ?></div>
                        </div>
                    <?php } ?>
                    </div>
            <?php }} ?>
            </div>
            <br><br>
            <form action="<?= config::get("URL"); ?>event/newEvent">
                <Button type="submit">Create new event</Button>
            </form>
    </div>
</div>

<script>
    function resetEvents(){
        const descriptions = document.getElementById("event_descriptions");
        if(!descriptions)
            return;
        const children = descriptions.children;
        console.log(children);
        [].forEach.call(children, function(child) {
            child.style.display = 'none';
        });
    }
    function showEvents(day){
        const dayDescription = document.getElementById("events_" + day);
        resetEvents()
        if(!dayDescription)
            return;
        dayDescription.style.display = 'block';
    }
</script>