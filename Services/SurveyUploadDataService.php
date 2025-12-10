<?php

namespace Modules\SAS\Services;


use Modules\SAS\Repositories\SurveyUploadDataRepository;

class SurveyUploadDataService
{


    /**
     * @var SurveyUploadDataRepository
     */
    private $surveyUploadDataRepository;

    public function __construct(SurveyUploadDataRepository $surveyUploadDataRepository)
    {
        $this->surveyUploadDataRepository = $surveyUploadDataRepository;
    }

    public function storeUploadData($survey, $allData)
    {
        return $this->surveyUploadDataRepository->storeUploadData($survey, $allData);
    }
}
