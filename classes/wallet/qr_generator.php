<?php
// This file is part of the Blerify Certificate module for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Branded QR code generator with Blerify logo overlay.
 *
 * Generates QR codes with high error correction (H, 30%) so a centered
 * logo can be overlaid without breaking scannability.
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_blerify\wallet;

defined('MOODLE_INTERNAL') || die();

class qr_generator {

    /** @var array QR module color in RGB (black). */
    const BRAND_COLOR = [0, 0, 0];

    /**
     * Generate a branded QR code with the Blerify logo centered.
     *
     * Uses core_qrcode with error correction level H (30%) so the logo
     * overlay does not break scannability.
     *
     * @param string $data The data to encode in the QR.
     * @return string Base64-encoded PNG image data.
     */
    public static function generate(string $data): string {
        global $CFG;

        $logopath = $CFG->dirroot . '/mod/blerify/pix/blerify.png';

        $qr = new \core_qrcode($data, 'QRCODE,H');
        $qrpng = $qr->getBarcodePngData(6, 6, self::BRAND_COLOR);

        if (empty($qrpng)) {
            $qr = new \core_qrcode($data, 'QRCODE,H');
            return base64_encode($qr->getBarcodePngData(4, 4));
        }

        $qrimg = imagecreatefromstring($qrpng);
        if (!$qrimg || !file_exists($logopath)) {
            return base64_encode($qrpng);
        }

        $qrw = imagesx($qrimg);
        $qrh = imagesy($qrimg);

        $logo = imagecreatefrompng($logopath);
        if (!$logo) {
            return base64_encode($qrpng);
        }

        $logow = imagesx($logo);
        $logoh = imagesy($logo);
        $targetsize = (int) ($qrw * 0.13);

        $pad = 5;
        $innersize = $targetsize - $pad * 2;
        $scaled = imagecreatetruecolor($targetsize, $targetsize);
        $white = imagecolorallocate($scaled, 255, 255, 255);
        imagefill($scaled, 0, 0, $white);
        imagealphablending($scaled, true);
        imagecopyresampled($scaled, $logo, $pad, $pad, 0, 0, $innersize, $innersize, $logow, $logoh);

        $cx = (int) ($qrw / 2);
        $cy = (int) ($qrh / 2);
        imagealphablending($qrimg, true);
        imagecopy(
            $qrimg,
            $scaled,
            $cx - (int) ($targetsize / 2),
            $cy - (int) ($targetsize / 2),
            0, 0,
            $targetsize, $targetsize
        );

        ob_start();
        imagepng($qrimg);
        $result = ob_get_clean();

        imagedestroy($qrimg);
        imagedestroy($logo);
        imagedestroy($scaled);

        return base64_encode($result);
    }
}
