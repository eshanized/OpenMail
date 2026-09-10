<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class MessageLabel extends Pivot
{
    protected $table = 'message_labels';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'message_metadata_id',
        'label_id',
        'user_id',
    ];
}
