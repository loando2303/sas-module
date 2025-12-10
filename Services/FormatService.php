<?php

namespace Modules\SAS\Services;


use App\Models\DropdownDataLocation;

class FormatService
{
    public function format($survey, $data)
    {
        $dataSurvey = (object)$data['survey'];
        $dataNote = $data['note'] ?? [];
        $dataSiteDiagram = $data['site_diagram'] ?? [];
        $dataArea = $data['area'];
        $dataLocation = $data['location'];
        $dataItem = $data['item'] ?? [];
        $dataTimeLine = $data['time_line'] ?? [];
        $dataRam = $data['rams'] ?? [];

        $updateSurvey = $this->getUpdateSurveyData($dataSurvey);

        $propertyData = $this->getPropertyData($dataSurvey);
        $areas = $this->getAreasData($survey, $dataArea);
        $releasedFromScopeAreas = $areas->filter(function ($item) {
            $item = (object)$item;
            return $item->not_assessed == RELEASE_FROM_SCOPE;
        });
        $locations = $this->getLocationsData($survey, $releasedFromScopeAreas, $dataLocation);
        $releasedFromScopeLocations = $locations->filter(function ($item) {
            $item = (object)$item;
            return $item->not_assessed == RELEASE_FROM_SCOPE;
        });
        $items = $this->getItemsData($survey, $releasedFromScopeLocations, $dataItem);

        $notes = $this->getNotesData($survey, $dataNote);
        $siteDiagram = $this->getSiteDiagramData($survey, $dataSiteDiagram);

        $timeLine = collect($dataTimeLine);
        $ram = $this->getRamsData($survey, $dataRam);
        return [
            $updateSurvey,
            $propertyData,
            $areas,
            $locations,
            $items,
            $notes,
            $siteDiagram,
            $timeLine,
            $ram,
        ];
    }

    public function getUpdateSurveyData($dataSurvey)
    {
        return [
            "id"                  => $dataSurvey->surveyDetailId,
            "status"              => $dataSurvey->status,
            "manifest_id"         => $dataSurvey->manifest_id,
            "is_locked"           => SURVEY_UNLOCKED,
            "reason_aborted"      => $dataSurvey->status === 8 ? $dataSurvey->reason : null,
            'surveyor_name'       => $dataSurvey->surveyor_name ?? null,
            'sign_date'           => $dataSurvey->sign_date ?? null,
            'sign_time'           => $dataSurvey->sign_time ?? null,
            'scope_change'        => $dataSurvey->scope_change ?? '',
            'customer'            => $dataSurvey->customer ?? '',
            'upload_image_id'     => $dataSurvey->upload_image_id ?? null,
            'upload_signature_id' => $dataSurvey->upload_signature_id ?? null,
            "reason"              => $dataSurvey->status === 8 ? ($dataSurvey->reason ?? null) : null,
            "reason_other"        => $dataSurvey->status === 8 ? ($dataSurvey->reasonOther ?? null) : null,
        ];
    }

    public function getNotesData($survey, $dataNote)
    {
        return collect($dataNote)->map(function ($item) use ($survey) {
            $item = arrayToObject($item);
            $itemDetail = $item->detail;
            return [
                "property_id"      => $survey->property_id,
                "category"         => $survey->id,
                "name"             => $itemDetail->reference ?? null,
                "plan_reference"   => $itemDetail->description ?? null,
                "upload_image_id"  => $item->upload_image_id ?? null,
                "note"             => $itemDetail->comment ?? null,
                "survey_id"        => 0,
                "type"             => 1,
                "document_present" => 1,
                "added"            => time(),
            ];
        });
    }

    public function getSiteDiagramData($survey, $data)
    {
        return collect($data)->map(function ($item) use ($survey) {
            $item = arrayToObject($item);
            return [
                "upload_image_id"  => $item->upload_image_id ?? null,
                'survey_id'        => $survey->id,
                "property_id"      => $survey->property_id,
                'created_by'       => $survey->surveyor_id ?? 0,
                'document_present' => 1,
            ];
        });
    }

