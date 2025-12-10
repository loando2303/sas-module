<?php

namespace Modules\SAS\Entities;

use Illuminate\Database\Eloquent\Model;

class SurveyUploadData extends Model
{
    protected $table    = 'survey_upload_data';
    protected $fillable = [
        'manifest_id',
        'survey_id',
        'status',
        'data',
        'created_at',
        'updated_at',
    ];


}
