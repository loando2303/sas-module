<?php

namespace Modules\SAS\Services;

use App\Http\Controllers\CommentHistoryController as CommentHistory;
use App\Jobs\SendClientEmail;
use App\Models\DropdownItem\MaterialAssessmentRisk;
use App\Models\DropdownItem\PriorityAssessmentRisk;
use App\Models\DropdownItemValue\AccessibilityVulnerabilityValue;
use App\Models\DropdownItemValue\ActionRecommendationValue;
use App\Models\DropdownItemValue\AdditionalInformationValue;
use App\Models\DropdownItemValue\AsbestosTypeValue;
use App\Models\DropdownItemValue\ExtentValue;
use App\Models\DropdownItemValue\ItemNoAccessValue;
use App\Models\DropdownItemValue\LicensedNonLicensedValue;
use App\Models\DropdownItemValue\MaterialAssessmentRiskValue;
use App\Models\DropdownItemValue\NoACMCommentsValue;
use App\Models\DropdownItemValue\PriorityAssessmentRiskValue;
use App\Models\DropdownItemValue\ProductDebrisTypeValue;
use App\Models\DropdownItemValue\SampleCommentValue;
use App\Models\DropdownItemValue\SampleIdValue;
use App\Models\DropdownItemValue\SpecificLocationValue;
use App\Models\DropdownItemValue\UnableToSampleValue;
use App\Models\Item;
use App\Models\ItemInfo;
use App\Models\Sample;
use Illuminate\Support\Facades\DB;
use Modules\SAS\Entities\SubSampleIdValue;
use Modules\SAS\Repositories\ItemRepository;
use Modules\SAS\Traits\SurveyUploadImageTrait;

class ItemService
{
    use SurveyUploadImageTrait;

    private $dataSample = [];

    /**
     * @var ItemRepository
     */
    protected $itemRepository;

    public function __construct(ItemRepository $itemRepository)
    {
        $this->itemRepository = $itemRepository;
    }

    public function createItemFromApp($areaId, $locationId, $data)
    {
        $itemFields = app(Item::class)->getFillable();
        $dataItem = collect($data)->only($itemFields)->toArray();
        $item = $this->itemRepository->create($dataItem);
        if ($item) {
            $itemReference = "IN" . $item->id;
            $this->itemRepository->where('id', $item->id ?? 0)
                ->update(
                    [
                        'record_id'   => $item->id,
                        'area_id'     => $areaId,
                        'location_id' => $locationId,
                        'reference'   => $itemReference,
                        'created_by'  => auth()->user()->id ?? 0
                    ]);
            return $this->itemRepository->find($item->id ?? 0);
        }
    }

    public function updateItemFromApp($id, $areaId, $locationId, $data)
    {
        $item = $this->itemRepository->find($id);
        if ($item) {
            $itemFields = $item->getFillable();
            $dataItem = collect($data)->only($itemFields)->toArray();
            if ($item->location_id != $locationId) {
                $dataItem['is_moved'] = 1;
            }
            $dataItem['area_id'] = $areaId ?? 0;
            $dataItem['location_id'] = $locationId ?? 0;
            $dataItem['is_locked'] = 0;
            $item->update($dataItem);
            if ($data['decommissioned'] === DECOMMISSION) {
                CommentHistory::storeDeccomissionHistory('decommission', 'item', $item->id, $data['decommissioned_reason'], $item->survey->reference ?? null);
            }
            if (isset($data['item_info']['comment'])) {
                CommentHistory::storeCommentHistory('item', $item->id, $data['item_info']['comment'], $item->survey->reference ?? null);
            }
            return $item;
        }
    }

    public function unlockRegisterData($item)
    {
        $item = $this->itemRepository->where("id", $item['id'])->first();
        if (!empty($item)) {
            $item->register()->update(['is_locked' => 0]);
            $item->forcedelete();
        }
    }

    public function updateItemNCO($item)
    {
        $reason = $item['not_assessed_reason'] ?? null;
        $item = $this->itemRepository->find($item['id']);
        $item->update(['nco' => 1, 'nco_reason' => $statusReason ?? 0, 'is_locked' => 0]);
        \CommentHistory::storeDeccomissionHistory('nco', 'item', $item->id, $reason, $item->survey->reference ?? null, $item->survey->surveyor_id ?? 0);
    }

