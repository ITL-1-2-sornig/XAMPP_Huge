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
     * This method controls what happens when you move to /agallery or /gallery/index in your app.
     */
    public function index()
    {
        $this->View->render('gallery/index'
        );
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

        $this->View->render('gallery/index'
        );
    }

}
