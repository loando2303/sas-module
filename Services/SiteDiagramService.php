<?php

namespace Modules\SAS\Services;


use Modules\SAS\Repositories\SiteDiagramRepository;
use Modules\SAS\Repositories\SurveyUploadImageRepository;
use Modules\SAS\Traits\SurveyUploadImageTrait;

class SiteDiagramService
{
    use SurveyUploadImageTrait;

    public function __construct(
        SiteDiagramRepository $siteDiagramRepository,
        SurveyUploadImageRepository $surveyUploadImageRepository
    )
    {
        $this->siteDiagramRepository = $siteDiagramRepository;
        $this->surveyUploadImageRepository = $surveyUploadImageRepository;
    }

    public function createSiteDiagram(&$siteDiagrams)
    {
        $model = $this->siteDiagramRepository->getModel();
        $fieldsOnly = $model->getFillable();
        $siteDiagrams = $siteDiagrams->map(function ($siteDiagram) use ($fieldsOnly) {
            if ($siteDiagram['upload_image_id']) {
                $uploadImageIds = [$siteDiagram['upload_image_id']];
                $uploads = $this->surveyUploadImageRepository->getImagesByUploadImageIds($uploadImageIds);
                if (!empty($uploads) && count($uploads) > 0){
                    [$upload]  = $uploads;
                    $siteDiagram['path'] = $upload->path ?? '';
                    $siteDiagram['file_name'] = $upload->file_name ?? '';
                    $siteDiagram['size'] = $upload->size ?? '';
                    $siteDiagram['mime'] = $upload->mime ?? '';
                }
                $siteDiagramData = collect($siteDiagram)->only($fieldsOnly)->toArray();
                $newItem = $this->siteDiagramRepository->create($siteDiagramData);
                $newItem->reference = "SDD" . $newItem->id;
                $newItem->save();
                $siteDiagram['id'] = $newItem->id;
                return $siteDiagram;
            }
        });
    }


}