    public function updateOrCreateItemInfo($item, $data)
    {
        if (empty($data)) {
            return false;
        }
        $itemInfo = app(ItemInfo::class)->getFillable();
        $dataItemInfo = collect($data)->only($itemInfo)->toArray();
        $this->itemRepository->updateOrCreateItemInfo($item, $dataItemInfo);
        return $item;
    }

    public function handleSample($item, $data, $hasSampleSequence)
    {
        if (is_null($data)) {
            return 0;
        }
        $sampleId = 0;
        if (isset($data->originalSampleID) && !is_null($data->originalSampleID) && ($data->originalSampleID != '') && ($data->originalSampleID > 0)) {
            $oldSample = Sample::find($data->originalSampleID);
            if (!is_null($oldSample)) {
                return $oldSample->id;
            }
        }
        // create new sample
        if (isset($data->reference) && !is_null($data->reference) && ($data->reference != '')) {
            if ($data->isOS == 1) {
                return $this->createNewSample($data, $item['record_id'] ?? 0);
            } else {
                // create new sample for presumption, inaccessible items
                if ($hasSampleSequence && !empty($data->type) && in_array($data->type, [SAMPLE_TYPE_ACCESSIBLE_PRESUMPTION, SAMPLE_TYPE_INACCESSIBLE])) {
                    return $this->createNewSample($data, $item['record_id'] ?? 0);
                }

                if (!empty($this->dataSample)) {
                    foreach ($this->dataSample as $key => $value) {
                        if ($value == $data->reference) {
                            return $key;
                        }
                    }
                }
            }
        }
        return $sampleId;
    }

    public function createNewSample($data, $itemRecordId = 0)
    {
        if (is_null($data)) {
            return 0;
        }

        if (!empty($data->reference)) {
            preg_match('{\d+$}', $data->reference, $match);
            $sequenceOrder = intval(end($match));
        }
        $dataSample = [
            'type'             => $data->type ?? SAMPLE_TYPE_ACCESSIBLE_ORIGINAL,
            'description'      => $data->reference,
            'comment_key'      => (int)$data->comment == 0 ? 522 : (int)$data->comment,
            'comment_other'    => $data->commentOther ?? 522,
            'original_item_id' => $itemRecordId,
            'sequence_order'   => $sequenceOrder ?? 0
        ];
        $sampleFields = app(Sample::class)->getFillable();
        $sample = collect($dataSample)->only($sampleFields)->toArray();
        $sampleCreate = Sample::create($sample);
        Sample::where('id', $sampleCreate->id)->update(['reference' => 'SR' . $sampleCreate->id]);

        $this->dataSample[$sampleCreate->id] = $data->reference;

        return $sampleCreate->id;
    }

    public function updateItemDetails($itemId, $data)
    {
        $this->insertDropdownValue($itemId, SPECIFIC_LOCATION_ID, 0, $data['specificLocation'] ?? null, $data['specificLocationOther'] ?? null);
        $this->insertDropdownValue($itemId, PRODUCT_DEBRIS_TYPE_ID, 0, $data['productDebris'] ?? null, $data['productDebrisOtherAsbestosOther'] ?? null);
        $this->insertDropdownValue($itemId, ASBESTOS_TYPE_ID, 0, $data['asbestosType'] ?? null, $data['asbestosTypeOther'] ?? null);
        $this->insertDropdownValue($itemId, EXTENT_ID, 0, $data['measurement'] ?? null);
        $this->insertDropdownValue($itemId, ITEM_NO_ACCESS_ID, 0, $data['inaccessItemReason'] ?? null, $data['inaccessItemReasonOther'] ?? null);
    }

