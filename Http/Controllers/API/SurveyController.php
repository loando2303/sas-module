<?php

namespace Modules\SAS\Http\Controllers\API;

use App\Http\Controllers\API\AppBaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\SAS\Http\Requests\API\ChangeSurveyOrRequest;
use Modules\SAS\Services\SurveyService;

class SurveyController extends AppBaseController
{
    /**
     * @var SurveyService
     */
    protected $surveyService;

    public function __construct(
        SurveyService $surveyService
    )
    {
        $this->surveyService = $surveyService;
    }

    public function changeSurveyor(ChangeSurveyOrRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = $request->validated();
            $result = $this->surveyService->changeSurveyor($data);
            DB::commit();
            return $this->sendResponse(['id' => $result], 'Successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e->getMessage());
        }
    }
}
