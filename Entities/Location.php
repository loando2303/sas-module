<?php

namespace Modules\SAS\Entities;

use Modules\SAS\Traits\RegisterTrait;

class Location extends \App\Models\Location
{
    use RegisterTrait;
    public function __construct(array $attributes = []) {
        $this->fillable[] = 'limit_access_other';

        parent::__construct($attributes);
    }

    public function items() {
        return $this->hasMany('App\Models\Item','location_id','id')->where('decommissioned',0)->where('survey_id',0);
    }
}
