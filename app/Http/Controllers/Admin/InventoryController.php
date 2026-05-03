<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Module;
use App\Services\ActivityLogger;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    private function hotelId(): int
    {
        return (int) (session('crm_hotel_id') ?: session('crm_sa_hotel_filter'));
    }

    private function requireModule(): void
    {
        if (! Module::isEnabled('inventory')) {
            abort(403, 'Inventory module is not enabled for this hotel.');
        }
    }

    // ── Items Index ───────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $this->requireModule();

        $search     = $request->input('search');
        $categoryId = $request->input('category_id');

        $items = InventoryItem::with('category')
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
            ->orderBy('name')
            ->get();

        $categories  = InventoryCategory::orderBy('name')->get();
        $lowStockCount = $items->filter(fn($i) => $i->isLowStock())->count();

        return view('admin.inventory.index', compact('items', 'categories', 'search', 'categoryId', 'lowStockCount'));
    }

    // ── Create Item ───────────────────────────────────────────────────────────
    public function create()
    {
        $this->requireModule();
        $categories = InventoryCategory::orderBy('name')->get();
        return view('admin.inventory.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->requireModule();
        $hotelId = $this->hotelId();

        $data = $request->validate([
            'name'          => 'required|string|max:150',
            'category_id'   => [
                'nullable',
                Rule::exists('inventory_categories', 'id')->where('hotel_id', $hotelId),
            ],
            'unit'          => 'required|string|max:30',
            'reorder_level' => 'nullable|numeric|min:0',
            'cost_price'    => 'nullable|numeric|min:0',
            'is_active'     => 'nullable|boolean',
        ]);

        $data['hotel_id']      = $hotelId;
        $data['reorder_level'] = $data['reorder_level'] ?? 0;
        $data['cost_price']    = $data['cost_price'] ?? 0;
        $data['is_active']     = $request->boolean('is_active', true);
        $data['current_stock'] = 0;

        $item = InventoryItem::create($data);
        ActivityLogger::log('inventory_item_created', 'Inventory', "Item '{$item->name}' added");

        return redirect()->route('inventory.index')->with('success', "Item '{$item->name}' added successfully.");
    }

    // ── Edit Item ─────────────────────────────────────────────────────────────
    public function edit($id)
    {
        $this->requireModule();
        $item       = InventoryItem::findOrFail($id);
        $categories = InventoryCategory::orderBy('name')->get();
        return view('admin.inventory.edit', compact('item', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $this->requireModule();
        $hotelId = $this->hotelId();

        $item = InventoryItem::findOrFail($id);

        $data = $request->validate([
            'name'          => 'required|string|max:150',
            'category_id'   => [
                'nullable',
                Rule::exists('inventory_categories', 'id')->where('hotel_id', $hotelId),
            ],
            'unit'          => 'required|string|max:30',
            'reorder_level' => 'nullable|numeric|min:0',
            'cost_price'    => 'nullable|numeric|min:0',
            'is_active'     => 'nullable|boolean',
        ]);

        $data['reorder_level'] = $data['reorder_level'] ?? 0;
        $data['cost_price']    = $data['cost_price'] ?? 0;
        $data['is_active']     = $request->boolean('is_active', true);

        $item->update($data);
        ActivityLogger::log('inventory_item_updated', 'Inventory', "Item '{$item->name}' updated");

        return redirect()->route('inventory.index')->with('success', "Item '{$item->name}' updated.");
    }

    // ── Delete Item ───────────────────────────────────────────────────────────
    public function destroy($id)
    {
        $this->requireModule();

        $item = InventoryItem::findOrFail($id);

        if ($item->movements()->count() > 0) {
            $item->update(['is_active' => false]);
            ActivityLogger::log('inventory_item_deactivated', 'Inventory', "Item '{$item->name}' deactivated (has movements)");
            return back()->with('success', "Item '{$item->name}' deactivated (cannot delete — has movement history).");
        }

        $name = $item->name;
        $item->delete();
        ActivityLogger::log('inventory_item_deleted', 'Inventory', "Item '{$name}' deleted");

        return back()->with('success', "Item '{$name}' deleted.");
    }

    // ── Movement History ──────────────────────────────────────────────────────
    public function movements($id)
    {
        $this->requireModule();

        $item      = InventoryItem::findOrFail($id);
        $movements = InventoryMovement::where('item_id', $id)
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.inventory.movements', compact('item', 'movements'));
    }

    // ── Purchase (add stock) ──────────────────────────────────────────────────
    public function purchase(Request $request, $id)
    {
        $this->requireModule();

        $data = $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'notes'    => 'nullable|string|max:255',
        ]);

        $item    = InventoryItem::findOrFail($id);
        $service = app(InventoryService::class);
        $service->recordMovement($item, 'purchase', (float) $data['quantity'], [
            'notes'      => $data['notes'] ?? null,
            'created_by' => session('crm_user_id'),
        ]);

        ActivityLogger::log('inventory_purchase', 'Inventory', "Purchased {$data['quantity']} {$item->unit} of '{$item->name}'");

        return back()->with('success', "Stock updated: +{$data['quantity']} {$item->unit} added to '{$item->name}'.");
    }

    // ── Usage / Wastage (deduct stock) ────────────────────────────────────────
    public function usage(Request $request, $id)
    {
        $this->requireModule();

        $data = $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'type'     => 'required|in:usage,wastage',
            'notes'    => 'nullable|string|max:255',
        ]);

        $item    = InventoryItem::findOrFail($id);
        $service = app(InventoryService::class);

        try {
            $service->recordMovement($item, $data['type'], (float) $data['quantity'], [
                'notes'      => $data['notes'] ?? null,
                'created_by' => session('crm_user_id'),
            ]);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $label = $data['type'] === 'wastage' ? 'Wasted' : 'Used';
        ActivityLogger::log('inventory_usage', 'Inventory', "{$label} {$data['quantity']} {$item->unit} of '{$item->name}'");

        return back()->with('success', "{$label} {$data['quantity']} {$item->unit} of '{$item->name}'.");
    }

    // ── Adjust Stock (set absolute) ───────────────────────────────────────────
    public function adjust(Request $request, $id)
    {
        $this->requireModule();

        $data = $request->validate([
            'new_stock' => 'required|numeric|min:0',
            'notes'     => 'nullable|string|max:255',
        ]);

        $item    = InventoryItem::findOrFail($id);
        $service = app(InventoryService::class);
        $service->adjustStock($item, (float) $data['new_stock'], $data['notes'] ?? 'Manual adjustment', (int) session('crm_user_id'));

        ActivityLogger::log('inventory_adjusted', 'Inventory', "Stock adjusted to {$data['new_stock']} {$item->unit} for '{$item->name}'");

        return back()->with('success', "Stock for '{$item->name}' set to {$data['new_stock']} {$item->unit}.");
    }

    // ── Categories ────────────────────────────────────────────────────────────
    public function categories()
    {
        $this->requireModule();
        $categories = InventoryCategory::withCount('items')->orderBy('name')->get();
        return view('admin.inventory.categories', compact('categories'));
    }

    public function categoryStore(Request $request)
    {
        $this->requireModule();

        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        $data['hotel_id'] = $this->hotelId();
        $cat = InventoryCategory::create($data);
        ActivityLogger::log('inventory_category_created', 'Inventory', "Category '{$cat->name}' created");

        return back()->with('success', "Category '{$cat->name}' added.");
    }

    public function categoryUpdate(Request $request, $id)
    {
        $this->requireModule();

        // Ensure the category belongs to this hotel (BelongsToHotel global scope enforces this)
        $cat  = InventoryCategory::findOrFail($id);
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        $cat->update($data);
        ActivityLogger::log('inventory_category_updated', 'Inventory', "Category '{$cat->name}' updated");

        return back()->with('success', "Category '{$cat->name}' updated.");
    }

    public function categoryDestroy($id)
    {
        $this->requireModule();

        // BelongsToHotel global scope ensures only this hotel's categories are found
        $cat = InventoryCategory::withCount('items')->findOrFail($id);

        if ($cat->items_count > 0) {
            return back()->with('error', "Cannot delete '{$cat->name}' — it has {$cat->items_count} item(s). Reassign or delete items first.");
        }

        $name = $cat->name;
        $cat->delete();
        ActivityLogger::log('inventory_category_deleted', 'Inventory', "Category '{$name}' deleted");

        return back()->with('success', "Category '{$name}' deleted.");
    }
}
