<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WhatsappMessage extends Model
{
    protected $guarded = [];

    protected $casts = ['response' => 'array', 'sent_at' => 'datetime'];

    public function related(): MorphTo
    {
        return $this->morphTo();
    }
}