    public function updateItemMasRisk($item, $data, $hasIsMasOverride)
    {
        $this->insertDropdownValue($item->id, MATERIAL_ASSESSMENT_RISK_ID, ASSESSMENT_TYPE_KEY, (int)($data['productDebrisType'] ?? null));
        $this->insertDropdownValue($item->id, MATERIAL_ASSESSMENT_RISK_ID, ASSESSMENT_DAMAGE_KEY, (int)($data['damageDeterioration'] ?? null));
        $this->insertDropdownValue($item->id, MATERIAL_ASSESSMENT_RISK_ID, ASSESSMENT_TREATMENT_KEY, (int)($data['surfaceTreatment'] ?? null));
        $this->insertDropdownValue($item->id, MATERIAL_ASSESSMENT_RISK_ID, ASSESSMENT_ASBESTOS_KEY, (int)($data['asbestosType'] ?? null));
        //send high risk email
        if ($data['damageDeterioration'] == 608) {
            \Queue::laterOn(CLIENT_EMAIL_QUEUE, 30, new SendClientEmail($item->property_id ?? 0, HIGH_RISK_ITEM_EMAILTYPE, $item->survey_id ?? 0));
        }
        $checkMasOverride = true;
        if ($hasIsMasOverride) {
            $checkMasOverride = $item->is_mas_override == ITEM_NOT_MATERIAL_SCORE_OVERRIDE;
        }
        // count total mas
        if ($item->itemType == 'inaccessibleLimited' && $checkMasOverride) {
            if ($item->item_removed == 1){
                $totalMasRisk = 0;
            }else{
                $totalMasRisk = 12;
            }
        } else {
            $totalMasRisk = $this->getMasScore($data['productDebrisType'] ?? null) + $this->getMasScore($data['damageDeterioration'] ?? null) + $this->getMasScore($data['surfaceTreatment'] ?? null) + $this->getMasScore($data['asbestosType'] ?? null);
        }

        return $totalMasRisk;
    }

    public function updateItemPasRisk($item, $data)
    {
        $this->insertDropdownValue($item->id, PRIORITY_ASSESSMENT_RISK_ID, PRIORITY_ASSESSMENT_ACTIVITY_PRIMARY_KEY, (int)($data['primary'] ?? null));
        $this->insertDropdownValue($item->id, PRIORITY_ASSESSMENT_RISK_ID, PRIORITY_ASSESSMENT_ACTIVITY_SECONDARY_KEY, (int)($data['secondary'] ?? null));
        $this->insertDropdownValue($item->id, PRIORITY_ASSESSMENT_RISK_ID, PRIORITY_ASSESSMENT_LOCATION_KEY, (int)($data['location'] ?? null));
        $this->insertDropdownValue($item->id, PRIORITY_ASSESSMENT_RISK_ID, PRIORITY_ASSESSMENT_ACCESSIBILITY_KEY, (int)($data['accessibility'] ?? null));
        $this->insertDropdownValue($item->id, PRIORITY_ASSESSMENT_RISK_ID, PRIORITY_ASSESSMENT_EXTENT_KEY, (int)($data['extentAmount'] ?? null));
        $this->insertDropdownValue($item->id, PRIORITY_ASSESSMENT_RISK_ID, PRIORITY_ASSESSMENT_OCCUPANTS_KEY, (int)($data['number'] ?? null));
        $this->insertDropdownValue($item->id, PRIORITY_ASSESSMENT_RISK_ID, PRIORITY_ASSESSMENT_FREQUENCY_OF_USE_KEY, (int)($data['frequency'] ?? null));
        $this->insertDropdownValue($item->id, PRIORITY_ASSESSMENT_RISK_ID, PRIORITY_ASSESSMENT_TIME_IN_AREA_KEY, (int)($data['averageTime'] ?? null));
        $this->insertDropdownValue($item->id, PRIORITY_ASSESSMENT_RISK_ID, PRIORITY_ASSESSMENT_TYPE_OF_ACTIVITY_KEY, (int)($data['type'] ?? null));
        $this->insertDropdownValue($item->id, PRIORITY_ASSESSMENT_RISK_ID, PRIORITY_ASSESSMENT_FREQUENCY_OF_ACTIVITY_KEY, (int)($data['frequencyActivity'] ?? null));

        //count total pas
        $totalPasRisk = round(($this->getPasScore($data['primary'] ?? null) + $this->getPasScore($data['secondary'] ?? null)) / 2);
        $totalPasRisk += round(($this->getPasScore($data['location'] ?? null) + $this->getPasScore($data['accessibility'] ?? null) + $this->getPasScore($data['extentAmount'] ?? null)) / 3);
        $totalPasRisk += round(($this->getPasScore($data['number'] ?? null) + $this->getPasScore($data['frequency'] ?? null) + $this->getPasScore($data['averageTime'] ?? null)) / 3);
        $totalPasRisk += round(($this->getPasScore($data['type'] ?? null) + $this->getPasScore($data['frequencyActivity'] ?? null)) / 2);

        return $totalPasRisk;
    }

