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

        // Build a friendly label like "Table 5", "Room 101", "Restaurant Menu"
        if ($table) {
            $heading = 'TABLE';
            $title   = $table;
            $caption = 'SCAN TO ORDER';
            $accent  = [220, 38, 38]; // red
        } elseif ($room) {
            $heading = 'ROOM';
            $title   = $room;
            $caption = 'SCAN TO ORDER';
            $accent  = [14, 165, 233]; // sky blue
        } else {
            $heading = '';
            $title   = 'Restaurant Menu';
            $caption = 'SCAN TO VIEW MENU';
            $accent  = [220, 38, 38];
        }

        if ($fmt === 'png') {
            $png = $this->renderLabeledQrCard($url, $hotel->name, $heading, $title, $caption, $accent);
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
     * Render a printable labeled QR card (PNG) matching the on-screen design:
     * dashed border, hotel name, big label (Table 5 / Room 101 / Restaurant Menu),
     * QR code, and a caption.
     */
    private function renderLabeledQrCard(string $url, string $hotelName, string $heading, string $title, string $caption, array $accent): string
    {
        $fontBold = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
        $fontReg  = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
        $hasTtf   = function_exists('imagettftext') && is_readable($fontBold) && is_readable($fontReg);

        $cardW = 700;
        $cardH = 900;

        $img    = imagecreatetruecolor($cardW, $cardH);
        $white  = imagecolorallocate($img, 255, 255, 255);
        $black  = imagecolorallocate($img, 30, 41, 59);
        $muted  = imagecolorallocate($img, 100, 116, 139);
        $accent_color = imagecolorallocate($img, $accent[0], $accent[1], $accent[2]);
        imagefilledrectangle($img, 0, 0, $cardW, $cardH, $white);

        // Dashed border (rectangular dots) around the card
        $bx1 = 20; $by1 = 20; $bx2 = $cardW - 20; $by2 = $cardH - 20;
        $dash = 14; $gap = 8; $thick = 3;
        for ($x = $bx1; $x < $bx2; $x += $dash + $gap) {
            imagefilledrectangle($img, $x, $by1, min($x + $dash, $bx2), $by1 + $thick, $accent_color);
            imagefilledrectangle($img, $x, $by2 - $thick, min($x + $dash, $bx2), $by2, $accent_color);
        }
        for ($y = $by1; $y < $by2; $y += $dash + $gap) {
            imagefilledrectangle($img, $bx1, $y, $bx1 + $thick, min($y + $dash, $by2), $accent_color);
            imagefilledrectangle($img, $bx2 - $thick, $y, $bx2, min($y + $dash, $by2), $accent_color);
        }

        // Helpers
        $centerTtf = function (int $size, string $font, string $text, int $y, $color) use ($img, $cardW) {
            $bbox = imagettfbbox($size, 0, $font, $text);
            $textW = abs($bbox[2] - $bbox[0]);
            $x = (int) (($cardW - $textW) / 2) - $bbox[0];
            imagettftext($img, $size, 0, $x, $y, $color, $font, $text);
        };
        $centerBitmap = function (int $fontSize, string $text, int $y, $color) use ($img, $cardW) {
            $charW = imagefontwidth($fontSize);
            $textW = $charW * strlen($text);
            $x = (int) (($cardW - $textW) / 2);
            imagestring($img, $fontSize, $x, $y, $text, $color);
        };

        // Header text
        if ($hasTtf) {
            $centerTtf(18, $fontBold, strtoupper($hotelName), 80, $accent_color);
            if ($heading !== '') {
                $centerTtf(20, $fontReg, $heading, 130, $muted);
                $centerTtf(54, $fontBold, $title, 200, $accent_color);
            } else {
                $centerTtf(34, $fontBold, $title, 180, $black);
            }
        } else {
            $centerBitmap(5, strtoupper($hotelName), 60, $accent_color);
            if ($heading !== '') {
                $centerBitmap(4, $heading, 100, $muted);
                $centerBitmap(5, $title, 130, $accent_color);
            } else {
                $centerBitmap(5, $title, 110, $black);
            }
        }

        // Generate the QR matrix
        $qr     = \BaconQrCode\Encoder\Encoder::encode($url, \BaconQrCode\Common\ErrorCorrectionLevel::M());
        $matrix = $qr->getMatrix();
        $mw     = $matrix->getWidth();
        $mh     = $matrix->getHeight();
        $arr    = $matrix->getArray();

        // QR area
        $qrAreaTop  = 240;
        $qrAreaSize = 480;
        $qrCell     = (int) max(1, floor($qrAreaSize / max($mw, $mh)));
        $qrPxW      = $qrCell * $mw;
        $qrPxH      = $qrCell * $mh;
        $qrX        = (int) (($cardW - $qrPxW) / 2);
        $qrY        = $qrAreaTop;
        $qrBlack    = imagecolorallocate($img, 0, 0, 0);

        // White padding behind QR
        imagefilledrectangle($img, $qrX - 12, $qrY - 12, $qrX + $qrPxW + 12, $qrY + $qrPxH + 12, $white);
        for ($y = 0; $y < $mh; $y++) {
            for ($x = 0; $x < $mw; $x++) {
                if ($arr[$y][$x] === "\1" || $arr[$y][$x] === 1) {
                    imagefilledrectangle(
                        $img,
                        $qrX + $x * $qrCell,
                        $qrY + $y * $qrCell,
                        $qrX + ($x + 1) * $qrCell - 1,
                        $qrY + ($y + 1) * $qrCell - 1,
                        $qrBlack
                    );
                }
            }
        }

        // Caption + URL beneath QR
        $belowY = $qrY + $qrPxH + 50;
        if ($hasTtf) {
            $centerTtf(20, $fontBold, $caption, $belowY, $black);
            $shortUrl = strlen($url) > 70 ? substr($url, 0, 67) . '...' : $url;
            $centerTtf(11, $fontReg, $shortUrl, $belowY + 40, $muted);
        } else {
            $centerBitmap(4, $caption, $belowY, $black);
            $shortUrl = strlen($url) > 80 ? substr($url, 0, 77) . '...' : $url;
            $centerBitmap(2, $shortUrl, $belowY + 20, $muted);
        }

        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);
        return $png;
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
