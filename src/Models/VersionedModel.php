<?php

namespace Dcp\Models;

use Illuminate\Database\Eloquent\Model;
use Dcp\Builders\TemporalBuilder;

class VersionedModel extends Model
{
    public function newEloquentBuilder($query)
    {
        return new TemporalBuilder($query);
    }
}
