<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Record a stock movement atomically.
     *
     * The item row is locked for update inside the transaction so concurrent
     * requests cannot overwrite each other's stock change.
     *
     * For 'usage' and 'wastage', if the requested quantity exceeds available
     * stock, an \Exception is thrown — callers should catch and show the user.
     *
     * @throws \Exception when usage/wastage quantity exceeds available stock
     */
    public function recordMovement(InventoryItem $item, string $type, float $qty, array $meta = []): InventoryMovement
    {
        return DB::transaction(function () use ($item, $type, $qty, $meta) {
            // Lock the row so no other transaction can read/write stock until we commit
            $fresh = InventoryItem::lockForUpdate()->findOrFail($item->id);

            $newStock = match ($type) {
                'purchase'   => $fresh->current_stock + $qty,
                'usage',
                'wastage'    => (function () use ($fresh, $qty, $type) {
                    if ($qty > $fresh->current_stock) {
                        throw new \Exception(
                            "Cannot record {$type}: requested {$qty} {$fresh->unit} but only {$fresh->current_stock} {$fresh->unit} in stock."
                        );
                    }
                    return $fresh->current_stock - $qty;
                })(),
                'adjustment' => $qty,
                default      => $fresh->current_stock,
            };

            $movement = InventoryMovement::create([
                'hotel_id'       => $fresh->hotel_id,
                'item_id'        => $fresh->id,
                'type'           => $type,
                'quantity'       => $qty,
                'notes'          => $meta['notes'] ?? null,
                'reference_type' => $meta['reference_type'] ?? null,
                'reference_id'   => $meta['reference_id'] ?? null,
                'created_by'     => $meta['created_by'] ?? null,
            ]);

            $fresh->update(['current_stock' => $newStock]);

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
