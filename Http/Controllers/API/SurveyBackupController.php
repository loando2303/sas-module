<?php

namespace Modules\SAS\Http\Controllers\API;

use App\Http\Controllers\API\AppBaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\SAS\Http\Requests\API\BackupDataRequest;
use Modules\SAS\Http\Requests\API\BackupImageRequest;
use Modules\SAS\Services\ConditionBackupService;

class SurveyBackupController extends AppBaseController
{
    private $conditionBackupService;

    public function __construct(ConditionBackupService $conditionBackupService)
    {
        $this->conditionBackupService = $conditionBackupService;
    }

    public function backupManifest(Request $request)
    {
        $validatedData = $request->validate([
            'survey_id' => 'required|integer',
        ]);

        $result = $this->conditionBackupService->createBackupManifest($validatedData['survey_id']);

        if ($result['status_code'] == 200) {

            return $this->sendResponse($result['data'], $result['msg']);
        } else {
            return $this->sendError($result['msg'], $result['status_code']);
        }
    }

    public function backupData(BackupDataRequest $apiUploadBackupDataRequest)
    {
        $data = $apiUploadBackupDataRequest->validated();

        $backupData = $this->conditionBackupService->backupData($data);

        if ($backupData['status_code'] == 200) {
            return $this->sendResponse($backupData['data'], $backupData['msg']);
        } else {
            return $this->sendError($backupData['msg'], $backupData['status_code']);
        }
    }

    public function backupImage(BackupImageRequest $request)
    {
        $data       = $request->validated();
        $backupData = $this->conditionBackupService->backupImage($data);

        if ($backupData['status_code'] == 200) {
            return $this->sendResponse([], $backupData['msg']);
        } else {
            return $this->sendError($backupData['msg']);
        }
    }

    public function restoreData($backup_id)
    {
        $result = $this->conditionBackupService->restoreData($backup_id);

        if ($result['status_code'] == 200) {
            return response()->download(Storage::path($result['data']->path), $result['data']->file_name);
        } else {
            return $this->sendError($result['msg'], $result['status_code']);
        }
    }

    public function restoreImage($backup_id, Request $request)
    {
        $images = $this->conditionBackupService->restoreImage($backup_id);

        if ($images['status_code'] == 200) {
            return $this->sendResponse($images['data'], $images['msg']);
        } else {
            return $this->sendError($images['msg'], $images['status_code']);
        }
    }

    public function backupList($surveyId)
    {
        $result = $this->conditionBackupService->backupList($surveyId);

        if ($result['status_code'] == 200) {
            return $this->sendResponse($result['data'], $result['msg']);
        } else {
            return $this->sendError($result['msg'], $result['status_code']);
        }
    }
}
