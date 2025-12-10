<?php

namespace Modules\SAS\Services;
if (class_exists("\\App\\Services\\ShineCompliance\\RamsService")) {
    info('111');
    class RamsServiceParent extends \App\Services\ShineCompliance\RamsService
    {
        public function __construct(
            \App\Repositories\RamsRepository          $ramsRepository,
            \App\Repositories\PropertyRepository      $propertyRepository,
            \App\Repositories\PreSurveyPlanRepository $preSurveyPlanRepository
        )
        {
            parent::__construct($ramsRepository, $propertyRepository, $preSurveyPlanRepository);
        }
    }
} else {
    class RamsServiceParent
    {
    }
}

class RamsService extends RamsServiceParent
{

}
