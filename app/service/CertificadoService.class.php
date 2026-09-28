<?php
class CertificadoService
{
    public static function getTexto(Inscricao $inscricao, Certificado $modelo, Evento $evento)
    {
        TTransaction::open('teste');
        $user = new SystemUser($inscricao->id_usuario);
        TTransaction::close();

        $tipo = $inscricao->tipo_participacao;
        $dataEvento = self::formatarPeriodoEvento($evento->data_inicio_evento, $evento->data_fim_evento);
        $dataEmissao = self::formatarDataExtenso($modelo->data_emissao_certificado);
        $dataTcc = self::formatarDataExtenso($evento->data_inicio_evento);

        // Garante a existência do código de validação
        if (empty($inscricao->cod_validador)) {
            $inscricao->cod_validador = QrCodeService::generateCertificadoHash($inscricao);
            $inscricao->store();
        }

        $textoBase = "";

        switch ($tipo) {
            case 'autor':
                $tcc = self::getTccByEvento($evento->id_evento);
                $orientador = $tcc->orientador_name;
                $banca = $tcc->banca_names;
                $textoBase = "A FAFRAM - Faculdade Dr. Francisco Maeda, pelo presente, certifica que <strong>{$user->name}</strong> apresentou seu Trabalho de Graduação intitulado <strong>\"{$tcc->titulo_tcc}\"</strong>, Defendido em {$dataTcc}, orientado por <strong>{$orientador}</strong>, avaliado pelos membros da banca <strong>{$banca}</strong>.<br><br>Ituverava, {$dataEmissao}.";
                break;
            case 'orientador':
                $tcc = self::getTccByEvento($evento->id_evento);
                $autores = $tcc->autores_names;
                $banca = $tcc->banca_names;
                $textoBase = "A FAFRAM - Faculdade Dr. Francisco Maeda, pelo presente, certifica que <strong>{$user->name}</strong> orientou o Trabalho de Graduação intitulado <strong>\"{$tcc->titulo_tcc}\"</strong> apresentado por <strong>{$autores}</strong>, Defendido em {$dataTcc}, tendo como membros examinadores <strong>{$banca}</strong>.<br><br>Ituverava, {$dataEmissao}.";
                break;
            case 'banca':
                $tcc = self::getTccByEvento($evento->id_evento);
                $autores = $tcc->autores_names;
                $orientador = $tcc->orientador_name;
                $textoBase = "A FAFRAM - Faculdade Dr. Francisco Maeda, pelo presente, certifica que <strong>{$user->name}</strong> participou como membro da banca examinadora do Trabalho de Graduação intitulado <strong>\"{$tcc->titulo_tcc}\"</strong> apresentado por <strong>{$autores}</strong>, orientado por <strong>{$orientador}</strong>, Defendido em {$dataTcc}.<br><br>Ituverava, {$dataEmissao}.";
                break;
            case 'palestrante':
                $textoBase = "A FAFRAM - Faculdade Dr. Francisco Maeda, pelo presente, certifica que <strong>{$user->name}</strong> Ministrou a palestra \"{$evento->titulo_evento}\", {$dataEvento}, com carga horária de {$modelo->carga_horaria_certificado} horas.<br><br>Ituverava, {$dataEmissao}.";
                break;
            case 'organizador':
                $textoBase = "A FAFRAM - Faculdade Dr. Francisco Maeda, pelo presente, certifica que <strong>{$user->name}</strong> participou da organização do evento <strong>\"{$evento->titulo_evento}\"</strong>, {$dataEvento}, com carga horária de {$modelo->carga_horaria_certificado} horas.<br><br>Ituverava, {$dataEmissao}.";
                break;
            case 'professor':
                $textoBase = "A FAFRAM - Faculdade Dr. Francisco Maeda, pelo presente, certifica que <strong>{$user->name}</strong> participou como professor do evento <strong>\"{$evento->titulo_evento}\"</strong>, {$dataEvento}, com carga horária de {$modelo->carga_horaria_certificado} horas.<br><br>Ituverava, {$dataEmissao}.";
                break;
            case 'monitor':
                $textoBase = "A FAFRAM - Faculdade Dr. Francisco Maeda, pelo presente, certifica que <strong>{$user->name}</strong> participou como monitor do evento <strong>\"{$evento->titulo_evento}\"</strong>, {$dataEvento}, com carga horária de {$modelo->carga_horaria_certificado} horas.<br><br>Ituverava, {$dataEmissao}.";
                break;
            default:
                $textoBase = "A FAFRAM - Faculdade Dr. Francisco Maeda, pelo presente, certifica que <strong>{$user->name}</strong> portador(a) do <strong>RG: {$user->rg}</strong>, participou do evento <strong>\"{$evento->titulo_evento}\"</strong>, {$dataEvento}, com carga horária de {$modelo->carga_horaria_certificado} horas.<br><br>Ituverava, {$dataEmissao}.";
                break;
        }

        return $textoBase;
    }

