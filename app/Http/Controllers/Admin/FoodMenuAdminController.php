<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FoodCategory;
use App\Models\FoodItem;
use App\Models\FoodOrder;
use App\Models\Hotel;
use App\Models\Module;
use App\Models\Room;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;

class FoodMenuAdminController extends Controller
{
    private function hotelId(): int
    {
        return (int) (session('crm_hotel_id') ?: session('crm_sa_hotel_filter'));
    }

    private function requireModule(): void
    {
        abort_unless(Module::isEnabled('food-menu'), 403, 'Food Menu module is not enabled for this hotel.');
    }

    // ── Dashboard ─────────────────────────────────────────────────────────────
    public function dashboard()
    {
        $this->requireModule();
        $hotelId = $this->hotelId();
        $today   = now()->startOfDay();

        $todayOrders   = FoodOrder::where('hotel_id', $hotelId)->whereDate('created_at', $today)->count();
        $pendingCount  = FoodOrder::where('hotel_id', $hotelId)->whereIn('status', ['pending', 'in_progress'])->count();
        $todayRevenue  = FoodOrder::where('hotel_id', $hotelId)->whereDate('created_at', $today)->where('status', 'approved')->sum('total_amount');
        $totalItems    = FoodItem::where('hotel_id', $hotelId)->count();
        $totalCategories = FoodCategory::where('hotel_id', $hotelId)->count();

        $recentOrders = FoodOrder::with('items')
            ->where('hotel_id', $hotelId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $menuItems = FoodItem::with('category')
            ->where('hotel_id', $hotelId)
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();

        return view('admin.food-menu.dashboard', compact(
            'todayOrders', 'pendingCount', 'todayRevenue', 'totalItems', 'totalCategories', 'recentOrders', 'menuItems'
        ));
    }

    // ── Categories ────────────────────────────────────────────────────────────
    public function categories()
    {
        $this->requireModule();
        $categories = FoodCategory::where('hotel_id', $this->hotelId())
            ->withCount('items')->orderBy('sort_order')->orderBy('name')->get();
        return view('admin.food-menu.categories', compact('categories'));
    }

    public function categoryStore(Request $request)
    {
        $this->requireModule();
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'sort_order'  => 'nullable|integer|min:0',
            'is_active'   => 'nullable|boolean',
        ]);
        $data['hotel_id']  = $this->hotelId();
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active']  = $request->boolean('is_active', true);

