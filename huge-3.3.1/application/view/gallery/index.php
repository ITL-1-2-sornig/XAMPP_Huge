<div class="container">
    <h1>ProfileController/index</h1>
    <div class="box">

        <!-- echo out the system feedback (error and success messages) -->
        <?php $this->renderFeedbackMessages(); ?>

        <h3>What happens here ?</h3>
        <div>
            This controller/action/view shows a gallery of all pictures uploaded by the user
        </div>
        <div>
            Upload new file
            <form method="post" enctype="multipart/form-data" action="<?= config::get("URL"); ?>gallery/upload">
                <input type="file" name="file" accept=".jpg,.png">
                <button type="submit">Submit</button>
            </form>

            <div>
                <?php foreach($this->images as $image) {
                    if($image->uploader_id == Session::get('user_id')){ //this check shouldn't be necessary, but i included it for safety?>
                    <img src="/uploads/<?= Session::get('user_id')?>/<?= $image->id ?>" >
                <?php }}>
            </div>
        </div>
    </div>
</div>
