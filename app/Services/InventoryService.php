<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function recordMovement(InventoryItem $item, string $type, float $qty, array $meta = []): InventoryMovement
    {
        return DB::transaction(function () use ($item, $type, $qty, $meta) {
            $movement = InventoryMovement::create([
                'hotel_id'       => $item->hotel_id,
                'item_id'        => $item->id,
                'type'           => $type,
                'quantity'       => $qty,
                'notes'          => $meta['notes'] ?? null,
                'reference_type' => $meta['reference_type'] ?? null,
                'reference_id'   => $meta['reference_id'] ?? null,
                'created_by'     => $meta['created_by'] ?? null,
            ]);

            $newStock = match ($type) {
                'purchase'   => $item->current_stock + $qty,
                'usage',
                'wastage'    => max(0, $item->current_stock - $qty),
                'adjustment' => $qty,
                default      => $item->current_stock,
            };

            $item->update(['current_stock' => $newStock]);

            return $movement;
        });
    }

    public function adjustStock(InventoryItem $item, float $newStock, string $notes, int $userId): void
    {
        $this->recordMovement($item, 'adjustment', $newStock, [
            'notes'      => $notes,
            'created_by' => $userId,
        ]);
    }

    public function getLowStockItems(int $hotelId): Collection
    {
        return InventoryItem::withoutGlobalScopes()
            ->where('hotel_id', $hotelId)
            ->where('is_active', true)
            ->whereRaw('current_stock <= reorder_level AND reorder_level > 0')
            ->with('category')
            ->orderBy('name')
            ->get();
    }
}
