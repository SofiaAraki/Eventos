<?php
class MinhasInscricoesList extends TStandardList
{
    protected $datagrid;

    public function __construct()
    {
        parent::__construct();

        parent::setDatabase('teste');
        parent::setActiveRecord('Inscricao');

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = '100%';

        $col_evento        = new TDataGridColumn('evento_name', 'Evento', 'center', '30%');
        $col_participacao  = new TDataGridColumn('tipo_participacao', 'Participação', 'center', '10%');
        $col_data          = new TDataGridColumn('data_inscricao', 'Data de Inscrição', 'center', '20%');
        $col_status        = new TDataGridColumn('status_inscricao', 'Status', 'center', '20%');

        $col_data->setTransformer(fn($v) => (new DateTime($v))->format('d/m/Y H:i'));
        $col_status->setTransformer([$this, 'formatStatus']);

        $this->datagrid->addColumn($col_evento);
        $this->datagrid->addColumn($col_participacao);
        $this->datagrid->addColumn($col_data);
        $this->datagrid->addColumn($col_status);

        // Ações
        $action_certificado = new TDataGridAction([$this, 'onEmitirCertificado'], ['key' => '{id_inscricao}']);
        $action_certificado->setUseButton(true);
        $action_certificado->setButtonClass('btn btn-default');
        $action_certificado->setLabel('Emitir Certificado');
        $action_certificado->setImage('fa:certificate blue');

        $action_qrcode = new TDataGridAction([$this, 'onGerarQrCode'], ['key' => '{id_inscricao}']);
        $action_qrcode->setUseButton(true);
        $action_qrcode->setButtonClass('btn btn-default');
        $action_qrcode->setLabel('QR Code');
        $action_qrcode->setImage('fa:qrcode blue');

        $this->datagrid->addAction($action_certificado);
        $this->datagrid->addAction($action_qrcode);

        $this->datagrid->createModel();

        $panel = new TPanelGroup('Minhas Inscrições');
        $panel->add($this->datagrid)->style = 'overflow-x:auto';
        $panel->addFooter('O Certificado é liberado após a confirmação da presença!');

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($panel);

        parent::add($vbox);
    }

    public function formatStatus($value)
    {
        return match($value) {
            0 => '<span class="label label-danger">Pendente</span>',
            1 => '<span class="label label-success">Liberado</span>',
            default => $value,
        };
    }

    public function onReload($param = null)
    {
        try {
            TTransaction::open('teste');
            $userId = TSession::getValue('userid');
            if (!$userId) throw new Exception('Usuário não autenticado.');

            $criteria = new TCriteria;
            $criteria->add(new TFilter('id_usuario', '=', $userId));

            $repository = new TRepository('Inscricao');
            $inscricoes = $repository->load($criteria);

            $this->datagrid->clear();
            if ($inscricoes) {
                foreach ($inscricoes as $inscricao) {
                    $this->datagrid->addItem($inscricao);
                }
            }
            TTransaction::close();
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function show()
    {
        $this->onReload();
        parent::show();
    }

    protected function onGerarQrCode($param)
    {
        try {
            $key = $param['key'] ?? null;
            if (empty($key)) throw new Exception('ID da inscrição não informado.');

            TTransaction::open('teste');
            $inscricao = new Inscricao($key);
            
            if ($inscricao->id_usuario != TSession::getValue('userid')) {
                throw new Exception('Acesso negado.');
            }

            $conteudo_qr = QrCodeService::generateToken($inscricao);

            $properties = [
                'leftMargin' => 12, 'topMargin' => 12, 'labelWidth' => 100,
                'labelHeight' => 60, 'spaceBetween' => 4, 'rowsPerPage' => 4,
                'colsPerPage' => 2, 'fontSize' => 12, 'barcodeHeight' => 80,
                'imageMargin' => 0
            ];

            $template = "<b>Inscrição:</b> {$inscricao->id_inscricao}\n";
            $template .= "<b>Nome:</b> {$inscricao->usuario_name}\n";
            $template .= "<b>Evento:</b> {$inscricao->evento_name}\n";
            $template .= "#qrcode#\n";

            $generator = new AdiantiBarcodeDocumentGenerator;
            $generator->setProperties($properties);
            $generator->setLabelTemplate($template);
            $generator->addObject($inscricao);
            $generator->setBarcodeContent($conteudo_qr);
            
            $generator->generate();
            $file = 'app/output/qrcode_insc_' . $key . '.pdf';
            $generator->save($file);
            
            $window = TWindow::create('QR Code de Identificação', 0.8, 0.9);
            $embed = new TElement('object');
            $embed->data = $file;
            $embed->type = 'application/pdf';
            $embed->style = "width: 100%; height:calc(100% - 10px)";
            $window->add($embed);
            $window->show();

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    protected function onEmitirCertificado($param)
    {
        try
        {
            TTransaction::open('teste');

            $id_inscricao = $param['key'] ?? $param['id_inscricao'];
            $inscricao = new Inscricao($id_inscricao);

            if ($inscricao->id_usuario != TSession::getValue('userid')) {
                throw new Exception('Acesso negado.');
            }

            if ($inscricao->status_inscricao != 1) {
                throw new Exception('O certificado estará disponível apenas após a confirmação da sua presença.');
            }

            $registro = Registro::where('id_inscricao', '=', $inscricao->id_inscricao)->first();

            if (!$registro) {
                $evento = new Evento($inscricao->id_evento);
                $certificadoModelo = Certificado::where('id_evento', '=', $evento->id_evento)
                                                ->where('tipo_participacao', '=', $inscricao->tipo_participacao)
                                                ->first();

                if (!$certificadoModelo) {
                    throw new Exception('Não há modelo de certificado para este tipo de participação.');
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
            $conteudo = $registro->descricao_certificado; // Corrigido

            self::gerarPdfCertificado(
                $conteudo,
                $registro->id_registro,
                $certificadoModelo,
                $inscricao->cod_validador ?? ''
            );

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

        // 1. Imagem de Fundo
        $bg_campo = $certificado->bg_frente_certificado ?? $certificado->bg_frente ?? '';
        $bg_nome  = !empty(trim($bg_campo)) ? trim($bg_campo) : 'fafram.png';

        $bg_full_path = getcwd() . '/app/images/certificados/' . $bg_nome;

        $bg_src = '';
        if (is_file($bg_full_path)) {
            $bg_data = base64_encode(file_get_contents($bg_full_path));
            $bg_src = "data:image/png;base64,$bg_data";
        }

        // 3. URL de Validação + QR Code
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

        // 4. Estrutura HTML do Certificado
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
        file_put_contents($file_path, $dompdf->output());

        $window = TWindow::create('Certificado', 0.8, 0.9);
        $object = new TElement('object');
        $object->data = "download.php?file=$file_path";
        $object->type = 'application/pdf';
        $object->style = "width: 100%; height: calc(100% - 10px)";
        $window->add($object);
        $window->show();
    }
}