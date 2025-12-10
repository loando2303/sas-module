<?php

namespace Modules\SAS\Entities;

use Illuminate\Database\Eloquent\Model;

class SurveyUploadManifest extends Model
{
    protected $table    = 'survey_upload_manifests';
    protected $fillable = [
        'survey_id',
        'created_by',
        'status',
        'created_at',
        'updated_at',
    ];


}
