<?php

class ScannerPage extends TPage
{
    private $form;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_scanner');
        $this->form->setFormTitle('Scanner de Credenciamento');

        $qrcode = new TQRCodeInputReader('token_credencial');
        $qrcode->setSize('80%');
        $qrcode->setChangeAction(new TAction([$this, 'onReadQRCode']));

        $this->form->addFields(
            [new TLabel('Aproxime o QR Code da Câmera')],
            [$qrcode]
        );

        $vbox = new TVBox;
        $vbox->style = 'width:100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->form);

        parent::add($vbox);
    }

    public static function onReadQRCode($param)
    {
        try {
            $token = $param['token_credencial'] ?? null;

            if (empty($token)) {
                return;
            }

            $dados = QrCodeService::validateToken($token);
            $idEventoQr = $dados['id_evento'];
            $idUsuarioLogado = TSession::getValue('userid');

            if (!$idUsuarioLogado) {
                throw new Exception('Usuário não autenticado.');
            }

            TTransaction::open('teste');

            $criterio = new TCriteria;
            $criterio->add(new TFilter('id_usuario', '=', $idUsuarioLogado));
            $criterio->add(new TFilter('id_evento', '=', $idEventoQr));

            $vinculo = Monitor::getObjects($criterio);

            if (empty($vinculo)) {
                TTransaction::close();

                throw new Exception(
                    'Você não possui permissão para validar presença neste evento.'
                );
            }

            $result = CheckinService::process($dados['id_inscricao']);

            TTransaction::close();

            if ($result['success']) {

                $isEntrada = ($result['tipo'] == 'ENTRADA');

                $bgStatus = $isEntrada ? '#28a745' : '#ffc107';
                $txStatus = $isEntrada ? '#ffffff' : '#212529';

                $label = $isEntrada
                    ? 'ENTRADA CONFIRMADA'
                    : 'SAÍDA REGISTRADA';

                $msg = "<div style='text-align:center; padding:5px;'>";
                $msg .= "    <div style='margin-bottom: 15px;'>";
                $msg .= "        <b style='font-size: 22px; color: inherit;'>" .
                            htmlspecialchars(
                                mb_strtoupper($result['participante']),
                                ENT_QUOTES,
                                'UTF-8'
                            ) .
                        "</b>";
                $msg .= "    </div>";

                $msg .= "    <div style='background: {$bgStatus};
                                            color: {$txStatus};
                                            padding: 20px;
                                            border-radius: 12px;'>";

                $msg .= "        <span style='font-size: 22px;
                                                font-weight: 900;'>";

                $msg .= $label;
                $msg .= "</span>";
                $msg .= "</div>";

                if ($result['permanencia']) {

                    $msg .= "    <div style='margin-top: 20px;'>";
                    $msg .= "        <span style='font-size: 18px;'>";

                    $msg .= "Permanência:
                            <b>{$result['permanencia']} min</b>";

                    $msg .= "</span>";
                    $msg .= "</div>";
                }

                $msg .= "</div>";

                new TMessage(
                    'info',
                    $msg,
                    null,
                    $isEntrada ? 'Bem-vindo(a)!' : 'Até logo!'
                );
            }

            TForm::sendData(
                'form_scanner',
                (object) ['token_credencial' => '']
            );

        } catch (Exception $e) {

            if (TTransaction::get()) {
                TTransaction::rollback();
            }

            new TMessage('error', $e->getMessage());

            TForm::sendData(
                'form_scanner',
                (object) ['token_credencial' => '']
            );
        }
    }
}