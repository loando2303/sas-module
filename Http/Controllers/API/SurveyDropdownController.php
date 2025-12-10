<?php

namespace Modules\SAS\Http\Controllers\API;

use App\Http\Controllers\API\AppBaseController;
use Illuminate\Http\JsonResponse;
use Modules\SAS\Services\SurveyDropdownService;

class SurveyDropdownController extends AppBaseController
{
    /**
     * @var SurveyDropdownService
     */
    protected $surveyDropdownService;

    public function __construct(SurveyDropdownService $surveyDropdownService)
    {
        $this->surveyDropdownService = $surveyDropdownService;
    }

    /**
     * @return JsonResponse
     */
    public function getAllDropdowns(): JsonResponse
    {
        $result = $this->surveyDropdownService->getAllDropdowns();

        return $this->sendResponse($result, 'Successfully');
    }
}
