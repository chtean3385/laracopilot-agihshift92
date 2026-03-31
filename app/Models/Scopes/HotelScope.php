<?php

namespace App\Models\Scopes;

use App\Services\HotelContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class HotelScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(HotelContext::class);

        if ($context->isSet()) {
            $builder->where($model->qualifyColumn('hotel_id'), $context->getHotel());
        }
        // When no hotel context: Super Admin or installer sees all data (no filter)
    }
}
