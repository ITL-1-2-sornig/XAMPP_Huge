<?php

/**
 * Handles all data for viewing the gallery
 */
class GalleryModel
{
    /**
     * Add data for new image to the database
     *
     * @param $userId
     * @param $name
     * @param $size
     * @return int new image id
     */
    public static function newImage($userId, $name, $size){
        $database = DatabaseFactory::getFactory()->getConnection();
        $query = $database->prepare("INSERT INTO images (name, uploader_id, size) VALUES (:name, :uploader_id, :size)");
        $query->execute(array(
                ':name' => $name,
                ':uploader_id' => $userId,
                ':size' => $size
        ));
        return $database->lastInsertId();
    }

    /**
     * Get data for all images 
     *
     * @param $userId
     * @return array all image data for the user
     */
    public static function getAllImagesForUser($userId){
        $database = DatabaseFactory::getFactory()->getConnection();
        $query = $database->prepare("SELECT * FROM images WHERE uploader_id=:uploader_id");
        $query->execute(array(
            ':uploader_id' => $userId
        ));

        $images = array();

        foreach ($query->fetchAll() as $image) {

            // all elements of array passed to Filter::XSSFilter for XSS sanitation, have a look into
            // application/core/Filter.php for more info on how to use. Removes (possibly bad) JavaScript etc from
            // the user's values
            array_walk_recursive($image, 'Filter::XSSFilter');

            $images[$image->id] = new stdClass();
            $images[$image->id]->id = $image->id;
            $images[$image->id]->uploader = $image->uploader_id;
            $images[$image->id]->name = $image->name;
            $images[$image->id]->hash = $image->hash;
            $images[$image->id]->downloads = $image->downloads;
            $images[$image->id]->shared = $image->shared;
            $images[$image->id]->size = $image->size;
        }
        return $images;
    }

    /**
     * Make image public and create hash for sharing
     *
     * @param $imageId
     * 
     * @return string hash
     */
    public static function makePublic($imageId){
        $database = DatabaseFactory::getFactory()->getConnection();
        $queryGetName = $database->prepare("SELECT name FROM images
            WHERE id = :image_id AND uploader_id=:uploader_id");
        $queryGetName->execute(array(
                ':image_id' => $imageId,
                ':uploader_id' => Session::get('user_id')
        ));
        $name = $queryGetName->fetch()->name;
        $hash = md5($imageId . "/" . $name);
        $query = $database->prepare("UPDATE images SET shared = TRUE, hash = :hash
            WHERE id = :image_id AND uploader_id=:uploader_id");
        $query->execute(array(
                ':image_id' => $imageId,
                ':hash' => $hash,
                ':uploader_id' => Session::get('user_id')
        ));
        return $hash;
    }

    /**
     * Make image not public and remove hash for sharing
     *
     * @param $imageId
     */
    public static function makeNotPublic($imageId){
        $database = DatabaseFactory::getFactory()->getConnection();
        $query = $database->prepare('UPDATE images SET shared = FALSE, hash = ""
            WHERE id = :image_id AND uploader_id=:uploader_id');
        $query->execute(array(
                ':image_id' => $imageId,
                ':uploader_id' => Session::get('user_id')
        ));
    }

    /**
     * Increment download counter for image
     *
     * @param $imageId
     */
    public static function increaseDownloadCounter($imageId){
        $database = DatabaseFactory::getFactory()->getConnection();
        $query = $database->prepare('UPDATE images SET downloads = downloads+1 WHERE id = :image_id');
        $query->execute(array(
                ':image_id' => $imageId
        ));
    }

    /**
     * get public image info by hash
     *
     * @param $hash
     * 
     * @return stdClass of info
     */
    public static function getImagePublic($hash){
        $database = DatabaseFactory::getFactory()->getConnection();
        $query = $database->prepare('SELECT id, uploader_id, hash, name FROM images WHERE hash=:hash AND shared=true');
        $query->execute(array(
                ':hash' => $hash
        ));
        return $query->fetch();
    }
}
