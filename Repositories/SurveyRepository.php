<?php

namespace Modules\SAS\Repositories;


use App\Models\Survey;
use Prettus\Repository\Eloquent\BaseRepository;

class SurveyRepository extends BaseRepository
{

    public function model()
    {
        return Survey::class;
    }
    public function getDetail($id, $with)
    {
        return $this->model->with($with)
            ->where('id', $id)
            ->first();
    }


}
