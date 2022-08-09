<?php

namespace Takaden;

use Illuminate\Database\Eloquent\Relations\HasMany;

interface Billable
{
    public function orders(): HasMany;
    public function payments(): HasMany;
}
