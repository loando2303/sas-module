<?php

namespace Modules\SAS\Helpers;

use Exception;
use Illuminate\Support\Facades\File;
use Imagick;

class AppImageHelpers
{
    // file size expected 20kb
    public static $EXPECTED_FILE_SIZE = 20000;

    // file size expected 20kb
    public static $EXPECTED_FILE_SIZE_STRING = '20kb';
    // ~ 2MB
    public static $MAX_FILE_SIZE   = 1900000;
    public static $MAX_FILE_WIDTH  = 800;
    public static $MAX_FILE_HEIGHT = 640;

    public static $PHOTO_FIELDS = [
        [
            'name' => 'item_photo',
            'type' => ITEM_PHOTO
        ],
        [
            'name' => 'item_location_photo',
            'type' => ITEM_PHOTO_LOCATION
        ],
        [
            'name' => 'item_additional_photo',
            'type' => ITEM_PHOTO_ADDITIONAL
        ]
    ];

    /**
     * @param $itemTableAlias
     * @param $withConcat
     * @return array
     */
    public static function getQueryStringPhoto($itemTableAlias = 'i2', $withConcat = true)
    {
        $selectQuery = [];
        $groupConcat = [];
        $joinQuery   = [];
        $whereInType = [];
        $appUrl      = env('APP_URL', '');
        foreach (self::$PHOTO_FIELDS as $key => $item) {
            if ($withConcat) {
                $selectString      = "concat('" . $appUrl . "', '" . SAS_APP_THUMB_NAIL . "/',s" . ($key + 1) . ".path)";
                $groupConcatString = "concat('" . $appUrl . "', '" . SAS_APP_THUMB_NAIL . "/',GROUP_CONCAT(IF(ss.type = '" . $item["type"] . "', ss.path, null) SEPARATOR ''))";
            } else {
                $selectString      = "s" . ($key + 1) . ".path";
                $groupConcatString = "GROUP_CONCAT(IF(ss.type = '" . $item["type"] . "', ss.path, null) SEPARATOR '')";
            }
            $whereInType[] = "'".$item["type"]."'";
            $selectQuery[] = $selectString . " AS " . $item["name"] . "";
            $groupConcat[] = $groupConcatString . " AS " . $item["name"] . "";
            $joinQuery[]   = "LEFT JOIN tbl_shine_document_storage s" . ($key + 1) . " ON s" . ($key + 1) . ".object_id = $itemTableAlias.id AND s" . ($key + 1) . ".type = '" . $item["type"] . "'";
        }

        return [
            'select'              => implode(",", $selectQuery),
            'join'                => implode("", $joinQuery),
            'select_group_concat' => implode(",", $groupConcat),
            'where_in_type'       => implode(",", $whereInType),
        ];
    }

    /**
     * @param $fileSize
     * @return bool
     */
    public static function validateFileSize($fileSize)
    {
        if (!$fileSize) {
            return false;
        }
        if ($fileSize <= self::$EXPECTED_FILE_SIZE) {
            return false;
        }
        return true;
    }

    /**
     * @param $size
     * @param $ratio
     * @param $img
     * @return void
     */
    public static function cropImageToTraditionalRatio($size, $ratio, &$img)
    {
        $width  = $size['width'];
        $height = $size['height'];
        // height is smaller than width => crop by height
        if ($height / $width < $ratio) {
            $newHeight = $height;
            $newWidth  = round($height / $ratio);
        } else {
            // crop by width
            $newWidth  = $width;
            $newHeight = round($width * $ratio);
        }
        $img->cropImage($newWidth, $newHeight, ($width - $newWidth) / 2, ($height - $newHeight) / 2);
    }


    /**
     * @param $pathInfo
     * @return array|string|string[]
     */
    public static function createNewFilePath($pathInfo)
    {
        $newPath = storage_path('app') . '/' . SAS_APP_THUMB_NAIL . '/' . $pathInfo['dirname'];
        $newFile = $newPath . '/' . $pathInfo['basename'];
        // path does not exist
        if (!File::exists($newPath)) {
            mkdir($newPath, 0755, true);
        }
        $newFile = self::toJPGImage($newFile);
        return $newFile;
    }