    public function getRamsData($survey, $data)
    {
        return collect($data)->map(function ($item) use ($survey) {
            $item = arrayToObject($item);
            return [
                "upload_image_id" => $item->upload_image_id ?? null,
                'survey_id'       => $survey->id,
                "property_id"     => $survey->property_id,
                'rams_id'         => $item->rams_id ?? 0,
                'pdf_id'          => $item->pdf_id ?? 0,
                'id'              => $item->pdf_id ?? 0,
            ];
        });
    }

    public function getAreasData($survey, $dataArea)
    {
        return collect($dataArea)->map(function ($item) use ($survey) {
            $item = arrayToObject($item);
            $status = $item->detail->objectStatus ?? 0;
            $reason = $item->detail->statusReason ?? 0;
            $otherReason = $item->detail->not_assessed_other_reason ?? 0;
            $newItem = [
                "id"                        => $item->detail->isNew == 0 ? $item->detail->floorDetailId : 0,
                "app_id"                    => $item->keyID,
                "property_id"               => $survey->property_id,
                "survey_id"                 => $survey->id,
                "description"               => $item->detail->floorDetails->description ?? '',
                "area_reference"            => $item->detail->floorDetails->reference ?? '',
                "decommissioned"            => $status,
                "decommissioned_reason"     => $reason,
                "not_assessed"              => $status,
                "not_assessed_reason"       => $reason,
                "not_assessed_other_reason" => $otherReason,
                "area_reference_id"         => $item->detail->floorDetails->area_reference_id ?? null ,
            ];
            if ($status != DECOMMISSION) {
                $newItem['decommissioned'] = 0;
                $newItem['decommissioned_reason'] = 0;
            }
            return $newItem;
        });
    }

    public function getLocationsData($survey, $releasedFromScopeAreas, $dataLocation)
    {
        return collect($dataLocation)->map(function ($item) use ($survey, $releasedFromScopeAreas) {
            $item = arrayToObject($item);
            $status = $item->detail->objectStatus ?? 0;
            $reason = $item->detail->statusReason ?? 0;
            $otherReason = $item->detail->not_assessed_other_reason ?? 0;
            if (!is_null($survey) && $survey->surveySetting->is_require_location_construction_details == 1) {
                $is_require_location_construction_details_survey = ACTIVE;
            } else {
                $is_require_location_construction_details_survey = (@$item->detail->is_require_location_construction_details_survey == 1) ? ACTIVE : IN_ACTIVE;
            }
            $releasedFromScopeArea = $releasedFromScopeAreas->where('app_id', $item->areaID)->first();
            $newItem = [
                "id"                                              => ($item->detail->isNew == 0) ? $item->detail->roomDetailId : 0,
                "app_id"                                          => $item->keyID,
                "app_area_id"                                     => $item->areaID,
                "property_id"                                     => $survey->property_id ?? 0,
                "survey_id"                                       => $survey->id ?? 0,
                'location_template_id'                            => $item->detail->location_template_id ?? 0,
                "description"                                     => $item->detail->roomLocationDetails->description ?? '',
                "location_reference"                              => $item->detail->roomLocationDetails->reference ?? '',
                "state"                                           => $item->detail->isAccessible ?? 0,
                "limit_access_other"                              => $item->detail->limit_access_other ?? '',
                "decommissioned"                                  => $status,
                "decommissioned_reason"                           => $reason,
                "not_assessed"                                    => !empty($releasedFromScopeArea) ? $releasedFromScopeArea['not_assessed'] : $status,
                "not_assessed_reason"                             => !empty($releasedFromScopeArea) ? $releasedFromScopeArea['not_assessed_reason'] : $reason,
                "not_assessed_other_reason"                       => !empty($releasedFromScopeArea) ? $releasedFromScopeArea['not_assessed_other_reason'] : $otherReason,
                "is_require_location_construction_details_survey" => $is_require_location_construction_details_survey,
                "upload_image_id"                                 => $item->upload_image_id ?? null,
                'is_ecc'                                          => isset($item->detail->ecc_dropdown_id) && $item->detail->ecc_dropdown_id ? 1 : 0,
                'location_ecc_code_id'                            => $item->detail->ecc_dropdown_id ?? 0,
            ];
            if ($status != DECOMMISSION) {
                $newItem['decommissioned'] = 0;
                $newItem['decommissioned_reason'] = 0;
            }

            $newItem['location_info'] = $this->getLocationInfoData($item->detail->roomLocationDetails);
            if (!empty($newItem['limit_access_other'])){
                $newItem['location_info']['reason_inaccess_other'] = $newItem['limit_access_other'];
            }
            $newItem['location_void'] = $this->getLocationVoidData($item->detail->roomLocationDetails->constructionDetails);
            $newItem['location_construction'] = $this->getLocationConstructionData($item->detail->roomLocationDetails->constructionDetails);
            $newItem['location_multi_voids'] = $this->getLocationMultiVoidsData($item->detail->roomLocationDetails);
            return $newItem;
        });
    }

