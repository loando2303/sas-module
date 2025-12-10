<?php

namespace Modules\SAS\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SurveyDropdownService
{
    public function getAllDropdowns(): array
    {
        $tables = [
            'tbl_dropdown_data_property',
            'tbl_dropdown_data_survey',
            'tbl_item_accessibility_vulnerability',
            'tbl_item_action_recommendation',
            'tbl_item_additional_information',
            'tbl_item_asbestos_type',
            'tbl_item_decommission_type',
            'tbl_item_extent',
            'tbl_item_licensed_non_licensed',
            'tbl_item_material_assessment_risk',
            'tbl_item_no_access',
            'tbl_item_no_acm_comments',
            'tbl_item_priority_assessment_risk',
            'tbl_item_product_debris_type',
            'tbl_item_sample_comment',
            'tbl_item_specific_location',
            'tbl_property_dropdown',
            'tbl_property_dropdown_title',
            'tbl_dropdown_data_location',
            'tbl_dropdown_data_area',
            'tbl_decommission',
            'tbl_decommission_reasons',
            'tbl_property_programme_type',
            'tbl_property_info_dropdowns',
            'tbl_property_info_dropdown_data',
            'tbl_audit_type',
            'tbl_audit_dropdown',
            'tbl_audit_non_user',
            'tbl_audit_question',
            'tbl_audit_answer',
            'tbl_users',
            'tbl_clients',
            'wra_area_suggestion',
        ];
        $tables = mergeSort($tables);
        $results = [];
        $excludeColumns = ['deleted_by', 'created_by', 'deleted_at', 'created_at', 'updated_at','password'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $query = DB::table($table);
                $columns = Schema::getColumnListing($table);
                if (in_array('decommissioned', $columns)) {
                    $query->where('decommissioned', 0);
                }

                $selectColumns = array_diff($columns, $excludeColumns);
                $data = $query->select($selectColumns)->get();

                $results[$table] = $data;
            } else {
                $results[$table] = [];
            }
        }
        return $results;
    }

}
