<?php

namespace Modules\SAS\Http\Requests\API;

use App\Http\Request\API\APIRequest;

class UploadManifestRequest extends APIRequest
{
    public function rules()
    {
        return [
            'survey_id' => 'required|integer',
        ];
    }
    /**
     * Custom message for validation
     *
     * @return array
     */
    public function messages()
    {
        return [];
    }


}