    private static function getTccByEvento($id_evento)
    {
        $tcc = Tcc::where('id_evento', '=', $id_evento)->first();
        if (!$tcc) throw new Exception("TCC não encontrado para este evento.");
        return $tcc;
    }

    public static function formatarDataExtenso($date)
    {
        if (!$date) return '';
        $date = new DateTime($date);
        $meses = [1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',5=>'maio',6=>'junho',7=>'julho',8=>'agosto',9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'];
        return $date->format('d') . " de " . $meses[(int)$date->format('m')] . " de " . $date->format('Y');
    }

    public static function formatarPeriodoEvento($inicio, $fim)
    {
        $inicio_ext = self::formatarDataExtenso($inicio);
        $fim_ext = self::formatarDataExtenso($fim);
        return ($inicio_ext === $fim_ext) ? "realizado em {$inicio_ext}" : "realizado de {$inicio_ext} até {$fim_ext}";
    }

    public static function emitir(int $id_inscricao)
    {
        TTransaction::open('teste');

        $inscricao = new Inscricao($id_inscricao);

        if ($inscricao->id_usuario != TSession::getValue('userid')) {
            TTransaction::close();
            throw new Exception('Acesso negado.');
        }

        if ($inscricao->status_inscricao != 1) {
            TTransaction::close();
            throw new Exception('O certificado estará disponível apenas após a confirmação da sua presença.');
        }

        $registro = Registro::where('id_inscricao', '=', $inscricao->id_inscricao)->first();

        if (!$registro) {
            $evento = new Evento($inscricao->id_evento);
            $certificadoModelo = Certificado::where('id_evento', '=', $evento->id_evento)
                                            ->where('tipo_participacao', '=', $inscricao->tipo_participacao)
                                            ->first();

            if (!$certificadoModelo) {
                TTransaction::close();
                throw new Exception('Não há modelo de certificado para este tipo de participação.');
            }

            $texto = self::getTexto($inscricao, $certificadoModelo, $evento);

            $registro = new Registro;
            $registro->id_inscricao = $inscricao->id_inscricao;
            $registro->id_certificado = $certificadoModelo->id_certificado;
            $registro->descricao_certificado = $texto;
            $registro->data_registro = date('Y-m-d H:i:s');
            $registro->store();
        }

        $certificadoModelo = $registro->certificado;
        $conteudo = $registro->descricao_certificado;
        $cod_validador = $inscricao->cod_validador ?? '';

        TTransaction::close();

        self::gerarPdfCertificado(
            $conteudo,
            $registro->id_registro,
            $certificadoModelo,
            $cod_validador
        );
    }

