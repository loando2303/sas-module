<?php

namespace Modules\SAS\Services;

use App\Http\Controllers\CommentHistoryController as CommentHistory;

use App\Models\LocationConstruction;
use App\Models\LocationInfo;
use App\Models\LocationVoid;
use Illuminate\Support\Facades\DB;
use Modules\SAS\Entities\Location;
use Modules\SAS\Repositories\LocationRepository;
use Modules\SAS\Repositories\LocationVoidMultipleRepository;
use Modules\SAS\Traits\SurveyUploadImageTrait;

class LocationService
{
    use SurveyUploadImageTrait;
    /**
     * @var LocationRepository
     */
    protected $locationRepository;
    /**
     * @var LocationVoidMultipleRepository
     */
    private $locationVoidRepository;

    public function __construct(LocationRepository $locationRepository, LocationVoidMultipleRepository $locationVoidRepository)
    {
        $this->locationRepository     = $locationRepository;
        $this->locationVoidRepository = $locationVoidRepository;
    }

    public function createLocationFromApp($areaId, $data)
    {
        $locationFields = app(Location::class)->getFillable();
        $dataLocation = collect($data)->only($locationFields)->toArray();
        $location = $this->locationRepository->create($dataLocation);
        if ($location) {
            $locationReference = "RL" . $location->id;
            $this->locationRepository->where('id', $location->id ?? 0)
                ->update(
                    [
                        'record_id'  => $location->id,
                        'area_id'    => $areaId,
                        'reference'  => $locationReference,
                        'created_by' => auth()->user()->id ?? 0
                    ]);
            return $location;
        }
    }

    public function updateLocationFromApp($id, $areaId, $data)
    {
        $location = $this->locationRepository->find($id);
        if ($location) {
            $locationFields = $location->getFillable();
            $dataLocation = collect($data)->only($locationFields)->toArray();
            if ($location->area_id != $areaId) {
                $dataLocation['is_moved'] = 1;
            }
            $dataLocation['area_id'] = $areaId ?? 0;
            $dataLocation['is_locked'] = 0;
            $location->update($dataLocation);
            if ($data['decommissioned'] === DECOMMISSION) {
                CommentHistory::storeDeccomissionHistory('decommission', 'location', $location->id, $data['decommissioned_reason'], $location->survey->reference ?? null);
            }
            if (isset($data['location_info']['comments'])) {
                CommentHistory::storeCommentHistory('location', $location->id, $data['location_info']['comments'] ?? null, $location->survey->reference ?? null);
            }
            return $location;
        }
    }

    public function unlockRegisterData($location)
    {
        $location = $this->locationRepository->where('id', $location['id'])->first();
        if (!empty($location)){
            if ($location->register){
                $location->register->update(['is_locked' => 0]);
                $location->register->items()->where('survey_id',0)->update(['is_locked' => 0]);
            }
            $location->forcedelete();
        }
    }

    public function updateOrCreateLocationInfo($location, $data)
    {
        if (empty($data)) {
            return false;
        }
        $locationInfo = app(LocationInfo::class)->getFillable();
        $dataLocationInfo = collect($data)->only($locationInfo)->toArray();
        $this->locationRepository->updateOrCreateLocationInfo($location, $dataLocationInfo);
        return $location;
    }

    public function updateOrCreateLocationVoid($location, $data)
    {
        if (empty($data)) {
            return false;
        }
        $locationVoid = app(LocationVoid::class)->getFillable();
        $dataLocationVoid = collect($data)->only($locationVoid)->toArray();
        $this->locationRepository->updateOrCreateLocationVoid($location, $dataLocationVoid);
        return $location;
    }

    public function updateOrCreateLocationConstruction($location, $data)
    {
        if (empty($data)) {
            return false;
        }
        $locationConstruction = app(LocationConstruction::class)->getFillable();
        $dataLocationConstruction = collect($data)->only($locationConstruction)->toArray();
        $this->locationRepository->updateOrCreateLocationConstruction($location, $dataLocationConstruction);
        return $location;
    }

    public function updateOrCreateLocationMultiVoids($location, &$data)
    {
        if (empty($data)) {
            return false;
        }
        $voidIds = [];
        foreach ($data as &$void) {
            if (empty($void['id'])) {
                $void['location_id'] = $location->id;
                $newVoid = $this->locationVoidRepository->create($void);
                $this->locationVoidRepository->update([
                    'record_id' => $newVoid->id ?? 0,
                    'reference' => 'LV' . $newVoid->id ?? 0
                ], $newVoid->id);
                $void['id'] = $newVoid->id ?? 0;
                $voidIds[] = $newVoid->id ?? 0;
            } else {
                $this->locationVoidRepository->update($void, $void['id']);
                $voidIds[] = $void['id'];
            }
        }
        return $voidIds;
    }

    public function syncDataFromApp(&$locations, $areas, $features, $surveyUploadImageService)
    {
        $hasOverrideFunction = in_array('override_function', $features);
        $hasMultipleVoids = in_array('void_details', $features);
        $locations = $locations->map(function ($location) use($areas, $hasOverrideFunction, $hasMultipleVoids, $surveyUploadImageService) {
            $areaId = getIdFromAppId($areas, $location['app_area_id']);
            if (!$hasOverrideFunction && $location['not_assessed'] == RELEASE_FROM_SCOPE) {
                $this->unlockRegisterData($location);
            } else {
                if (is_null($location['id']) || $location['id'] == 0) {
                    $dataLocation = $this->createLocationFromApp($areaId, $location);
                } else {
                    $dataLocation = $this->updateLocationFromApp($location['id'], $areaId, $location);
                }
                if (!empty($dataLocation)) {
                    //$locations[$key]['id'] = $dataLocation->id;
                    $location['id'] = $dataLocation->id;
                }
                $this->updateOrCreateLocationInfo($dataLocation, $location['location_info']);
                $this->updateOrCreateLocationConstruction($dataLocation, $location['location_construction']);
                $this->updateOrCreateLocationVoid($dataLocation, $location['location_void']);
                if ($hasMultipleVoids) {
                    $location['voidIds'] = $this->updateOrCreateLocationMultiVoids($dataLocation, $location['location_multi_voids']);
                    $locationVoids = collect($location['location_multi_voids']);
                    [$uploads, $dataUploaded] = $surveyUploadImageService->getUploadImages($locationVoids);
                    $this->syncFromUploaded($dataUploaded, $uploads);

                }
            }
            return $location;
        });
    }
}