    public function updateItemTotalRisk($item, $totalMasRisk = 0, $totalPasRisk = 0)
    {
        if (@$item->state == ITEM_NOACM_STATE) {
            $totalMasRisk = 0;
            $totalPasRisk = 0;
            $totalRisk = 0;
        } else {
            $totalRisk = $totalMasRisk + $totalPasRisk;
        }
        $this->itemRepository->update(['total_mas_risk' => $totalMasRisk, 'total_pas_risk' => $totalPasRisk, 'total_risk' => $totalRisk], $item->id ?? 0);
    }

    public function getMasScore($mas_id)
    {
        $mas_id = (int)$mas_id;
        $data = MaterialAssessmentRisk::find($mas_id);
        return is_null($data) ? 0 : $data->score;
    }

    public function getPasScore($pas_id)
    {
        $pas_id = (int)$pas_id;
        $data = PriorityAssessmentRisk::find($pas_id);
        return is_null($data) ? 0 : $data->score;
    }

    public function syncDataFromApp(&$items, $locations, $areas, $features)
    {
        $hasOverrideFunction = in_array('override_function', $features);
        $hasSampleSequence = in_array('sample_sequence', $features);
        $hasIsMasOverride = in_array('is_mas_override', $features);
        $items = $items->map(function ($item) use ($areas, $locations, $hasOverrideFunction, $hasSampleSequence, $hasIsMasOverride) {
            $areaId = getIdFromAppId($areas, $item['app_area_id']);
            $locationId = getIdFromAppId($locations, $item['app_location_id']);
            if (!$hasOverrideFunction && $item['not_assessed'] == RELEASE_FROM_SCOPE) {
                $this->unlockRegisterData($item);
            } elseif ($item['not_assessed'] == SAS_NCO) {
                $this->updateItemNCO($item);
            } else {
                if (is_null($item['id']) || $item['id'] == 0) {
                    $dataItem = $this->createItemFromApp($areaId, $locationId, $item);
                } else {
                    $dataItem = $this->updateItemFromApp($item['id'], $areaId, $locationId, $item);
                }
                if (!empty($dataItem)) {
                    $item['id'] = $dataItem->id;
                }
                $this->updateOrCreateItemInfo($dataItem, $item['item_info']);
                $sample = $this->handleSample($dataItem, $item['sample'], $hasSampleSequence);
                $this->insertDropdownValue($dataItem->id, SAMPLE_ID, 0, $sample);
                $this->updateItemDetails($dataItem->id, $item['item_details']);
                $dataItem->itemType = $item['itemType'] ?? '';
                $dataItem->is_mas_override = $item['is_mas_override'] ?? null;
                $totalMasRisk = $this->updateItemMasRisk($dataItem, $item['item_mas'], $hasIsMasOverride);
                $totalPasRisk = $this->updateItemPasRisk($dataItem, $item['item_pas']);
                $this->updateItemTotalRisk($dataItem, $totalMasRisk, $totalPasRisk);
                $this->insertDropdownValue($dataItem->id, ACTIONS_RECOMMENDATIONS_ID, 0, $item['item_action_recommendations']['actionsRecommendations'] ?? null, $item['item_action_recommendations']['actionsRecommendationsOther'] ?? null);
            }
            return $item;
        });
    }

