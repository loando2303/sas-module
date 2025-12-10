<?php

namespace Modules\SAS\Repositories;

use Modules\SAS\Entities\Location;
use Prettus\Repository\Eloquent\BaseRepository;

class LocationRepository extends BaseRepository
{
    public function model()
    {
        return Location::class;
    }

    public function updateOrCreateLocationInfo($location, $data) {
        $location->locationInfo()->updateOrCreate(['location_id' => $location->id ?? 0], $data);
        return $location;
    }
    public function updateOrCreateLocationVoid($location, $data) {
        $location->locationVoid()->updateOrCreate(['location_id' => $location->id ?? 0], $data);
        return $location;
    }
    public function updateOrCreateLocationConstruction($location, $data) {
        $location->locationConstruction()->updateOrCreate(['location_id' => $location->id ?? 0], $data);
        return $location;
    }
}