    public function getLocationInfoData($data)
    {
        return [
            "reason_inaccess_key"   => $data->reason ?? null,
            "reason_inaccess_other" => $data->reasonOther ?? null,
            "comments"              => $data->comments ?? null,
        ];
    }

    public function getLocationVoidData($data)
    {
        return [
            'ceiling'        => $this->getParentDropdownID($data->ceilingVoid ?? null),
            'ceiling_other'  => $data->ceilingVoidOther ?? null,
            'cavities'       => $this->getParentDropdownID($data->cavities ?? null),
            'cavities_other' => $data->cavitiesOther ?? null,
            'risers'         => $this->getParentDropdownID($data->risers ?? null),
            'risers_other'   => $data->risersOther ?? null,
            'ducting'        => $this->getParentDropdownID($data->ducting ?? null),
            'ducting_other'  => $data->ductingOther ?? null,
            'boxing'         => $this->getParentDropdownID($data->boxing ?? null),
            'boxing_other'   => $data->boxingOther ?? null,
            'pipework'       => $this->getParentDropdownID($data->pipework ?? null),
            'pipework_other' => $data->pipeworkOther ?? null,
            'floor'          => $this->getParentDropdownID($data->floorVoid ?? null),
            'floor_other'    => $data->floorVoidOther ?? null,
        ];
    }

    public function getLocationConstructionData($data)
    {
        return [
            "ceiling"       => $data->ceilingOther ? $this->getOtherConstructionData("ceiling", $data->ceiling) : ($data->ceiling ?? null),
            "ceiling_other" => $data->ceilingOther ?? null,
            "walls"         => $data->wallsOther ? $this->getOtherConstructionData("wall", $data->walls) : ($data->walls ?? null),
            "walls_other"   => $data->wallsOther ?? null,
            "doors"         => $data->doorsOther ? $this->getOtherConstructionData("door", $data->doors) : ($data->doors ?? null),
            "doors_other"   => $data->doorsOther ?? null,
            "floor"         => $data->floorOther ? $this->getOtherConstructionData("floor", $data->floor) : ($data->floor ?? null),
            "floor_other"   => $data->floorOther ?? null,
            "windows"       => $data->windowsOther ? $this->getOtherConstructionData("window", $data->windows) : ($data->windows ?? null),
            "windows_other" => $data->windowsOther ?? null,
        ];
    }

