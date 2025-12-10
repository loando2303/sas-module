<?php

namespace Modules\SAS\Http\Controllers\API;

use App\Http\Controllers\API\AppBaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Nshare\Events\JobWasUpdated;
use Modules\SAS\Http\Requests\API\UploadImageRequest;
use Modules\SAS\Services\AreaService;
use Modules\SAS\Services\LocationService;
use Modules\SAS\Services\ItemService;
use Modules\SAS\Services\FormatService;
use Modules\SAS\Services\RamsService;
use Modules\SAS\Services\SiteDiagramService;
use Modules\SAS\Services\SitePlanService;
use Modules\SAS\Services\SurveyService;
use Modules\SAS\Services\SurveyTimeLineService;
use Modules\SAS\Services\SurveyUploadDataService;
use Modules\SAS\Services\SurveyUploadImageService;
use Modules\SAS\Services\SurveyUploadManifestService;

class SurveyUploadController extends AppBaseController
{
    /**
     * @var SurveyService
     */
    protected $surveyService;

    /**
     * @var SurveyUploadManifestService
     */
    protected $surveyUploadManifestService;

    /**
     * @var SurveyUploadDataService
     */
    protected $surveyUploadDataService;

    /**
     * @var SurveyUploadImageService
     */
    protected $surveyUploadImageService;
    /**
     * @var AreaService
     */
    protected $areaService;
    /**
     * @var LocationService
     */
    private $locationService;
    /**
     * @var ItemService
     */
    private $itemService;
    /**
     * @var FormatService
     */
    protected $formatService;
    /**
     * @var SitePlanService
     */
    protected $sitePlanService;