    /**
     * @param $aliasPath
     * @return bool|void
     */
    public static function createThumbnail($aliasPath)
    {
        try {
            $path     = public_path() . '/' . $aliasPath;
            $fileSize = filesize($path);
            if (!self::validateFileSize($fileSize)) {
                // if smaller 20kb, copy it to thumbnail
                $pathInfo = pathinfo($aliasPath);
                $newFile  = self::createNewFilePath($pathInfo);
                File::copy($path, $newFile);
                return;
            }
            $ratio    = 0.8; // width/height 4/3

            $pathInfo = pathinfo($aliasPath);
            $newFile      = self::createNewFilePath($pathInfo);
            $img = new Imagick($path);

            self::autoRotateImage($img);

            $img->setImageBackgroundColor('white');
            $img->trimImage(0);
            $img->setImagePage(0, 0, 0, 0);
            $img->setImageFormat('jpg');
            $img->setImageCompressionQuality(100);
            $img->stripImage();
            $img->setOption('jpeg:extent', self::$EXPECTED_FILE_SIZE_STRING);

            $size = $img->getImageGeometry();
            self::cropImageToTraditionalRatio($size, $ratio, $img);
            $imageNewSize = $img->getImageGeometry();
            // if the image is big will resize to standard width height
            if ($fileSize >= self::$MAX_FILE_SIZE || ($imageNewSize['width'] > (self::$MAX_FILE_WIDTH + 200))) {
                $img->resizeImage(self::$MAX_FILE_WIDTH, self::$MAX_FILE_HEIGHT, Imagick::FILTER_UNDEFINED, 1);
            }

            $img->writeImage($newFile);
            $img->clear();
            $img->destroy();
            return true;
        } catch (Exception $e) {
            info("createThumbnail:".$e->getMessage());
            return false;
        }
    }
    /**
     * @param Imagick $image
     * @throws \ImagickException
     */
    public static function autoRotateImage(Imagick $image)
    {
        switch ($image->getImageOrientation()) {
            case Imagick::ORIENTATION_TOPLEFT:
                break;
            case Imagick::ORIENTATION_TOPRIGHT:
                $image->flopImage();
                break;
            case Imagick::ORIENTATION_BOTTOMRIGHT:
                $image->rotateImage("#000", 180);
                break;
            case Imagick::ORIENTATION_BOTTOMLEFT:
                $image->flopImage();
                $image->rotateImage("#000", 180);
                break;
            case Imagick::ORIENTATION_LEFTTOP:
                $image->flopImage();
                $image->rotateImage("#000", -90);
                break;
            case Imagick::ORIENTATION_RIGHTTOP:
                $image->rotateImage("#000", 90);
                break;
            case Imagick::ORIENTATION_RIGHTBOTTOM:
                $image->flopImage();
                $image->rotateImage("#000", 90);
                break;
            case Imagick::ORIENTATION_LEFTBOTTOM:
                $image->rotateImage("#000", -90);
                break;
            default: // Invalid orientation
                break;
        }
        $image->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
    }

    /**
     * @param $path
     * @return false|mixed|string
     */
    public static function getImageExtension($path){
        $explodeImage = explode('.', $path);
        $extension = end($explodeImage);
        return $extension;
    }
    /**
     * @param $path
     * @return array|string|string[]
     */
    public static function toJPGImage($path){
        return str_replace([".jpeg", ".png"], ".jpg", $path);
    }

    /**
     * @param $path
     * @return bool
     */
    public static function isNotJPGImage($extension){
        return $extension !== 'jpg';
    }


    /**
     * @param $path
     * @return bool
     */
    public static function isFileExists($path)
    {
        $appUrlThumbnailPath = env('APP_URL', '') . SAS_APP_THUMB_NAIL;
        $path                = str_replace($appUrlThumbnailPath, "", $path);
        $pathToJPG           = self::toJPGImage($path);
        return file_exists(storage_path("app/" . $path)) && file_exists(public_path(SAS_APP_THUMB_NAIL . "/" . $pathToJPG));
    }

    /**
     * @param $item
     * @return void
     */
    public static function modifyItemImage(&$item){
        foreach (self::$PHOTO_FIELDS as $key => $field){
            if (!self::isFileExists($item->{$field['name']})){
                $item->{$field['name']} = null;
            }

            $extension = self::getImageExtension($item->{$field['name']});
            if (self::isNotJPGImage($extension)){
                $item->{$field['name']} = self::toJPGImage($item->{$field['name']});
                if (!isset($item->raw_image)){
                    $item->raw_image = [];
                }
                $item->raw_image[$field['name']] = $extension;
            }
        }
    }


}
