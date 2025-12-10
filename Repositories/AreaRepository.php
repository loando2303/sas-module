<?php

namespace Modules\SAS\Repositories;


use Modules\SAS\Entities\Area;
use Prettus\Repository\Eloquent\BaseRepository;

class AreaRepository extends BaseRepository
{


    public function model()
    {
        return Area::class;
    }
}