    public function getLocationMultiVoidsData($locationDetails = [])
    {
        $voids = $locationDetails->voidDetails ?? [];
        $locationVoids = [];
        if (count($voids)) {
            foreach ($voids as $void) {
                $locationVoids[] = [
                    'id'             => $void->isNew == 0 ? $void->originalID : 0,
                    'dropdown_id'    => $void->dropdownId ?? null,
                    'name'           => $void->name ?? null,
                    'dropdown_value' => $this->getParentDropdownID($void->dropdownValue ?? '', $void->other ?? null),
                    'other'          => $void->other ?? null,
                    'upload_image_id' => $void->upload_image_id ?? null
                ];
            }
        } else {
            $locationVoidFields = config('sas.location_void');
            foreach ($locationVoidFields as $field => $value) {
                $this->getLocationVoid($locationVoids, $locationDetails, $field, $value);
            }
        }

        return $locationVoids;
    }

    public function getLocationVoid(&$locationVoids, $locationDetails, $field, $value)
    {
        if (isset($locationDetails->{$field}) && !is_null($locationDetails->{$field})) {
            $locationVoids[] = [
                'dropdown_id'    => $value,
                'dropdown_value' => $locationDetails->{$field},
                'other'          => $locationDetails->{$field . 'Other'} ?? null,
            ];
        }
    }

    public function getParentDropdownID($ids, $other = null)
    {
        // convert string to int
        $array = array_filter(explode(",", $ids));
        $newArray = [];
        foreach ($array as $key => $value) {
            $newArray[$key] = intval($value);
        }
        $original_ids = implode(",", $newArray);
        if (!is_null($ids) and ($ids != '')) {
            $data = DropdownDataLocation::whereRaw("id IN ($ids)")->first();
            if (!is_null($data) and !empty($data)) {
                if ($data->parent_id != 0) {
                    $original_ids = $data->parent_id . ',' . $ids;
                } else {
                    $first_id = $newArray[0] ?? null;
                    if ($first_id && $other) {
                        $other_id = DropdownDataLocation::where('description', 'Other')->where('parent_id', $first_id)->first();
                        if ($other_id) {
                            return $ids . ',' . $other_id->id;
                        }
                    }
                }
            }
        }
        return $original_ids;
    }

    public function getOtherConstructionData($type, $dropdowns = NULL)
    {
        $constructionData = config('sas.location_construction');
        $constructionValue = $constructionData[$type] ?? '';
        if (!$dropdowns) {
            return $constructionValue;
        }
        if (strpos($dropdowns, (string)$constructionValue) === FALSE) {
            return $dropdowns . ',' . $constructionValue;
        }

        return $dropdowns;
    }

    public function getPropertyData($data)
    {
        $dataAsset = ($data->assetUse ?? null);
        $dataAssetType = ($data->propertyAccessType ?? null);
        $dataAsset = (object)$dataAsset;
        $dataAssetType = (object)$dataAssetType;
        $dataUpdate['PrimaryUse'] = $dataAsset->primary ?? null;
        $dataUpdate['primaryusemore'] = $dataAsset->primaryOther ?? null;
        $dataUpdate['SecondaryUse'] = $dataAsset->secondary ?? null;
        $dataUpdate['secondaryusemore'] = $dataAsset->secondaryOther ?? null;
        $dataUpdate['programmeType'] = ($dataAssetType->accessPrimary ?? null);

        $dataConstruct = ($data->construction ?? null);
        $dataConstruct = (object)$dataConstruct;
        $dataUpdate['constructionAge'] = $dataConstruct->assetAge ?? null;
        $dataUpdate['constructionType'] = $dataConstruct->contructionType ?? null;
        $dataUpdate['electricalMeter'] = ($dataConstruct->electricalMeter ?? null);
        $dataUpdate['gasMeter'] = ($dataConstruct->gasMeter ?? null);
        $dataUpdate['loftVoid'] = ($dataConstruct->loftVoid ?? null);

        $dataSizeVolume = ($data->sizeVolume ?? null);
        $dataSizeVolume = (object)$dataSizeVolume;
        $dataUpdate['sizeFloors'] = $dataSizeVolume->numberFloors ?? null;
        $dataUpdate['sizeStaircases'] = $dataSizeVolume->numberStaircases ?? null;
        $dataUpdate['sizeLifts'] = $dataSizeVolume->numberLifts ?? null;
        $dataUpdate['sizeNetArea'] = $dataSizeVolume->netAreaPerFloor ?? null;
        $dataUpdate['sizeGrossArea'] = $dataSizeVolume->grossArea ?? null;
        $dataUpdate['sizeComments'] = $dataSizeVolume->comments ?? null;

        $floor_above = $dataSizeVolume->numberAboveFloors ?? null;
        $floors_below = $dataSizeVolume->numberBelowFloors ?? null;

        $dataUpdate['floors_above'] = $floor_above > 25 ? 'Other' : $floor_above;
        $dataUpdate['floors_above_other'] = $floor_above > 25 ? $floor_above : null;
        $dataUpdate['floors_below'] = $floors_below > 25 ? 'Other' : $floors_below;
        $dataUpdate['floors_below_other'] = $floors_below > 25 ? $floors_below : null;

        $dataUpdate['property_status'] = $data->propertyStatus ?? null;
        $dataUpdate['property_occupied'] = $data->propertyOccupied ?? null;

        return $dataUpdate;
    }

