<?php

namespace Modules\SAS\Services;


use Modules\SAS\Repositories\SurveyUploadManifestRepository;

class SurveyUploadManifestService
{
    /**
     * @var SurveyUploadManifestRepository
     */
    protected  $surveyUploadManifestRepository;

    public function __construct(SurveyUploadManifestRepository  $surveyUploadManifestRepository)
    {
         $this->surveyUploadManifestRepository =  $surveyUploadManifestRepository;
    }

    public function createManifest(int $surveyId = 0)
    {
        return  $this->surveyUploadManifestRepository->createManifest($surveyId);
    }

    public function updateManifest($data, $id) {
        return  $this->surveyUploadManifestRepository->update($data, $id);
    }
}
