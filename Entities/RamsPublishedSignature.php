<?php

namespace Modules\SAS\Entities;

use Illuminate\Database\Eloquent\Model;

class RamsPublishedSignature extends Model
{
    protected $table = 'ram_published_signatories';

    protected $fillable = [
        'published_id',
        'name',
        'type',
        'size',
        'filename',
        'mime',
        'path',
        'is_large_file',
        'deleted_by',
        'created_by',
        'app_created_at',
        'user_id',
    ];
}
