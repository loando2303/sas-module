<?php

namespace Modules\SAS\Entities;

use App\Models\ModelBase;

class SiteDiagram extends ModelBase
{
    protected $table = 'tbl_site_diagram';

    protected $fillable = [
        'id',
        'reference',
        'survey_id',
        'property_id',
        'created_by',
        'document_present',
        'path',
        'file_name',
        'size',
        'mime',
    ];

    public function survey() {
        return $this->belongsTo('App\Models\Survey','survey_id','id');
    }

}
