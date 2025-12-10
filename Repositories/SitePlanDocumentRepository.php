<?php
namespace Modules\SAS\Repositories;
use App\Models\SitePlanDocument;
use Prettus\Repository\Eloquent\BaseRepository;

class SitePlanDocumentRepository extends BaseRepository {

    /**
     * Specify Model class name
     *
     * @return string
     */
    function model()
    {
        return SitePlanDocument::class;
    }
}
