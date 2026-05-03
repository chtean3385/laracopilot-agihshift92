<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RestaurantMenuCategory;
use App\Models\RestaurantMenuItem;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RestaurantMenuController extends Controller
{
    private function hotelId(): int
    {
        return (int) (session('crm_hotel_id') ?: session('crm_sa_hotel_filter'));
    }

    // Menu management page
    public function index()
    {
        $categories = RestaurantMenuCategory::with('items')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.restaurant.menu', compact('categories'));
    }

    // Store category
    public function categoryStore(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100']);

        $category = RestaurantMenuCategory::create([
            'hotel_id'   => $this->hotelId(),
            'name'       => $request->name,
            'sort_order' => $request->sort_order ?? 0,
        ]);

        ActivityLogger::log('restaurant_category_created', 'Restaurant', "Category '{$category->name}' created");

        return back()->with('success', "Category '{$category->name}' added.");
    }

    // Update category
    public function categoryUpdate(Request $request, $id)
    {
        $request->validate(['name' => 'required|string|max:100']);

        $category = RestaurantMenuCategory::findOrFail($id);
        $category->update([
            'name'       => $request->name,
            'sort_order' => $request->sort_order ?? $category->sort_order,
        ]);

        return back()->with('success', 'Category updated.');
    }

    // Delete category
    public function categoryDestroy($id)
    {
        $category = RestaurantMenuCategory::findOrFail($id);

        if ($category->items()->count() > 0) {
            return back()->with('error', 'Cannot delete category with menu items. Delete items first.');
        }

        $category->delete();

        return back()->with('success', 'Category deleted.');
    }

    // Store menu item
    public function itemStore(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:150',
            'price'       => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:restaurant_menu_categories,id',
            'food_type'   => 'required|in:veg,nonveg,beverage',
            'description' => 'nullable|string|max:500',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $hotelId = $this->hotelId();
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store("restaurant/{$hotelId}", 'public');
        }

        $item = RestaurantMenuItem::create([
            'hotel_id'    => $hotelId,
            'category_id' => $request->category_id,
            'name'        => $request->name,
            'description' => $request->description,
            'image_path'  => $imagePath,
            'price'       => $request->price,
            'food_type'   => $request->food_type,
            'sort_order'  => $request->sort_order ?? 0,
            'is_available'=> true,
        ]);

        ActivityLogger::log('restaurant_item_created', 'Restaurant', "Menu item '{$item->name}' added");

        return back()->with('success', "'{$item->name}' added to menu.");
    }

    // Update menu item
    public function itemUpdate(Request $request, $id)
    {
        $request->validate([
            'name'         => 'required|string|max:150',
            'price'        => 'required|numeric|min:0',
            'category_id'  => 'nullable|exists:restaurant_menu_categories,id',
            'food_type'    => 'required|in:veg,nonveg,beverage',
            'image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_image' => 'nullable|boolean',
        ]);

        $item = RestaurantMenuItem::findOrFail($id);

        $data = [
            'category_id' => $request->category_id,
            'name'        => $request->name,
            'description' => $request->description,
            'price'       => $request->price,
            'food_type'   => $request->food_type,
            'sort_order'  => $request->sort_order ?? $item->sort_order,
        ];

        if ($request->boolean('remove_image') && $item->image_path) {
            Storage::disk('public')->delete($item->image_path);
            $data['image_path'] = null;
        }

        if ($request->hasFile('image')) {
            if ($item->image_path) {
                Storage::disk('public')->delete($item->image_path);
            }
            $hotelId = $this->hotelId();
            $data['image_path'] = $request->file('image')->store("restaurant/{$hotelId}", 'public');
        }

        $item->update($data);

        return back()->with('success', "'{$item->name}' updated.");
    }

    // Delete menu item
    public function itemDestroy($id)
    {
        $item = RestaurantMenuItem::findOrFail($id);
        $name = $item->name;
        if ($item->image_path) {
            Storage::disk('public')->delete($item->image_path);
        }
        $item->delete();

        return back()->with('success', "'{$name}' removed from menu.");
    }

    // Toggle item availability
    public function itemToggle($id)
    {
        $item = RestaurantMenuItem::findOrFail($id);
        $item->update(['is_available' => !$item->is_available]);

        $status = $item->is_available ? 'available' : 'unavailable';
        return response()->json(['success' => true, 'is_available' => $item->is_available, 'status' => $status]);
    }
}