    public static function gerarPdfCertificado(string $texto, int $registro_id, Certificado $certificado, string $cod_validador = '')
    {   
        if (empty($cod_validador)) {
            TTransaction::open('teste');
            $registro = new Registro($registro_id);
            if (!empty($registro->id_inscricao)) {
                $inscricao = new Inscricao($registro->id_inscricao);
                $cod_validador = $inscricao->cod_validador ?? '';
            }
            TTransaction::close();
        }

        $bg_campo = $certificado->bg_frente_certificado ?? $certificado->bg_frente ?? '';
        $bg_nome  = !empty(trim($bg_campo)) ? trim($bg_campo) : 'fafram.png';

        $bg_full_path = getcwd() . '/app/images/certificados/' . $bg_nome;

        $bg_src = '';
        if (is_file($bg_full_path)) {
            $bg_data = base64_encode(file_get_contents($bg_full_path));
            $bg_src = "data:image/png;base64,$bg_data";
        }

        $baseUrl = "https://portal.feituverava.com.br/eventos/index.php?class=Validador";
        $qrcode_src = '';

        if (!empty($cod_validador)) {
            $fullUrl = $baseUrl . "&cod_validador=" . $cod_validador;
            $qr_raw = QrCodeService::getCertificadoQrCodeBase64($fullUrl);
            
            if (!empty($qr_raw)) {
                $qr_clean = trim(str_replace(["\r", "\n"], '', $qr_raw));
                
                if (strpos($qr_clean, 'data:image') === 0) {
                    $qrcode_src = $qr_clean;
                } else {
                    $qrcode_src = 'data:image/png;base64,' . $qr_clean;
                }
            }
        }

        $bg_html = $bg_src ? "<img src='{$bg_src}' style='position: absolute; left: 0px; top: 0px; width: 100%; height: 100%; z-index: -1000;' />" : "";

        $html = "
        <html>
        <head>
            <meta charset='utf-8'>
            <style>
                @page { margin: 0px; }
                body { margin: 0px; padding: 0px; width: 100%; height: 100%; font-family: Arial, sans-serif; }
                .container { position: relative; padding: 40px 80px 20px 80px; text-align: center; }
                .texto-certificado { font-size: 20px; line-height: 1.6; margin-top: 270px; margin-bottom: 20px; }
                .bloco-validacao { font-size: 10px; color: #333; text-align: right; line-height: 1.2; margin-top: 10px; margin-right: 10px; }
                .img-qrcode { width: 55px; height: 55px; margin-bottom: 2px; }
            </style>
        </head>
        <body>
            {$bg_html}
            <div class='container'>
                <div class='texto-certificado'>
                    {$texto}
                </div>
                <div class='bloco-validacao'>
                    " . ($qrcode_src ? "<img src='{$qrcode_src}' class='img-qrcode'><br>" : "") . "
                    <b>Validador:</b> {$baseUrl}<br>
                    <b>Cód. Validação:</b> {$cod_validador}
                </div>
            </div>          
        </body>
        </html>";

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        
        $file_path = 'app/output/certificado_' . $registro_id . '.pdf';
        file_put_contents(getcwd() . '/' . $file_path, $dompdf->output());

        $window = TWindow::create('Certificado', 0.8, 0.9);
        $object = new TElement('object');
        $object->data = "download.php?file=$file_path";
        $object->type = 'application/pdf';
        $object->style = "width: 100%; height: calc(100% - 10px)";
        $window->add($object);
        $window->show();
    }

    public static function gerarPdfManual(EmissaoManual $emissao)
    {   
        $bg_nome = !empty(trim($emissao->bg_frente_certificado)) ? trim($emissao->bg_frente_certificado) : 'fafram.png';
        $bg_full_path = getcwd() . '/app/images/certificados/' . $bg_nome;

        $bg_src = '';
        if (is_file($bg_full_path)) {
            $bg_data = base64_encode(file_get_contents($bg_full_path));
            $bg_src = "data:image/png;base64,$bg_data";
        }

        $bg_html = $bg_src ? "<img src='{$bg_src}' style='position: absolute; left: 0px; top: 0px; width: 100%; height: 100%; z-index: -1000;' />" : "";

        $html = "
        <html>
        <head>
            <meta charset='utf-8'>
            <style>
                @page { margin: 0px; }
                body { margin: 0px; padding: 0px; width: 100%; height: 100%; font-family: Arial, sans-serif; }
                .container { position: relative; padding: 40px 100px 20px 100px; text-align: center; }
                .texto-certificado { font-size: 20px; line-height: 1.6; margin-top: 270px; margin-bottom: 20px; }
            </style>
        </head>
        <body>
            {$bg_html}
            <div class='container'>
                <div class='texto-certificado'>
                    {$emissao->descricao_certificado}
                </div>
            </div>          
        </body>
        </html>";

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        
        $file_path = 'app/output/certificado_manual_' . $emissao->id_emissao . '.pdf';
        file_put_contents(getcwd() . '/' . $file_path, $dompdf->output());

        $window = TWindow::create('Certificado Manual', 0.8, 0.9);
        $object = new TElement('object');
        $object->data = "download.php?file=$file_path";
        $object->type = 'application/pdf';
        $object->style = "width: 100%; height: calc(100% - 10px)";
        $window->add($object);
        $window->show();
    }
}