    public function getItemsData($survey, $releasedFromScopeLocations, $dataItem)
    {
        return collect($dataItem)->map(function ($item) use ($survey, $releasedFromScopeLocations) {
            $item = arrayToObject($item);
            $status = $item->detail->objectStatus ?? 0;
            $reason = $item->detail->statusReason ?? 0;
            $otherReason = $item->detail->not_assessed_other_reason ?? 0;
            $itemType = \CommonHelpers::getItemStateFromText($item->detail->itemType ?? null);
            $releasedFromScopeLocation = $releasedFromScopeLocations->where('app_id', $item->locationID)->first();
            $newItem = [
                "id"                        => ($item->detail->isNew == 0) ? $item->detail->recordDetailId : 0,
                "app_id"                    => $item->keyID,
                "app_area_id"               => $item->areaID,
                "app_location_id"           => $item->locationID,
                "property_id"               => $survey->property_id ?? 0,
                "survey_id"                 => $survey->id ?? 0,
                "state"                     => $itemType['state'] ?? 0,
                "itemType"                  => $item->detail->itemType ?? '',
                "decommissioned"            => $status,
                "decommissioned_reason"     => $reason,
                "not_assessed"              => !empty($releasedFromScopeLocation) ? $releasedFromScopeLocation['not_assessed'] : $status,
                "not_assessed_reason"       => !empty($releasedFromScopeLocation) ? $releasedFromScopeLocation['not_assessed_reason'] : $reason,
                "not_assessed_other_reason" => !empty($releasedFromScopeLocation) ? $releasedFromScopeLocation['not_assessed_other_reason'] : $otherReason,
                "name"                      => $item->detail->reference ?? null,
                "is_mas_override"           => $item->detail->is_mas_override ?? null,
                "sample_sequential_number"  => $item->detail->sample_sequential_number ?? null,
                "upload_image_item_id"      => $item->upload_image_item_id ?? null,
                "upload_image_location_id"  => $item->upload_image_location_id ?? null,
                "upload_image_addition_id"  => $item->upload_image_addition_id ?? null,
                "sample"                    => $item->detail->sample ?? null,
                "item_removed"              => $item->detail->item_removed ?? 0,
                "item_removed_other"        => $item->detail->item_removed_other ?? null,
            ];
            if ($status != DECOMMISSION) {
                $newItem['decommissioned'] = 0;
                $newItem['decommissioned_reason'] = 0;
            }
            $newItem['item_info'] = $this->getItemInfoData($item->detail, $itemType);
            $newItem['item_details'] = $this->getItemDetailsData($item->detail);
            $newItem['item_mas'] = $this->getItemMaterialTabData($item->detail);
            $newItem['item_pas'] = $this->getItemPriorityTabData($item->detail);
            $newItem['item_action_recommendations'] = $this->getItemActionRecommendationTabData($item->detail);
            return $newItem;
        });
    }

