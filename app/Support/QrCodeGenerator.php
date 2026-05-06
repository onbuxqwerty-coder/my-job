<?php

declare(strict_types=1);

namespace App\Support;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCodeGenerator
{
    /**
     * Генерує EPC QR-код для банківського переказу (SEPA Credit Transfer).
     */
    public static function epc(
        string $recipientName,
        string $iban,
        float  $amount,
        string $purpose,
        string $edrpou = ''
    ): string {
        $lines = [
            'BCD',
            '002',
            '1',
            'SCT',
            '',
            $recipientName,
            $iban,
            'UAH' . number_format($amount, 2, '.', ''),
            '',
            $purpose,
            $edrpou,
        ];

        $payload = implode("\n", $lines);

        $renderer = new ImageRenderer(
            new RendererStyle(300),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        return $writer->writeString($payload);
    }
}
