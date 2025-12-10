<?php

namespace Modules\SAS\Services;

use Modules\SAS\Repositories\SurveyUploadImageRepository;

class SurveyUploadImageService
{
    /**
     * @var SurveyUploadImageRepository
     */
    protected $surveyUploadImageRepository;

    public function __construct(SurveyUploadImageRepository $surveyUploadImageRepository)
    {
        $this->surveyUploadImageRepository = $surveyUploadImageRepository;
    }

    public function storeUploadImage(array $data)
    {
        return $this->surveyUploadImageRepository->storeUploadImage($data);
    }

    public function getImagesByUploadImageIds($uploadImageIds)
    {
        return $this->surveyUploadImageRepository->getImagesByUploadImageIds($uploadImageIds);
    }
    public function getUploadImages($data, $field = 'upload_image_id')
    {
        $uploads = $data->filter(function ($item) use($field){
            return !is_null($item[$field]);
        });
        $uploadIds = $uploads->pluck($field);
        $dataUploaded =  $this->getImagesByUploadImageIds($uploadIds);
        return [$uploads, $dataUploaded];
    }
}
