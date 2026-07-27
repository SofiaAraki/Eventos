<?php
class Validador extends TPage
{
    protected $form;

    public function __construct($param = null)
    {
        parent::__construct();

        // 1. Instância do FormBuilder
        $this->form = new BootstrapFormBuilder('form_validador');
        $this->form->setFormTitle('Validação de Certificado');

        // 2. Campo de Entrada
        $cod_validador = new TEntry('cod_validador');
        $cod_validador->setSize('100%');
        $cod_validador->placeholder = 'Insira o código de validação';

        // Preenche caso venha via URL (GET/REQUEST)
        $code = $param['cod_validador'] ?? ($_REQUEST['cod_validador'] ?? null);
        if (!empty($code)) {
            $cod_validador->setValue($code);
        }

        $this->form->addFields(
            [ $cod_validador ]
        );

        // 4. Ação do Botão
        $btn = $this->form->addAction('Validar Certificado', new TAction([$this, 'onValida']), 'fa:check-circle');
        $btn->class = 'btn btn-primary btn-block';
        $btn->style = 'height: 40px; width: 100%; font-size:16px; margin-top: 10px;';

        // 5. Container de Centralização
        $wrapper = new TElement('div');
        $wrapper->style = 'margin: auto; width: 100%; max-width: 450px; padding-top: 100px;';
        $wrapper->id    = 'validador-wrapper';
        $wrapper->add($this->form);

        parent::add($wrapper);
    }

    public static function onPrintCertificado($param)
    {
        try
        {
            TTransaction::open('teste');

            $inscricao = new Inscricao($param['id_inscricao']);
            
            // Busca o registro do certificado na tabela de registros
            $registro = Registro::where('id_inscricao', '=', $inscricao->id_inscricao)->first();

            // Se ainda não existir o registro, cria-o dinamicamente
            if (!$registro)
            {
                $evento = new Evento($inscricao->id_evento);
                $certificadoModelo = Certificado::where('id_evento', '=', $evento->id_evento)
                                                ->where('tipo_participacao', '=', $inscricao->tipo_participacao)
                                                ->first();

                if (!$certificadoModelo) {
                    throw new Exception("Modelo de certificado não encontrado para esta modalidade.");
                }

                $texto = CertificadoService::getTexto($inscricao, $certificadoModelo, $evento);

                $registro = new Registro;
                $registro->id_inscricao = $inscricao->id_inscricao;
                $registro->id_certificado = $certificadoModelo->id_certificado;
                $registro->descricao_certificado = $texto;
                $registro->data_registro = date('Y-m-d H:i:s');
                $registro->store();
            }

            $certificadoModelo = $registro->certificado;
            $conteudo = $registro->descricao_certificado;

            self::gerarPdfCertificado($conteudo, $registro->id_registro, $certificadoModelo, $inscricao->cod_validador ?? '');

            new TMessage('info', 'Certificado AUTÊNTICO e VÁLIDO!');

            TTransaction::close();
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public static function gerarPdfCertificado(string $texto, int $registro_id, Certificado $certificado, string $cod_validador = '')
    {   
        // Se o cod_validador não veio na chamada, recupera via Registro
        if (empty($cod_validador)) {
            $registro = new Registro($registro_id);
            if (!empty($registro->id_inscricao)) {
                $inscricao = new Inscricao($registro->id_inscricao);
                $cod_validador = $inscricao->cod_validador ?? '';
            }
        }

        // Imagem de Fundo
        $bg_campo = $certificado->bg_frente_certificado ?? $certificado->bg_frente ?? '';
        $bg_nome  = !empty(trim($bg_campo)) ? trim($bg_campo) : 'fafram.png';

        $bg_full_path = getcwd() . '/app/images/certificados/' . $bg_nome;

        $bg_src = '';
        if (is_file($bg_full_path)) {
            $bg_data = base64_encode(file_get_contents($bg_full_path));
            $bg_src = "data:image/png;base64,$bg_data";
        }

        // URL de Validação + QR Code
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

        // Estrutura HTML do Certificado
        $bg_html = $bg_src ? "<img src='{$bg_src}' style='position: absolute; left: 0px; top: 0px; width: 100%; height: 100%; z-index: -1000;' />" : "";

        $html = "
        <html>
        <head>
            <meta charset='utf-8'>
            <style>
                @page { 
                    margin: 0px; 
                }
                body {
                    margin: 0px;
                    padding: 0px;
                    width: 100%;
                    height: 100%;
                    font-family: Arial, sans-serif;
                }
                .container {
                    position: relative;
                    padding: 40px 80px 20px 80px;
                    text-align: center;
                }
                .texto-certificado {
                    font-size: 20px;
                    line-height: 1.6;
                    margin-top: 270px; 
                    margin-bottom: 20px;
                }
                .bloco-validacao {
                    font-size: 10px;
                    color: #333;
                    text-align: right;
                    line-height: 1.2;
                    margin-top: 10px; /* Puxa levemente para cima se necessário, ajuste aqui */
                    margin-right: 10px;
                }
                .img-qrcode {
                    width: 55px;
                    height: 55px;
                    margin-bottom: 2px;
                }
            </style>
        </head>
        <body>
            {$bg_html}
            <div class='container'>
                <div class='texto-certificado'>
                    {$texto}
                </div>
                <!-- QR Code e Validação abaixo, no canto direito -->
                <div class='bloco-validacao'>
                    " . ($qrcode_src ? "<img src='{$qrcode_src}' class='img-qrcode'><br>" : "") . "
                    <b>Validação:</b> {$baseUrl}<br>
                    <b>Cód. Validador:</b> {$cod_validador}
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
        file_put_contents($file_path, $dompdf->output());

        $window = TWindow::create('Certificado', 0.8, 0.9);
        $object = new TElement('object');
        $object->data = "download.php?file=$file_path";
        $object->type = 'application/pdf';
        $object->style = "width: 100%; height: calc(100% - 10px)";
        $window->add($object);
        $window->show();
    }

    public function onValida($param)
    {
        try
        {
            $codigo = trim($param['cod_validador'] ?? '');

            if (empty($codigo)) {
                throw new Exception("Por favor, digite o código de validação.");
            }

            TTransaction::open('teste');

            // Busca a inscrição pelo hash de validação
            $inscricao = Inscricao::where('cod_validador', '=', $codigo)->first();

            if (!$inscricao) {
                throw new Exception("Código de validação inválido ou não encontrado.");
            }

            if ($inscricao->status_inscricao != 1) {
                throw new Exception("A inscrição atrelada a este código não está confirmada.");
            }

            TTransaction::close();

            // Executa a impressão/exibição do PDF
            self::onPrintCertificado(['id_inscricao' => $inscricao->id_inscricao]);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onShow($param = null)
    {
        // Se o código veio pela URL (ex: ao escanear o QR Code), valida automaticamente ao carregar a página
        if (!empty($_REQUEST['cod_validador'])) {
            $this->onValida($_REQUEST);
        }
    }
}