    public function getItemInfoData($data, $itemType)
    {
        return [
            'extent'             => $data->extent ?? null,
            'comment'            => $data->comment ?? null,
            'assessment'         => $itemType['isFullAssessment'] ?? null,
            'is_r_and_d_element' => (@$data->rAndD == true) ? 1 : 0
        ];
    }

    public function getItemDetailsData($data)
    {
        $inaccessItemReason = $data->reason ?? 0;
        $inaccessItemReasonOther = $data->reasonOther ?? '';
        if (is_string($inaccessItemReason)) {
            $inaccessItemReason = 592;
            $inaccessItemReasonOther = $data->reason ?? '';
        }
        return [
            'specificLocation'                => $data->specificLocation ?? null,
            'specificLocationOther'           => $data->specificLocationOther ?? null,
            'productDebris'                   => $data->productDebris ?? null,
            'productDebrisOtherAsbestosOther' => $data->productDebrisOtherAsbestosOther ?? null,
            'asbestosType'                    => $data->asbestosType ?? null,
            'asbestosTypeOther'               => $data->asbestosTypeOther ?? null,
            'measurement'                     => $data->measurement ?? null,
            'inaccessItemReason'              => $inaccessItemReason ?? null,
            'inaccessItemReasonOther'         => $inaccessItemReasonOther ?? null,
        ];
    }

    public function getItemMaterialTabData($data)
    {
        $dataItemDetailsInfoMaterial = $data->materialAssessment ?? null;
        return [
            'productDebrisType'   => $dataItemDetailsInfoMaterial->productDebrisType ?? null,
            'damageDeterioration' => $dataItemDetailsInfoMaterial->damageDeterioration ?? null,
            'surfaceTreatment'    => $dataItemDetailsInfoMaterial->surfaceTreatment ?? null,
            'asbestosType'        => $dataItemDetailsInfoMaterial->asbestosType ?? null,
        ];
    }

    public function getItemPriorityTabData($data)
    {
        $dataItemDetailsInfoPriority = $data->priorityAssessment ?? null;
        $dataItemDetailsInfoPasOC = $dataItemDetailsInfoPriority->normalOccupancyActivity ?? null;
        $dataItemDetailsInfoPasLikeHood = $dataItemDetailsInfoPriority->likelihoodOfDisturbance ?? null;
        $dataItemDetailsInfoPasHuman = $dataItemDetailsInfoPriority->humanExposurePotential ?? null;
        $dataItemDetailsInfoPasMaintain = $dataItemDetailsInfoPriority->maintenanceActivity ?? null;

        return [
            'primary'           => $dataItemDetailsInfoPasOC->primary ?? null,
            'secondary'         => $dataItemDetailsInfoPasOC->secondary ?? null,
            'location'          => $dataItemDetailsInfoPasLikeHood->location ?? null,
            'accessibility'     => $dataItemDetailsInfoPasLikeHood->accessibility ?? null,
            'extentAmount'      => $dataItemDetailsInfoPasLikeHood->extentAmount ?? null,
            'number'            => $dataItemDetailsInfoPasHuman->number ?? null,
            'frequency'         => $dataItemDetailsInfoPasHuman->frequency ?? null,
            //'frequencyUse'      => $dataItemDetailsInfoPasHuman->frequency ?? null,
            'averageTime'       => $dataItemDetailsInfoPasHuman->averageTime ?? null,
            'type'              => $dataItemDetailsInfoPasMaintain->type ?? null,
            'frequencyActivity' => $dataItemDetailsInfoPasMaintain->frequency ?? null,
        ];
    }

    public function getItemActionRecommendationTabData($data)
    {
        return [
            'actionsRecommendations'      => $data->actionsRecommendations ?? null,
            'actionsRecommendationsOther' => $data->actionsRecommendationsOther ?? null,
        ];
    }
}
