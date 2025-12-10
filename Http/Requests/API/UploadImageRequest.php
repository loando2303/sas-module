<?php

namespace Modules\SAS\Http\Requests\API;

use App\Http\Request\API\APIRequest;

class UploadImageRequest extends APIRequest
{
    public function rules()
    {
        return [
            'manifest_id' => 'required|integer|exists:survey_upload_manifests,id',
            'survey_id' => 'required',
            'image_type' => 'nullable',
            'file' => 'required|file|mimes:jpeg,bmp,png,jpg,heic',
        ];
    }

}

