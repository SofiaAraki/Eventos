<?php

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCodeService
{
    private static function getSecret()
    {
        return getenv('QRCODE_TOKEN') ?: 'sua_chave_secreta_aqui_123';
    }

    public static function generateToken(Inscricao $inscricao)
    {
        $raw_data = "insc:{$inscricao->id_inscricao}|ev:{$inscricao->id_evento}|user:{$inscricao->id_usuario}";
        $hash = hash_hmac('sha256', $raw_data, self::getSecret());
        
        return base64_encode($raw_data) . '.' . $hash;
    }

    public static function validateToken($token)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            throw new Exception('QR Code com formato inválido.');
        }

        $data_base64 = $parts[0];
        $hash_received = $parts[1];
        $raw_data = base64_decode($data_base64);

        $expected_hash = hash_hmac('sha256', $raw_data, self::getSecret());
        if (!hash_equals($expected_hash, $hash_received)) {
            throw new Exception('QrCode não autorizado ou violado!');
        }

        // Extrai os dados (ex: insc:123|ev:45|user:7)
        preg_match('/insc:(\d+)/', $raw_data, $matches_insc);
        preg_match('/ev:(\d+)/', $raw_data, $matches_ev);
        preg_match('/user:(\d+)/', $raw_data, $matches_user);

        return [
            'id_inscricao' => $matches_insc[1] ?? null,
            'id_evento'    => $matches_ev[1] ?? null,
            'id_usuario'   => $matches_user[1] ?? null
        ];
    }

    public static function generateCertificadoHash(Inscricao $inscricao)
    {
        $raw = "cert|insc:{$inscricao->id_inscricao}|user:{$inscricao->id_usuario}|ev:{$inscricao->id_evento}";
        $signature = hash_hmac('sha1', $raw, self::getSecret());
        return strtoupper(substr($signature, 0, 16));
    }

    public static function getCertificadoQrCodeBase64(string $url)
    {
        // try {
        //     // Encodifica a URL para ser enviada na requisição HTTP
        //     $chartUrl = "https://quickchart.io/qr?text=" . urlencode($url) . "&size=150&margin=1";
            
        //     // Baixa os bytes da imagem PNG gerada
        //     $imageBytes = @file_get_contents($chartUrl);

        //     if ($imageBytes !== false) {
        //         return 'data:image/png;base64,' . base64_encode($imageBytes);
        //     }
        // } catch (Exception $e) {
        //     // Trata a exceção silenciosamente em caso de offline
        // }

        // FALLBACK: Usando BaconQrCode + Imagick (padrão Adianti recente)
        if (extension_loaded('imagick') && class_exists('BaconQrCode\Writer')) {
            $renderer = new ImageRenderer(
                new RendererStyle(150, 1),
                new ImagickImageBackEnd()
            );
            
            $writer = new Writer($renderer);
            $pngData = $writer->writeString($url);
            
            return 'data:image/png;base64,' . base64_encode($pngData);
        }

        return '';
    }
}
