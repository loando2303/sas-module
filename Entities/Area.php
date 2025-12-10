<?php

namespace Modules\SAS\Entities;

use Modules\SAS\Traits\RegisterTrait;

class Area extends \App\Models\Area
{
    use RegisterTrait;

    public function locations()
    {
        return $this->hasMany('App\Models\Location', 'area_id', 'id')->where('decommissioned', 0)->where('survey_id', 0);
    }

    public function allItems()
    {
        return $this->hasMany('App\Models\Item', 'area_id', 'id');
    }

}