    public function __construct(
        SurveyService               $surveyService,
        SurveyUploadManifestService $surveyUploadManifestService,
        SurveyUploadDataService     $surveyUploadDataService,
        SurveyUploadImageService    $surveyUploadImageService,
        AreaService                 $areaService,
        LocationService             $locationService,
        FormatService               $formatService,
        SitePlanService             $sitePlanService,
        ItemService                 $itemService,
        SiteDiagramService          $siteDiagramService,
        SurveyTimeLineService       $surveyTimeLineService,
        RamsService                 $ramsService
    )
    {
        $this->surveyService               = $surveyService;
        $this->surveyUploadManifestService = $surveyUploadManifestService;
        $this->surveyUploadDataService     = $surveyUploadDataService;
        $this->surveyUploadImageService    = $surveyUploadImageService;
        $this->areaService                 = $areaService;
        $this->locationService             = $locationService;
        $this->itemService                 = $itemService;
        $this->formatService               = $formatService;
        $this->sitePlanService             = $sitePlanService;
        $this->siteDiagramService          = $siteDiagramService;
        $this->surveyTimeLineService       = $surveyTimeLineService;
        $this->ramsService                 =  $ramsService;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadManifest(Request $request): JsonResponse
    {
        $surveyId = $request->survey_id ?? 0;
        if (!$this->surveyService->getDetail($surveyId)) {
            return $this->sendError('Not found survey.');
        }
        try {
            $surveyUploadManifest = $this->surveyUploadManifestService->createManifest($surveyId);
            return $this->sendResponse($surveyUploadManifest, 'Created new survey manifest successfully.');
        } catch (Exception $exception) {
            info('uploadManifest: ' . $exception->getMessage());
            return $this->sendError('Can not create survey Manifest.');
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadData(Request $request)
    {
        $data     = $request->all();
        $surveyId = $data['survey']['surveyDetailId'];
        $survey   = $this->surveyService->getDetail($surveyId);
        if (!$survey) {
            return $this->sendError('Not found survey.');
        }
        $modifyData = $this->formatService->format($survey, $data);
        [$updateSurvey, $propertyData, $areas, $locations, $items, $notes, $siteDiagram, $timeLine, $ram] = $modifyData;

        try {
            $uploadData = $this->surveyUploadDataService->storeUploadData($survey, $data);
        } catch (Exception $exception) {
            info("Error uploading survey" . $exception->getMessage());
            return $this->sendError('Can not create survey Upload data.');
        }
        $features = getFeaturesByClient();

		if ($survey->status != LOCKED_SURVEY_STATUS) {
			return $this->sendError('Survey does not ready for send back', 403);
		}

        try {
            DB::beginTransaction();
            $this->surveyService->updateSurvey($survey, $updateSurvey, $propertyData, $features);

            // survey photo
            $dataImagePropertyCollect = collect([$updateSurvey]);
            [$uploads, $dataUploaded] = $this->surveyUploadImageService->getUploadImages($dataImagePropertyCollect);
            $this->surveyService->syncFromUploaded($dataUploaded, $uploads);

            if ($updateSurvey['status'] == SENT_BACK_FROM_DEVICE_SURVEY_STATUS) {


                if (isset($updateSurvey['upload_signature_id']) && !empty($updateSurvey['upload_signature_id'])){
                    // signature photo
                    $dataImageSignatureCollect = collect([$updateSurvey]);
                    [$uploads, $dataUploaded] = $this->surveyUploadImageService->getUploadImages($dataImageSignatureCollect, 'upload_signature_id');
                    $this->surveyService->syncFromUploaded($dataUploaded, $uploads, 'upload_signature_id');
                }

                // areas
                $this->areaService->syncDataFromApp($areas, $features);

                // locations
                $this->locationService->syncDataFromApp($locations, $areas, $features, $this->surveyUploadImageService);
                [$locationUploads, $dataLocationUploaded] = $this->surveyUploadImageService->getUploadImages($locations);
                $this->locationService->syncFromUploaded($dataLocationUploaded, $locationUploads);

                // items
                $this->itemService->syncDataFromApp($items, $locations, $areas, $features);
                collect(['upload_image_item_id', 'upload_image_location_id','upload_image_addition_id'])->each(function ($field) use($items) {
                    [$uploads, $dataUploaded] = $this->surveyUploadImageService->getUploadImages($items, $field);
                    $this->itemService->syncFromUploaded($dataUploaded, $uploads, $field);
                });

                // site plan
                $this->sitePlanService->createSitePlan($notes);
                [$uploads, $dataUploaded] = $this->surveyUploadImageService->getUploadImages($notes);
                $this->sitePlanService->syncFromUploaded($dataUploaded, $uploads);
                // site diagram
                if (in_array("site_diagram", $features)){
                    $this->siteDiagramService->createSiteDiagram($siteDiagram);
                }
                // survey timeline
                if (in_array("survey_timeline", $features)){
                    $this->surveyTimeLineService->insertSurveyTimeLine($timeLine);
                }
                // send email no sample
                if (in_array("no_sample_email", $features)){
                    $this->surveyService->sendMailNoSample($survey);
                }
                // rams signature
                if (in_array("rams_signature", $features)){
                    [$uploads, $dataUploaded] = $this->surveyUploadImageService->getUploadImages($ram);
                    $this->sitePlanService->syncFromUploaded($dataUploaded, $uploads);
                    $this->surveyService->generateRamPdf($survey, $this->ramsService);
                }
            } else {
                $survey->surveyArea()->update(['is_locked' => 0]);
                $survey->location()->update(['is_locked' => 0]);
                $survey->item()->update(['is_locked' => 0]);
            }
            if (in_array("nshare", $features)){
                $nShareConfig ='nshare.rubixOperation';
                $nShareStatus = $updateSurvey['status'] == ABORTED_SURVEY_STATUS ? config($nShareConfig.'.aborted') : config($nShareConfig.'.siteWorkComplete');
                if ($survey->project_id) {
                    event(new JobWasUpdated($survey->project_id, $survey->id, $nShareStatus));
                }
            }

            // Update survey
            $this->surveyUploadManifestService->updateManifest(['status' => config('sas.manifest.processed')], $updateSurvey['manifest_id']);
            DB::commit();
            return $this->sendResponse(['survey' => $survey->only('id')], 'Created new survey upload data successfully.');
        } catch (Exception $exception) {
            DB::rollBack();
            $this->surveyUploadManifestService->updateManifest(['status' => config('sas.manifest.error')], $updateSurvey['manifest_id']);
            info('uploadData: ' . $exception->getMessage());
            return $this->sendError($exception->getMessage());
        }
    }

    /**
     * @param UploadImageRequest $request
     * @return JsonResponse
     */
    public function uploadImage(UploadImageRequest $request)
    {
        $data = $request->validated();
        try {
            $file = $data['file'] ?? null;
            if (empty($file) || !$file->isValid()) {
                return $this->sendError('File not exist or invalid !');
            }
            $surveyUploadImage = $this->surveyUploadImageService->storeUploadImage($data);
            return $this->sendResponse(['upload_image_id' => $surveyUploadImage->id], 'Created new survey upload image successfully.');
        } catch (Exception $exception) {
            info($exception->getMessage());
            $this->surveyUploadManifestService->updateManifest(['status' => config('sas.manifest.error')], $data['manifest_id']);
            return $this->sendError('Can not create survey Upload image.');
        }
    }

}
