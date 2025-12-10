<?php

namespace Modules\SAS\Traits;



trait RegisterTrait
{
    public function register() {
        $tableName = $this->getTable();
        $columns = \Schema::getColumnListing($tableName);
        $conditions = [];
        $registerColumns = [
            'survey_id',
            'cd_survey_id',
            'rcf_id',
            'assess_id',
            'gas_id'
        ];
        foreach ($registerColumns as $column) {
            if (in_array($column, $columns)) {
                $conditions[$column] = 0;
            }
        }
        return $this->hasOne(self::class, 'record_id', 'record_id')
            ->where($conditions);
    }
}