<?php

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd; // <--- USAR SVG EM VEZ DE GD
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
        try {
            $chartUrl = "https://quickchart.io/qr?text=" . urlencode($url) . "&size=150&margin=1";
            $imageBytes = @file_get_contents($chartUrl);

            if ($imageBytes !== false) {
                return 'data:image/png;base64,' . base64_encode($imageBytes);
            }
        } catch (Exception $e) {
            // Silencioso
        }

        // FALLBACK USANDO SVG (100% nativo no XAMPP e Laragon)
        if (class_exists('BaconQrCode\Writer')) {
            $renderer = new ImageRenderer(
                new RendererStyle(150, 1),
                new SvgImageBackEnd()
            );
            
            $writer = new Writer($renderer);
            $svgData = $writer->writeString($url);
            
            return 'data:image/svg+xml;base64,' . base64_encode($svgData);
        }

        return '';
    }

    public static function gerarPdfCracha($key)
    {
        if (empty($key)) {
            throw new Exception('ID da inscrição não informado.');
        }

        TTransaction::open('teste');

        $inscricao = new Inscricao($key);

        if ($inscricao->id_usuario != TSession::getValue('userid')) {
            TTransaction::close();
            throw new Exception('Acesso negado.');
        }

        $evento = new Evento($inscricao->id_evento);

        $conteudo_qr = self::generateToken($inscricao);

        $renderer = new ImageRenderer(
            new RendererStyle(140, 1),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $svgData = $writer->writeString($conteudo_qr);
        if (strpos($svgData, 'preserveAspectRatio') === false) {
            $svgData = str_replace('<svg ', '<svg preserveAspectRatio="xMidYMid meet" ', $svgData);
        }

        $base64QrCode = 'data:image/svg+xml;base64,' . base64_encode($svgData);

        $caminhoLogoCompleta = getcwd() . '/fafram-logo.png';
        $logoCompletaBase64 = '';
        if (file_exists($caminhoLogoCompleta)) {
            $data = base64_encode(file_get_contents($caminhoLogoCompleta));
            $mime = mime_content_type($caminhoLogoCompleta);
            $logoCompletaBase64 = "data:{$mime};base64,{$data}";
        }

        $caminhoFavicon = getcwd() . '/favicon.png';
        $faviconBase64 = '';
        if (file_exists($caminhoFavicon)) {
            $data = base64_encode(file_get_contents($caminhoFavicon));
            $mime = mime_content_type($caminhoFavicon);
            $faviconBase64 = "data:{$mime};base64,{$data}";
        }

        $nomeParticipante = mb_strtoupper($inscricao->usuario_name ?? 'PARTICIPANTE', 'UTF-8');
        $nomeEvento = mb_strtoupper($evento->titulo_evento ?? $inscricao->evento_name ?? 'EVENTO', 'UTF-8');
        $tipoParticipacao = mb_strtoupper($inscricao->tipo_participacao ?? 'ALUNO', 'UTF-8');

        TTransaction::close();

        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <style>
                @page {
                    margin: 0px;
                }
                body {
                    font-family: 'Helvetica Neue', Arial, sans-serif;
                    margin: 0px;
                    padding: 0px;
                    background-color: #ffffff;
                    color: #1e293b;
                }
                .badge-card {
                    width: 100%;
                    height: 100%;
                    text-align: center;
                    position: relative;
                }

                /* Marca d'água centralizada no fundo */
                .bg-watermark {
                    position: absolute;
                    top: 150px;
                    width: 300px;
                    left: 7%;
                    opacity: 0.1;
                    z-index: -1;
                }

                /* Cabeçalho branco para dar destaque à logo e à escrita FAFRAM */
                .header-gradient {
                    background: #ffffff;
                    padding: 16px 10px 10px 10px;
                    text-align: center;
                    border-bottom: 4px solid #ea580c;
                }
                .logo-header {
                    height: 42px;
                    width: auto;
                    margin: 0 auto 6px auto;
                    display: block;
                }
                .header-subtitle {
                    color: #1e3a8a;
                    font-size: 9px;
                    font-weight: 800;
                    letter-spacing: 1.5px;
                    text-transform: uppercase;
                }

                .content {
                    padding: 12px;
                }
                .event-name {
                    font-size: 10px;
                    font-weight: 700;
                    color: #2563eb;
                    margin-bottom: 6px;
                    text-transform: uppercase;
                    line-height: 1.2;
                }
                .participant-name {
                    font-size: 13px;
                    font-weight: 800;
                    color: #0f172a;
                    margin-bottom: 6px;
                    line-height: 1.2;
                }
                .role-badge {
                    display: inline-block;
                    background-color: #fff7ed;
                    color: #ea580c;
                    border: 1px solid #ffedd5;
                    font-size: 9px;
                    font-weight: 700;
                    padding: 3px 10px;
                    border-radius: 10px;
                    letter-spacing: 0.5px;
                    margin-bottom: 8px;
                }

                .qr-container {
                    background: #ffffff;
                    border: 2px dashed #cbd5e1;
                    border-radius: 10px;
                    padding: 6px;
                    display: inline-block;
                    margin-bottom: 4px;
                    text-align: center;
                }
                .qr-img {
                    width: 120px;
                    height: auto; /* Mantém a proporção e impede que achate */
                    max-width: 120px;
                    display: block;
                    margin: 0 auto;
                    object-fit: contain; /* Garante que o SVG mantenha a proporção */
                }
                .inscription-id {
                    font-size: 9px;
                    font-weight: bold;
                    color: #64748b;
                }
                .footer-notice {
                    font-size: 8px;
                    color: #94a3b8;
                    margin-top: 2px;
                }
            </style>
        </head>
        <body>
            <div class='badge-card'>
                " . ($faviconBase64 ? "<img src='{$faviconBase64}' class='bg-watermark'>" : "") . "

                <div class='header-gradient'>
                    " . ($logoCompletaBase64 ? "<img src='{$logoCompletaBase64}' class='logo-header'>" : "") . "
                    <div class='header-subtitle'>Credencial de Acesso</div>
                </div>

                <div class='content'>
                    <div class='event-name'>{$nomeEvento}</div>
                    <div class='participant-name'>{$nomeParticipante}</div>
                    <div class='role-badge'>{$tipoParticipacao}</div>

                    <div>
                        <div class='qr-container'>
                            <img src='{$base64QrCode}' class='qr-img'>
                        </div>
                    </div>

                    <div class='inscription-id'>INSCRIÇÃO: #{$inscricao->id_inscricao}</div>
                    <div class='footer-notice'>Apresente este QR Code na portaria do evento</div>
                </div>
            </div>
        </body>
        </html>";

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper([0, 0, 260, 380]);
        $dompdf->render();

        $file_path = 'app/output/qrcode_insc_' . $key . '.pdf';
        file_put_contents(getcwd() . '/' . $file_path, $dompdf->output());

        $window = TWindow::create('Crachá de Identificação', 0.45, 0.85);
        $embed = new TElement('object');
        $embed->data = 'download.php?file=' . $file_path;
        $embed->type = 'application/pdf';
        $embed->style = "width: 100%; height: calc(100% - 10px)";
        $window->add($embed);
        $window->show();
    }
}