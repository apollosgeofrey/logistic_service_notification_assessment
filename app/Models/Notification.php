<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    //

    protected $fillable = [
        'subscriber_id',
        'channel',
        'content',
        'priority',
        'status',
        'sent_at',
        'delivered_at',
        'external_id'
    ];
    
}
