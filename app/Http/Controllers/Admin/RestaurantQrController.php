<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\Module;
use App\Models\Room;
use App\Models\RestaurantTable;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class RestaurantQrController extends Controller
{
    private function hotelId(): int
    {
        return (int) (session('crm_hotel_id') ?: session('crm_sa_hotel_filter'));
    }

    private function requireModule(): void
    {
        abort_unless(Module::isEnabled('restaurant'), 403, 'Restaurant module is not enabled for this hotel.');
    }

    private function buildWriter(int $size = 220, int $margin = 1): Writer
    {
        $renderer = new ImageRenderer(new RendererStyle($size, $margin), new SvgImageBackEnd());
        return new Writer($renderer);
    }

    public function index()
    {
        $this->requireModule();
        $hotel  = Hotel::findOrFail($this->hotelId());
        $tables = RestaurantTable::where('hotel_id', $hotel->id)->orderBy('name')->get();
        $rooms  = Room::where('hotel_id', $hotel->id)->orderBy('room_number')->get();

        $baseUrl = url('/r/' . $hotel->slug);
        $writer  = $this->buildWriter(220, 1);

        $qrSvgs = ['__general__' => $writer->writeString($baseUrl)];
        foreach ($tables as $t) {
            $qrSvgs['table_' . $t->id] = $writer->writeString($baseUrl . '/table/' . rawurlencode($t->name));
        }
        foreach ($rooms as $r) {
            $qrSvgs['room_' . $r->id] = $writer->writeString($baseUrl . '/room/' . rawurlencode($r->room_number));
        }

        return view('admin.restaurant.qr', compact('hotel', 'tables', 'rooms', 'baseUrl', 'qrSvgs'));
    }

    public function pdf()
    {
        $this->requireModule();
        $hotel  = Hotel::findOrFail($this->hotelId());
        $tables = RestaurantTable::where('hotel_id', $hotel->id)->orderBy('name')->get();
        $rooms  = Room::where('hotel_id', $hotel->id)->orderBy('room_number')->get();

        $baseUrl = url('/r/' . $hotel->slug);
        $writer  = $this->buildWriter(360, 2);

        $qrSvgs = ['__general__' => $writer->writeString($baseUrl)];
        foreach ($tables as $t) {
            $qrSvgs['table_' . $t->id] = $writer->writeString($baseUrl . '/table/' . rawurlencode($t->name));
        }
        foreach ($rooms as $r) {
            $qrSvgs['room_' . $r->id] = $writer->writeString($baseUrl . '/room/' . rawurlencode($r->room_number));
        }

        $pdf = Pdf::loadView('admin.restaurant.qr-pdf', compact('hotel', 'tables', 'rooms', 'qrSvgs', 'baseUrl'))
                  ->setPaper('a4', 'portrait');

        return $pdf->download('restaurant-qr-' . $hotel->slug . '.pdf');
    }

    public function download(Request $request)
    {
        $this->requireModule();
        $hotel = Hotel::findOrFail($this->hotelId());

        $table = $request->input('table');
        $room  = $request->input('room');
        $fmt   = $request->input('format', 'svg') === 'png' ? 'png' : 'svg';

        $url   = url('/r/' . $hotel->slug);
        $label = 'general';

        if ($table) {
            $url  .= '/table/' . rawurlencode($table);
            $label = 'table-' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $table);
        } elseif ($room) {
            $url  .= '/room/' . rawurlencode($room);
            $label = 'room-' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $room);
        }

        if ($fmt === 'png') {
            $png = $this->renderQrPng($url, 480, 16);
            return response($png, 200, [
                'Content-Type'        => 'image/png',
                'Content-Disposition' => 'attachment; filename="restaurant-qr-' . $label . '.png"',
            ]);
        }

        $svg = $this->buildWriter(400, 2)->writeString($url);
        return response($svg, 200, [
            'Content-Type'        => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="restaurant-qr-' . $label . '.svg"',
        ]);
    }

    /**
     * Render a QR code to PNG using the BaconQrCode matrix + GD.
     * (Avoids needing the Imagick PHP extension.)
     */
    private function renderQrPng(string $text, int $size = 480, int $margin = 16): string
    {
        $qr     = \BaconQrCode\Encoder\Encoder::encode($text, \BaconQrCode\Common\ErrorCorrectionLevel::M());
        $matrix = $qr->getMatrix();
        $w      = $matrix->getWidth();
        $h      = $matrix->getHeight();
        $array  = $matrix->getArray();

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
