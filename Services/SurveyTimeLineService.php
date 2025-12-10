<?php

namespace Modules\SAS\Services;


use Modules\SAS\Repositories\SurveyTimeLineRepository;

class SurveyTimeLineService
{

    public function __construct(
        SurveyTimeLineRepository $surveyTimeLineRepository
    )
    {
        $this->surveyTimeLineRepository = $surveyTimeLineRepository;
    }

    public function insertSurveyTimeLine($data)
    {
        $model = $this->surveyTimeLineRepository->getModel();
        $fieldsOnly = $model->getFillable();
        $data = $data->map(function ($item) use ($fieldsOnly) {
            $itemData = collect($item)->only($fieldsOnly)->toArray();
            return $itemData;
        })->toArray();
        $this->surveyTimeLineRepository->insert($data);
    }


}
