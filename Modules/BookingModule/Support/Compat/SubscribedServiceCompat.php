<?php

namespace Modules\BookingModule\Support\Compat;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SubscribedServiceCompat extends Model
{
    protected $table = 'subscribed_services';

    protected $guarded = [];

    public function scopeOfSubscription(Builder $query, $flag = 1): Builder
    {
        return $query->where('is_subscribed', (int) $flag);
    }
}
