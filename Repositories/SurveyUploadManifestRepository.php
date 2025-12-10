<?php

namespace Modules\SAS\Repositories;

use Modules\SAS\Entities\SurveyUploadManifest;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Eloquent\BaseRepository;


class SurveyUploadManifestRepository extends BaseRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return SurveyUploadManifest::class;
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

    public function createManifest(int $surveyId = 0)
    {
        $dataManifest = [
            'survey_id'  => $surveyId,
            'created_by' => auth()->user()->id ?? 0,
            'status'     => config('sas.manifest.uploaded'),
        ];

        return $this->model->create($dataManifest);
    }

    public function updateManifest($data, $id)
    {
        return $this->model->where('id', $id)->update($data);
    }
}
