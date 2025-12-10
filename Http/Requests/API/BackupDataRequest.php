<?php

namespace Modules\SAS\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;

class BackupDataRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'backup_id' => 'required|integer',
            'survey_id' => 'required|integer',
            'file' => 'required|file',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return array
     */
    public function authorize()
    {
        return [
            'backup_id.required' => 'The backup_id field is required!',
            'file.required' => 'The text file is required!',
            'survey_id.required' => 'The survey field is required!'
        ];
    }
}
