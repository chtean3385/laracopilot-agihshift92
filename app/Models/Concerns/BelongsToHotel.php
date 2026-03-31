<?php

namespace App\Models\Concerns;

use App\Models\Scopes\HotelScope;
use App\Services\HotelContext;

trait BelongsToHotel
{
    public static function bootBelongsToHotel(): void
    {
        static::addGlobalScope(new HotelScope());

        static::creating(function ($model) {
            if (empty($model->hotel_id)) {
                $context = app(HotelContext::class);
                if ($context->isSet()) {
                    $model->hotel_id = $context->getHotel();
                }
            }
        });
    }
}
