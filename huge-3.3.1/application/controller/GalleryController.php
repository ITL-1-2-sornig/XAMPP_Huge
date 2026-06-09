<?php

class GalleryController extends Controller
{
    /**
     * Construct this object by extending the basic Controller class
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * This method controls what happens when you move to /gallery or /gallery/index in your app.
     */
    public function index()
    {
        if(!Session::userIsLoggedIn()){
            Redirect::to("login");
        }
        $this->View->render('gallery/index', array(
            'images'=>GalleryModel::getAllImagesForUser(Session::get('user_id'))
        ));
    }

    /**
     * This method handles uploads of files
     */
    public function upload()
    {
        if(!Session::get('user_id'))
            return;
        // 1) Upload-Fehler prüfen
        if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            die('Upload failed!');
        }
        // 2) Dateigröße prüfen (max. 5 MB)
        if ($_FILES['file']['size'] > 5 * 1024 * 1024) {
            die('File to large!');
        }
        // 3) MIME-Type aus Dateiinhalt prüfen (sicherer als Endung!)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($_FILES['file']['tmp_name']);
        $accepted = ['image/jpeg', 'image/png'];
        if (!in_array($mime, $accepted)) {
            die('Incorrect Filetype!');
        }
        // 4) Sicheren Dateinamen erzeugen
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES['file']['name']));

        // 5) database entry
        $id = GalleryModel::newImage(Session::get('user_id'), $name, $_FILES['file']['size']);
        if(!$id)
            return;

        // 5) Datei AUSSERHALB Webroot speichern
        $target_dir = dirname(__DIR__) . '/uploads/' . Session::get('user_id') . '/';
        if (!file_exists($target_dir))
            mkdir($target_dir);
        $target = $target_dir . $id . '.' . pathinfo($_FILES['file']['name'])['extension'];
        if(file_exists($target)){
            return;
        }
        move_uploaded_file($_FILES['file']['tmp_name'], $target);

        $this->index();
    }

    /**
     * This method handles displaying non-public images
     */
    public function showImg($image_id, $extension, $actual_name){
        $this->accessImg($image_id, $extension, $actual_name,Session::get('user_id'), true);
    }

    /**
     * This method handles downloading non-public images
     */
    public function downloadImg($image_id, $extension, $actual_name){
        $this->accessImg($image_id, $extension, $actual_name,Session::get('user_id'), false);
    }

    /**
     * This method handles displaying non-public images
     */
    public function showImgPublic($hash){
        $imgInfo = GalleryModel::getImagePublic($hash);
        if($imgInfo){
            $split_name = explode('.', $imgInfo->name);
            $extension = end($split_name);
            $this->accessImg($imgInfo->id, $extension, $imgInfo->name, $imgInfo->uploader_id, true);
        }  
    }

    /**
     * This method handles downloading non-public images
     */
    public function downloadImgPublic($hash){
        $imgInfo = GalleryModel::getImagePublic($hash);
        if($imgInfo){
            $split_name = explode('.', $imgInfo->name);
            $extension = end($split_name);
            $this->accessImg($imgInfo->id, $extension, $imgInfo->name, $imgInfo->uploader_id, false);
        }
    }

    /**
     * This method handles accessing non-public images
     */
    private function accessImg($image_id, $extension, $actual_name, $user_id, $inline){
        $name_internal = $image_id . "." . $extension;
        $path = dirname(__DIR__) . '/uploads/' . $user_id . "/" . $name_internal;
        if (!file_exists($path)) {
            die('File not found!');
        }
        if(!$inline)
            GalleryModel::increaseDownloadCounter($image_id);
        // MIME-Type aus Dateiinhalt ermitteln
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($path);

        // HTTP-Header senden – VOR jeder anderen Ausgabe!
        header('Content-Type: ' . $mime);
        header('Content-Disposition: ' .
        ($inline? 'inline':'attachment') .
        '; filename="' . $actual_name . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);  // Dateiinhalt ausgeben
        exit;               // danach NICHTS mehr ausgeben!

    }

    public function makePublic($image_id){
        GalleryModel::makePublic($image_id);
        $this->index();
    }

    public function makeNotPublic($image_id){
        GalleryModel::makeNotPublic($image_id);
        $this->index();
    }

    public function public($hash){
        $this->View->render('gallery/shared', array(
            'image'=>GalleryModel::getImagePublic($hash)
        ));
    }
}
