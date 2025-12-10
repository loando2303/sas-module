<?php

namespace Modules\SAS\Repositories;

use Modules\SAS\Entities\SurveyUploadData;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Eloquent\BaseRepository;


class SurveyUploadDataRepository extends BaseRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return SurveyUploadData::class;
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

    public function storeUploadData($survey, $allData = [])
    {
        $dataUpload = [
            'manifest_id' => $survey->manifest_id ?? 0,
            'survey_id'   => $survey->id ?? 0,
            'data'        => json_encode($allData) ?? null,
            // 'type'        => $data['type'] ?? 0,
            'status'      => config('sas.manifest.uploaded'),
        ];
        return $this->model->create($dataUpload);
    }
}
