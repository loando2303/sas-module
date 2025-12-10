<?php

namespace Modules\SAS\Http\Requests\API;

use App\Http\Request\API\APIRequest;

class ChangeSurveyOrRequest extends APIRequest
{
    public function rules()
    {
        return [
            'survey_id'   => 'required|integer|exists:tbl_survey,id',
            'surveyor_id' => 'required|exists:tbl_users,id',
        ];
    }


}