        $cat = FoodCategory::create($data);
        ActivityLogger::log('food_category_created', 'FoodMenu', "Category '{$cat->name}' added");
        return back()->with('success', "Category '{$cat->name}' added.");
    }

    public function categoryUpdate(Request $request, $id)
    {
        $this->requireModule();
        $cat  = FoodCategory::where('hotel_id', $this->hotelId())->findOrFail($id);
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'sort_order'  => 'nullable|integer|min:0',
            'is_active'   => 'nullable|boolean',
        ]);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active']  = $request->boolean('is_active', true);

        $cat->update($data);
        ActivityLogger::log('food_category_updated', 'FoodMenu', "Category '{$cat->name}' updated");
        return back()->with('success', "Category '{$cat->name}' updated.");
    }

    public function categoryDestroy($id)
    {
        $this->requireModule();
        $cat = FoodCategory::where('hotel_id', $this->hotelId())->withCount('items')->findOrFail($id);
        if ($cat->items_count > 0) {
            return back()->with('error', "Cannot delete '{$cat->name}' — it has {$cat->items_count} item(s). Move or delete items first.");
        }
        $name = $cat->name;
        $cat->delete();
        ActivityLogger::log('food_category_deleted', 'FoodMenu', "Category '{$name}' deleted");
        return back()->with('success', "Category '{$name}' deleted.");
    }

    // ── Items ─────────────────────────────────────────────────────────────────
    public function itemCreate()
    {
        $this->requireModule();
        $categories = FoodCategory::where('hotel_id', $this->hotelId())->where('is_active', true)->orderBy('name')->get();
        return view('admin.food-menu.item-form', ['item' => null, 'categories' => $categories]);
    }

    public function itemStore(Request $request)
    {
        $this->requireModule();
        $hotelId = $this->hotelId();

        $data = $request->validate([
            'name'        => 'required|string|max:150',
            'category_id' => [
                'nullable',
                \Illuminate\Validation\Rule::exists('food_categories', 'id')->where('hotel_id', $hotelId),
            ],
            'description' => 'nullable|string|max:1000',
            'price'       => 'required|numeric|min:0',
            'sort_order'  => 'nullable|integer|min:0',
            'is_available'=> 'nullable|boolean',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $data['hotel_id']     = $hotelId;
        $data['sort_order']   = $data['sort_order'] ?? 0;
        $data['is_available'] = $request->boolean('is_available', true);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store("food/{$hotelId}", 'public');
        }
        unset($data['image']);

        $item = FoodItem::create($data);
        ActivityLogger::log('food_item_created', 'FoodMenu', "Item '{$item->name}' added");
        return redirect()->route('food-menu.dashboard')->with('success', "Item '{$item->name}' added.");
    }

    public function itemEdit($id)
    {
        $this->requireModule();
        $hotelId = $this->hotelId();
        $item = FoodItem::where('hotel_id', $hotelId)->findOrFail($id);
        $categories = FoodCategory::where('hotel_id', $hotelId)->where('is_active', true)->orderBy('name')->get();
        return view('admin.food-menu.item-form', compact('item', 'categories'));
    }

    public function itemUpdate(Request $request, $id)
    {
        $this->requireModule();
        $hotelId = $this->hotelId();
        $item    = FoodItem::where('hotel_id', $hotelId)->findOrFail($id);

        $data = $request->validate([
            'name'        => 'required|string|max:150',
            'category_id' => [
                'nullable',
                \Illuminate\Validation\Rule::exists('food_categories', 'id')->where('hotel_id', $hotelId),
            ],
            'description' => 'nullable|string|max:1000',
            'price'       => 'required|numeric|min:0',
            'sort_order'  => 'nullable|integer|min:0',
            'is_available'=> 'nullable|boolean',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $data['sort_order']   = $data['sort_order'] ?? 0;
        $data['is_available'] = $request->boolean('is_available', true);

        if ($request->hasFile('image')) {
            if ($item->image_path) {
                Storage::disk('public')->delete($item->image_path);
            }
            $data['image_path'] = $request->file('image')->store("food/{$hotelId}", 'public');
        }
        unset($data['image']);

        $item->update($data);
        ActivityLogger::log('food_item_updated', 'FoodMenu', "Item '{$item->name}' updated");
        return redirect()->route('food-menu.dashboard')->with('success', "Item '{$item->name}' updated.");
    }

    public function itemDestroy($id)
    {
        $this->requireModule();
        $item = FoodItem::where('hotel_id', $this->hotelId())->findOrFail($id);

        if ($item->image_path) {
            Storage::disk('public')->delete($item->image_path);
        }
        $name = $item->name;
        $item->delete();
        ActivityLogger::log('food_item_deleted', 'FoodMenu', "Item '{$name}' deleted");
        return back()->with('success', "Item '{$name}' deleted.");
    }

    public function itemToggle($id)
    {
        $this->requireModule();
        $item = FoodItem::where('hotel_id', $this->hotelId())->findOrFail($id);
        $item->update(['is_available' => ! $item->is_available]);
        return back()->with('success', "Item '{$item->name}' " . ($item->is_available ? 'enabled' : 'disabled') . '.');
    }

    // ── QR Code Page ──────────────────────────────────────────────────────────
    public function qr()
    {
        $this->requireModule();
        $hotelId = $this->hotelId();
        $hotel = Hotel::findOrFail($hotelId);
        $rooms = Room::where('hotel_id', $hotelId)->orderBy('room_number')->get();

        $baseUrl = url('/menu/' . $hotel->slug);

        // Pre-render SVG QR codes server-side (Bacon QR Code, no JS needed)
        $renderer = new ImageRenderer(new RendererStyle(220, 1), new SvgImageBackEnd());
        $writer   = new Writer($renderer);

        $qrSvgs = [];
        $qrSvgs['__general__'] = $writer->writeString($baseUrl);
        foreach ($rooms as $room) {
            $qrSvgs[$room->id] = $writer->writeString($baseUrl . '/' . rawurlencode($room->room_number));
        }

        return view('admin.food-menu.qr', compact('hotel', 'rooms', 'baseUrl', 'qrSvgs'));
    }

    // ── QR Print PDF (printable single PDF, all rooms) ────────────────────────
    public function qrPdf()
    {
        $this->requireModule();
        $hotelId = $this->hotelId();
        $hotel = Hotel::findOrFail($hotelId);
        $rooms = Room::where('hotel_id', $hotelId)->orderBy('room_number')->get();

        $baseUrl = url('/menu/' . $hotel->slug);
        // Higher-resolution QR for print (PDF-embeddable SVG)
        $renderer = new ImageRenderer(new RendererStyle(360, 2), new SvgImageBackEnd());
        $writer   = new Writer($renderer);

        $qrSvgs = ['__general__' => $writer->writeString($baseUrl)];
        foreach ($rooms as $room) {
            $qrSvgs[$room->id] = $writer->writeString($baseUrl . '/' . rawurlencode($room->room_number));
        }

        $pdf = Pdf::loadView('admin.food-menu.qr-pdf', compact('hotel', 'rooms', 'qrSvgs', 'baseUrl'))
                  ->setPaper('a4', 'portrait');

        return $pdf->download('food-menu-qr-' . $hotel->slug . '.pdf');
    }

    // ── QR Download (single QR as SVG or PNG) ─────────────────────────────────
    public function qrDownload(Request $request)
    {
        $this->requireModule();
        $hotelId = $this->hotelId();
        $hotel = Hotel::findOrFail($hotelId);

        $room = $request->input('room');
        $fmt  = $request->input('format', 'svg') === 'png' ? 'png' : 'svg';
        $url  = url('/menu/' . $hotel->slug) . ($room ? '/' . rawurlencode($room) : '');

        if ($fmt === 'png') {
            $png      = $this->renderQrPng($url, 480, 16);
            $filename = 'food-menu-qr-' . ($room ?: 'general') . '.png';
            return response($png, 200, [
                'Content-Type'        => 'image/png',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        $renderer = new ImageRenderer(new RendererStyle(400, 2), new SvgImageBackEnd());
        $writer   = new Writer($renderer);
        $svg      = $writer->writeString($url);

        $filename = 'food-menu-qr-' . ($room ?: 'general') . '.svg';
        return response($svg, 200, [
            'Content-Type'        => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Render a QR code to PNG using the BaconQrCode matrix + GD.
     * Avoids needing the Imagick PHP extension.
     */
    private function renderQrPng(string $text, int $size = 480, int $margin = 16): string
    {
        $qr      = \BaconQrCode\Encoder\Encoder::encode($text, \BaconQrCode\Common\ErrorCorrectionLevel::M());
        $matrix  = $qr->getMatrix();
        $w       = $matrix->getWidth();
        $h       = $matrix->getHeight();
        $array   = $matrix->getArray();

        $cell = (int) max(1, floor(($size - 2 * $margin) / max($w, $h)));
        $imgW = $cell * $w + 2 * $margin;
        $imgH = $cell * $h + 2 * $margin;

        $img   = imagecreatetruecolor($imgW, $imgH);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagefilledrectangle($img, 0, 0, $imgW, $imgH, $white);

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                if ($array[$y][$x] === "\1" || $array[$y][$x] === 1) {
                    imagefilledrectangle(
                        $img,
                        $margin + $x * $cell,
                        $margin + $y * $cell,
                        $margin + ($x + 1) * $cell - 1,
                        $margin + ($y + 1) * $cell - 1,
                        $black
                    );
                }
            }
        }

        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);
        return $png;
    }
}
