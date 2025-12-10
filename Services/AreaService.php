<?php

namespace Modules\SAS\Services;

use App\Http\Controllers\CommentHistoryController as CommentHistory;
use Modules\SAS\Repositories\AreaRepository;

class AreaService
{
    /**
     * @var AreaRepository
     */
    protected $areaRepository;

    public function __construct(AreaRepository $areaRepository)
    {
        $this->areaRepository = $areaRepository;
    }
    public function createAreaFromApp($data)
    {
        $area = $this->areaRepository->create($data);
        if ($area) {
            $refArea = "AF" . $area->id;
            $this->areaRepository->where('id', $area->id)
                ->update(['record_id' => $area->id,
                          'reference' => $refArea,
                          'created_by' => auth()->user()->id ?? 0
                         ]);
            return $area;
        }
    }
    public function updateAreaFromApp($id, $data)
    {
        $area = $this->areaRepository->where('id',$id)->first();
        if (!empty($area)) {
            $data['is_locked'] = 0;
            $area->update($data);
            if ($data['decommissioned'] === DECOMMISSION){
                CommentHistory::storeDeccomissionHistory('decommission','area', $area->id, $data['decommissioned_reason'], $area->survey->reference ?? null);
            }
            return $area;
        }
    }
    public function unlockRegisterData($area){
        $area = $this->areaRepository->where('id',$area['id'])->first();
        if (!empty($area)){
            if ($area->register){
                $area->register->update(['is_locked' => 0]);
                $area->register->locations()->update(['is_locked' => 0]);
                $area->register->allItems()->where('survey_id', 0)->update(['is_locked' => 0]);
            }
            $area->forceDelete();
        }

    }

    public function syncDataFromApp(&$areas, $features)
    {
        $hasOverrideFunction = in_array('override_function', $features);
        $areas = $areas->map(function ($area) use ($hasOverrideFunction) {
            if (!$hasOverrideFunction && $area['not_assessed'] == RELEASE_FROM_SCOPE) {
                $this->unlockRegisterData($area);
            } else {
                if (is_null($area['id']) || $area['id'] == 0) {
                    $newArea = $this->createAreaFromApp($area);
                    if (!empty($newArea)) {
                        $area['id'] = $newArea->id;
                    }
                } else {
                    $update = $this->updateAreaFromApp($area['id'], $area);
                    if (!empty($update)) {
                        $area['id'] = $update->id;
                    }
                }
            }
            return $area;
        });
    }
}
