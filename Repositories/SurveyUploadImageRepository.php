<?php

namespace Modules\SAS\Repositories;

use App\Helpers\CommonHelpers;
use Illuminate\Support\Facades\Storage;
use Modules\SAS\Entities\SurveyUploadImage;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Eloquent\BaseRepository;

class SurveyUploadImageRepository extends BaseRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return SurveyUploadImage::class;
    }

    public function getEntity()
    {
        return $this->model;
    }

    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }

    public function storeUploadImage(array $data = [])
    {
        $path = CommonHelpers::getFileStoragePath($data['survey_id'], $data['image_type'], 0, true);
        $flag = false;
        $number = 0;
        while (!$flag && $number < NUMBER_RETRY_CREATE_FOLDER && !file_exists(storage_path('app') . '/' . $path)) {
            $number++;
            $flag = CommonHelpers::createFolder($flag, $path, $number);
        }
        if (isset($data['file'])) {
            Storage::disk('local')->put($path, $data['file']);
        }
        $dataUpload = [
            'manifest_id' => $data['manifest_id'] ?? 0,
            'survey_id'   => $data['survey_id'] ?? 0,
            'image_type'  => $data['image_type'] ?? null,
            'reference'   => $data['reference'] ?? null,
            'file_name'   => isset($data['file']) ? $data['file']->getClientOriginalName() : null,
            'path'        => isset($data['file']) ? $path . $data['file']->hashName() : null,
            'mime'        => isset($data['file']) ? $data['file']->getClientMimeType() : null,
            'size'        => isset($data['file']) ? $data['file']->getSize() : null,
        ];
        return $this->model->create($dataUpload);
    }

    public function getImagesByUploadImageIds($uploadImageIds)
    {
        return $this->model->whereIn('id', $uploadImageIds)->get();
    }
}
