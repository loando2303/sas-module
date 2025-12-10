<?php
namespace Modules\SAS\Repositories;
use Modules\SAS\Entities\SiteDiagram;
use Prettus\Repository\Eloquent\BaseRepository;

class SiteDiagramRepository extends BaseRepository {

    /**
     * Specify Model class name
     *
     * @return string
     */
    function model()
    {
        return SiteDiagram::class;
    }
}
