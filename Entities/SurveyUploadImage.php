<?php

namespace Modules\SAS\Entities;

use Illuminate\Database\Eloquent\Model;

class SurveyUploadImage extends Model
{
    protected $table    = 'survey_upload_images';
    protected $fillable = [
        'manifest_id',
        'survey_id',
        'image_type',
        'file_name',
        'path',
        'mime',
        'size',
        'created_at',
        'updated_at',
    ];


}
