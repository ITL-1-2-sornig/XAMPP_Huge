<div class="container">
    <h1>GalleryController/index</h1>
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

            <div class="gallery-container">
                <?php foreach($this->images as $image) {
                    if($image->uploader == Session::get('user_id')){ //this check shouldn't be necessary, but you never know
                    $split_name = explode('.', $image->name);
                    $extension = end($split_name);
                    $imgParams = $image->id."/".$extension."/".$image->name; ?>
                    <div class="image-box">
                        <img class = "image"
                            src="<?= config::get("URL"); ?>gallery/showImg/<?= $imgParams ?>"
                            alt="<?= $image->name ?>">
                        <br>
                        <div class="image-text-container">
                        <?= $image->name ?>
                            <div>
                                (<a href="<?= config::get("URL"); ?>gallery/downloadImg/<?= $imgParams ?>">
                                    Download
                                </a>)
                            </div>
                        </div>
                        <?php if($image->shared){?>
                            Link for sharing:
                            <div><?= config::get("URL"); ?>gallery/public/<?= $image->hash ?></div>
                            <form action="<?= config::get("URL"); ?>gallery/makeNotPublic/<?= $image->id ?>">
                                <button type="submit">unshare</button>
                            </form>
                        <?php } else {?>
                            <form action="<?= config::get("URL"); ?>gallery/makePublic/<?= $image->id ?>">
                                <button type="submit">share</button>
                            </form>
                        <?php } ?>
                    </div>
                <?php }} ?>
            </div>
        </div>
    </div>
</div>
