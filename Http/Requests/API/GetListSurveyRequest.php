<?php

namespace Modules\SAS\Http\Requests\API;


use App\Http\Request\API\APIRequest;

class GetListSurveyRequest extends APIRequest
{
    public function rules()
    {
        return [
            'user_id' => 'required|integer',
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
            'user_id.required' => 'The user_id field is required',
        ];
    }


}
