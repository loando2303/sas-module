<?php

namespace Modules\SAS\Traits;

use App\Models\ShineDocumentStorage;
use Modules\SAS\Entities\RamsPublishedSignature;
use Imagick;
use Modules\SAS\Helpers\AppImageHelpers;

trait SurveyUploadImageTrait
{
    public function syncFromUploaded($dataUploaded, $uploads, $field = 'upload_image_id')
    {
        $dataUploaded->each(function ($uploaded) use ($uploads, $field) {
            $upload = $uploads->where($field, $uploaded->id)->first();

            if (!empty($upload) && isset($upload['id'])) {
                if ($uploaded->image_type === 'ram_signature') {
                    $publishedId = $upload['pdf_id'];
                    RamsPublishedSignature::create(
                        [
                            'published_id'   => $publishedId,
                            'type'           => 'survey',
                            'path'           => $uploaded->path,
                            'file_name'      => $uploaded->file_name,
                            'mime'           => $uploaded->mine,
                            'size'           => $uploaded->size,
                            'app_created_at' => now(),
                            'user_id'        => auth()->user()->id ?? 0
                        ]
                    );
                } else {
                    ShineDocumentStorage::updateOrCreate(
                        [
                            'object_id' => $upload['id'],
                            'type'      => $uploaded->image_type
                        ],
                        [
                            'path'      => $uploaded->path,
                            'file_name' => $uploaded->file_name,
                            'mime'      => $uploaded->mime,
                            'size'      => $uploaded->size,
                            'addedBy'   => auth()->user()->id ?? 0,
                            'addedDate' => now()->timestamp,
                        ]);
                }
                $this->fixImageOrientation(storage_path() . '/app/' . $uploaded->path);
                $this->createThumbnail($uploaded->path, true);
                if (in_array($uploaded->image_type, [
                    ITEM_PHOTO,
                    ITEM_PHOTO_LOCATION,
                    ITEM_PHOTO_ADDITIONAL
                ])) {
                    // TODO check if domain not exist this help so need patch it
                    AppImageHelpers::createThumbnail($uploaded->path);
                }

            }
        });
    }

    public function fixImageOrientation($image)
    {
        if (!file_exists($image)) {
            return false;
        }
        $img = new Imagick($image);
        // rotate image
        $this->autoRotateImage($img);

        // remove orientation
        $img->stripImage();
        $img->writeImage($image);
        $img->clear();
        $img->destroy();

        return true;
    }

    public function autoRotateImage($image)
    {
        $orientation = $image->getImageOrientation();

        switch ($orientation) {
            case Imagick::ORIENTATION_BOTTOMRIGHT:
                $image->rotateimage("#000", 180); // rotate 180 degrees
                break;

            case Imagick::ORIENTATION_RIGHTTOP:
                $image->rotateimage("#000", 90); // rotate 90 degrees CW
                break;

            case Imagick::ORIENTATION_LEFTBOTTOM:
                $image->rotateimage("#000", -90); // rotate 90 degrees CCW
                break;
        }

        // Now that it's auto-rotated, make sure the EXIF data is correct in case the EXIF gets saved with the image!
        $image->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
    }

    public static function createThumbnail($alias_path, $is_crop = true)
    {
        $ratio = 0.8; // height/width 3/4
        $quality = 90;
        $path = public_path() . '/' . $alias_path;
        $path_info = pathinfo($alias_path);

        try {
            $img = new Imagick($path);
            $img->trimImage(0);
            $img->setImagePage(0, 0, 0, 0);
            $size = $img->getImageGeometry();
            $new_path = storage_path('app') . '/' . THUMB_NAIL . '/' . $path_info['dirname'];
            $new_file = $new_path . '/' . $path_info['basename'];
            if (!\File::exists($new_file)) {
                // path does not exist
               return false;
            }
            if (!\File::exists($new_path)) {
                // path does not exist
                mkdir($new_path, 0755, true);
            }
            if ($is_crop) {
                $width = $size['width'];
                $height = $size['height'];
                $new_w = $new_h = NULL;
                if ($height / $width < $ratio) {
                    $new_h = $height;
                    $new_w = round($height / $ratio);
                    // height is smaller than width => crop by height
                } else {
                    // crop by width
                    $new_w = $width;
                    $new_h = round($width * $ratio);
                }
                $img->cropImage($new_w, $new_h, ($width - $new_w) / 2, ($height - $new_h) / 2);
            }
            if (filesize($path) > 1024) {
                $img->setImageCompressionQuality(25);
            }
            $img->writeImage($new_file);
            return true;
        } catch (\Exception $e) {
            info('createThumbnail error: '.$e->getMessage());
            return false;
        }
        return false;
    }

}
