<?php

namespace Modules\SAS\Services;


use App\Models\ShineDocumentStorage;
use Modules\SAS\Repositories\SitePlanDocumentRepository;
use Modules\SAS\Traits\SurveyUploadImageTrait;

class SitePlanService
{
    use SurveyUploadImageTrait;
    /**
     * @var SitePlanDocumentRepository
     */
    protected $sitePlanDocumentRepository;

    public function __construct(SitePlanDocumentRepository $sitePlanDocumentRepository)
    {
        $this->sitePlanDocumentRepository = $sitePlanDocumentRepository;
    }

    public function createSitePlan(&$notes)
    {
        $model      = $this->sitePlanDocumentRepository->getModel();
        $fieldsOnly = $model->getFillable();
        $notes      = $notes->map(function ($note) use ($fieldsOnly) {
            $sitePlan        = collect($note)->only($fieldsOnly)->toArray();
            $plan            = $this->sitePlanDocumentRepository->create($sitePlan);
            $plan->reference = "PP" . $plan->id;
            $plan->save();
            $note['id'] = $plan->id;
            return $note;
        });
    }



}
