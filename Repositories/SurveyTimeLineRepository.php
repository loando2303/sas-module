<?php
namespace Modules\SAS\Repositories;
use Modules\SAS\Entities\SurveyTimeLine;
use Prettus\Repository\Eloquent\BaseRepository;

class SurveyTimeLineRepository extends BaseRepository {

    /**
     * Specify Model class name
     *
     * @return string
     */
    function model()
    {
        return SurveyTimeLine::class;
    }
}
