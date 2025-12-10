<?php

namespace Modules\SAS\Repositories;

use Modules\SAS\Entities\LocationVoidMultiple;
use Prettus\Repository\Eloquent\BaseRepository;

class LocationVoidMultipleRepository extends BaseRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    function model()
    {
        return LocationVoidMultiple::class;
    }
}
