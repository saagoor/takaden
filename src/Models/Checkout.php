<?php

namespace Takaden\Models;

use Illuminate\Database\Eloquent\Model;

class Checkout extends Model
{
    protected $guarded = [];

    protected $table = 'takaden_checkouts';

    public function orderable()
    {
        return $this->morphTo();
    }
}
