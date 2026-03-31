<?php

namespace App\Services;

class HotelContext
{
    protected ?int $hotelId = null;

    public function setHotel(int $id): void
    {
        $this->hotelId = $id;
    }

    public function getHotel(): ?int
    {
        return $this->hotelId;
    }

    public function isSet(): bool
    {
        return $this->hotelId !== null;
    }

    public function clear(): void
    {
        $this->hotelId = null;
    }
}
