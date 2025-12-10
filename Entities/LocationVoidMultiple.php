<?php

namespace Modules\SAS\Entities;

use App\Models\ModelBase;
use Illuminate\Database\Eloquent\SoftDeletes;

if (class_exists("\\App\\Models\\LocationVoidMultiple")) {
    class LocationVoidMultipleParent extends \App\Models\LocationVoidMultiple
    {
    }
} else {
    class LocationVoidMultipleParent extends ModelBase
    {
    }
}

class LocationVoidMultiple extends LocationVoidMultipleParent
{
    use SoftDeletes;
}