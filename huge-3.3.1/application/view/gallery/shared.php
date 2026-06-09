<div class="container">
    <h1>ProfileController/index</h1>
    <div class="box">

        <!-- echo out the system feedback (error and success messages) -->
        <?php $this->renderFeedbackMessages(); ?>

        <h3>What happens here ?</h3>
        <div>
            This controller/action/view shows a picture shared by a user
        </div>
        <div class="image-container">
            <img src="<?= config::get("URL"); ?>gallery/showImgPublic/<?= $this->image->hash ?>"
                alt="<?= $this->image->name ?>">
            <div class="image-text-container">
                <?= $this->image->name ?>
                (<a href="<?= config::get("URL"); ?>gallery/downloadImgPublic/<?= $this->image->hash ?>">
                    Download
                </a>)
            </div>
        </div>
    </div>
</div>
