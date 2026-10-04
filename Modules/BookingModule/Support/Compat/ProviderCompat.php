<?php

namespace Modules\BookingModule\Support\Compat;

use Illuminate\Database\Eloquent\Model;

class ProviderCompat extends Model
{
    protected $table = 'providers';

    protected $guarded = [];
}
