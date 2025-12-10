<?php

namespace Modules\SAS\Http\Requests\API;


use App\Http\Request\API\APIRequest;

class GetSurveyDetailRequest extends APIRequest
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
        return [
            'survey_id.required' => 'The survey_id field is required'
        ];
    }


}
