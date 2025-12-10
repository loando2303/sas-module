<?php

namespace Modules\SAS\Services;

use App\Http\Controllers\CommentHistoryController as CommentHistory;
use App\Jobs\SendApprovalEmail;
use App\Jobs\SendSampleEmail;
use Illuminate\Support\Facades\Schema;
use Modules\SAS\Repositories\SurveyRepository;
use Modules\SAS\Traits\SurveyUploadImageTrait;

class SurveyService
{
    use SurveyUploadImageTrait;
    /**
     * @var SurveyRepository
     */
    protected $surveyRepository;

    public function __construct(SurveyRepository $surveyRepository)
    {
        $this->surveyRepository = $surveyRepository;
    }

    public function getDetail($id, $with = [])
    {
        return $this->surveyRepository->getDetail($id, $with);
    }

    public function updateSurvey($survey, $dataSurvey, $propertyData, $features)
    {
        info('start updateSurvey');
        $surveyFields = $survey->getFillable();
        $scopeChange = $dataSurvey['scope_change'];
        $customer = $dataSurvey['customer'];

        $dataSurvey = collect($dataSurvey)->only($surveyFields)->toArray();

        $this->surveyRepository->update($dataSurvey, $survey->id);
        $survey->surveyInfo()->update(['property_data' => json_encode($propertyData)]);

        if (in_array("customer", $features)){
            $customer = json_encode($customer);
            if (empty($customer)){
                $customer = json_encode(
                    [
                        'satisfaction' => 0,
                        'feedback' => "",
                    ]
                );
            }
            $survey->surveyInfo()->update(['customer' => $customer]);
        }

        if (in_array("scope_change", $features)){
            $survey->surveyInfo()->update(['scope_change' => $scopeChange]);
        }
        $survey->surveyDate()->update(['sent_back_date' => time()]);
        if ($propertyData['sizeComments']) {
            // store comment history
            CommentHistory::storeCommentHistory('property', $survey->property_id, $propertyData['sizeComments'], $survey->reference ?? null);
        }
        info('end updateSurvey');
    }


    public function sendMailNoSample($survey)
    {
        $relations = [
            'property',
            'property.propertyInfo',
            'client',
            'item',
            'item.sample',
        ];
        $survey->load($relations);
        if ($survey->status == SENT_BACK_FROM_DEVICE_SURVEY_STATUS) {
            \Queue::pushOn(SURVEY_APPROVAL_EMAIL_QUEUE, new SendApprovalEmail($survey, SURVEY_SENT_TO_WEB_PORTAL_EMAILTYPE));
        }

        $item_updated = $survey->item ?? [];
        $sample = 0;
        foreach ($item_updated as $item) {
            if (!empty($item->sample) && $item->decommissioned != 1) {
                $sample++;
            }
        }
        if ($sample == 0 && $survey->client_id == 1) {

            $dataSampleEmail = [
                'survey_id'        => $survey_id ?? '',
                'client_id'        => $survey->property->client_id ?? '',
                "survey_reference" => $survey->reference ?? '',
                "contractor_name"  => $survey->client->name ?? '',
                "block_reference"  => $survey->property->pblock ?? '',
                "property_uprn"    => $survey->property->reference ?? '',
                "property_name"    => $survey->property->name ?? '',
                "postcode"         => $survey->property->propertyInfo->postcode ?? '',
                "domain"           => \Config::get('app.url')
            ];
            if (isset($survey->cad_tech_id) and $survey->cad_tech_id != NULL) {
                $cad_tech_id = $survey->cad_tech_id;

                $dataSampleEmail['subject'] = 'No Samples Recorded within the Survey';
                \Queue::pushOn(SURVEY_APPROVAL_EMAIL_QUEUE, new SendSampleEmail($dataSampleEmail, EMAIL_NO_SAMPLES_RECORDED_QUEUE, $cad_tech_id));
                $userFullName = auth()->user()->full_name ?? '';
                $comment = $userFullName . " send no sample email from mobile on survey " . $survey->reference;
                \CommonHelpers::logAudit(SURVEY_TYPE, $survey->id, SEND_EMAIL_TYPE, $survey->reference, $survey->property_id, $comment, 0, $survey->property_id);
            }
        }
    }
    public function generateRamPdf($survey, RamsService $ramsService)
    {
        if (!empty($survey->ramHasSignature)) {
            foreach ($survey->ramHasSignature as $ram) {
                $ramsService->generateRamsSurveyPDF($ram);
            }
        }
    }

    public function changeSurveyor($data)
    {
        @[
            'survey_id'   => $surveyId,
            'surveyor_id' => $surveyorId,
        ] = $data;
        $survey = $this->surveyRepository->getDetail($surveyId, []);
        $survey->surveyor_id = $surveyorId;
        $survey->save();
        $userFullName = auth()->user()->full_name ?? '';
        $comment = $userFullName . "app change surveyor on survey " . $survey->reference. " to " . $surveyorId;
        \CommonHelpers::logAudit(SURVEY_TYPE, $survey->id, 'edit', $survey->reference, $survey->property_id, $comment, 0, $survey->property_id);
        return $survey->id;
    }


}