    public function insertDropdownValue($itemId, $dropdownItemId, $dropdownDataItemParentId, $dropdownDataItemId, $other = null)
    {
        // todo maybe sai so với code cũ nếu sai thì bỏ đoạn này đi thay bằng đoạn dưới nhé :D
        if (is_array($dropdownDataItemId)) {
            $dropdownDataItemId = end($dropdownDataItemId);
        }
        if (is_string($dropdownDataItemId) && strpos($dropdownDataItemId, ',') === false) {
            $dropdownDataItemId = (int)$dropdownDataItemId;
        }

        // === code cũ ====
//        if (is_array($dropdown_data_item_id)) {
//            if ($dropdown_item_id == SPECIFIC_LOCATION_ID) {
//                if (strpos($dropdown_data_item_id, ',') !== false) {
//                    $dropdown_data_item_id = $dropdown_data_item_id;
//                } else {
//                    $dropdown_data_item_id = (int)$dropdown_data_item_id;
//                }
//            } else {
//                $dropdown_data_item_id = end($dropdown_data_item_id);
//            }
//        }
//
//        if ($dropdown_item_id == SPECIFIC_LOCATION_ID) {
//            if (strpos($dropdown_data_item_id, ',') !== false) {
//                $dropdown_data_item_id = $dropdown_data_item_id;
//            } else {
//                $dropdown_data_item_id = (int)$dropdown_data_item_id;
//            }
//        }

        if (is_array($other)) {
            $other = implode(",", $other);
        }

        $dataDropdownValue = [
            'dropdown_item_id'             => $dropdownItemId,
            'dropdown_data_item_parent_id' => $dropdownDataItemParentId,
            'dropdown_data_item_id'        => $dropdownDataItemId,
            'dropdown_other'               => $other
        ];

        switch ($dropdownItemId) {
            case PRODUCT_DEBRIS_TYPE_ID:
                ProductDebrisTypeValue::updateOrCreate(['item_id' => $itemId], $dataDropdownValue);
                break;
            case EXTENT_ID:
                ExtentValue::updateOrCreate(['item_id' => $itemId], $dataDropdownValue);
                break;
            case ASBESTOS_TYPE_ID:
                AsbestosTypeValue::updateOrCreate(['item_id' => $itemId], $dataDropdownValue);
                break;
            case ACTIONS_RECOMMENDATIONS_ID:
                ActionRecommendationValue::updateOrCreate(['item_id' => $itemId], $dataDropdownValue);
                break;
            case ADDITIONAL_INFORMATION_ID:
                AdditionalInformationValue::updateOrCreate(['item_id' => $itemId], $dataDropdownValue);
                break;
            case SAMPLE_COMMENTS_ID:
                SampleCommentValue::updateOrCreate(['item_id' => $itemId], $dataDropdownValue);
                break;
            case SPECIFIC_LOCATION_ID:
                SpecificLocationValue::updateOrCreate(['item_id' => $itemId], $dataDropdownValue);
                break;
            case ACCESSIBILITY_VULNERABILITY_ID:
                AccessibilityVulnerabilityValue::updateOrCreate(['item_id' => $itemId], $dataDropdownValue);
                break;
            case LICENSED_NONLICENSED_ID:
                LicensedNonLicensedValue::updateOrCreate(['item_id' => $itemId], $dataDropdownValue);
                break;
            case UNABLE_TO_SAMPLE_ID:
                UnableToSampleValue::updateOrCreate(['item_id' => $itemId], $dataDropdownValue);
                break;
            case ITEM_NO_ACCESS_ID:
                $dataDropdownValue['dropdown_data_item_id'] = ($dataDropdownValue['dropdown_data_item_id'] == '') ? 0 : $dataDropdownValue['dropdown_data_item_id'];

                ItemNoAccessValue::updateOrCreate(['item_id' => $itemId], $dataDropdownValue);
                break;
            case PRIORITY_ASSESSMENT_RISK_ID:
                PriorityAssessmentRiskValue::updateOrCreate(['item_id' => $itemId, 'dropdown_data_item_parent_id' => $dropdownDataItemParentId,], $dataDropdownValue);
                break;
            case NO_ACM_COMMENTS_ID:
                NoACMCommentsValue::updateOrCreate(['item_id' => $itemId], $dataDropdownValue);
                break;
            case MATERIAL_ASSESSMENT_RISK_ID:
                MaterialAssessmentRiskValue::updateOrCreate(['item_id' => $itemId, 'dropdown_data_item_parent_id' => $dropdownDataItemParentId,], $dataDropdownValue);
                break;
            case SAMPLE_ID:
                SampleIdValue::updateOrCreate(['item_id' => $itemId], $dataDropdownValue);
                break;
            case SAS_SUB_SAMPLE_ID:
                SubSampleIdValue::updateOrCreate(['item_id' => $itemId], $dataDropdownValue);
                break;
        }
    }
}
