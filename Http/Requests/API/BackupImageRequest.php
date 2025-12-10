<?php

namespace Modules\SAS\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;

class BackupImageRequest extends FormRequest
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
            'app_id' => 'required|integer',
            'file' => 'required|file',
            'image_type' => 'nullable',
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
            'app_id.required' => 'The app_id field is required!',
            'file.required' => 'The file data is required!',
        ];
    }
}
