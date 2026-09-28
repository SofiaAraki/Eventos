<?php

class Validador extends TPage
{
    protected $form;

    public function __construct($param = null)
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_validador');
        $this->form->setFormTitle('Validação de Certificado');

        $cod_validador = new TEntry('cod_validador');
        $cod_validador->setSize('100%');
        $cod_validador->placeholder = 'Insira o código de validação';

        $code = $param['cod_validador'] ?? ($_REQUEST['cod_validador'] ?? null);
        if (!empty($code)) {
            $cod_validador->setValue($code);
        }

        $this->form->addFields([$cod_validador]);

        $btn = $this->form->addAction('Validar Certificado', new TAction([$this, 'onValida']), 'fa:check-circle');
        $btn->class = 'btn btn-primary btn-block';
        $btn->style = 'height: 40px; width: 100%; font-size:16px; margin-top: 10px;';

        $wrapper = new TElement('div');
        $wrapper->style = 'margin: auto; width: 100%; max-width: 450px; padding-top: 100px;';
        $wrapper->id    = 'validador-wrapper';
        $wrapper->add($this->form);

        parent::add($wrapper);
    }

    public function onValida($param)
    {
        try {
            $codigo = trim($param['cod_validador'] ?? '');

            if (empty($codigo)) {
                throw new Exception("Por favor, digite o código de validação.");
            }

            TTransaction::open('teste');

            $inscricao = Inscricao::where('cod_validador', '=', $codigo)->first();

            if (!$inscricao) {
                throw new Exception("Código de validação inválido ou não encontrado.");
            }

            if ((int)$inscricao->status_inscricao !== 1) {
                throw new Exception("A inscrição atrelada a este código não está confirmada.");
            }

            $id_inscricao = $inscricao->id_inscricao;

            TTransaction::close();

            $actionPrint = new TAction([$this, 'onPrintCertificado'], ['id_inscricao' => $id_inscricao]);

            new TMessage('info', 'Certificado AUTÊNTICO e VÁLIDO!', $actionPrint);

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onPrintCertificado($param)
    {
        try {
            TTransaction::open('teste');

            $id_inscricao = $param['id_inscricao'] ?? null;
            if (!$id_inscricao) {
                throw new Exception("Inscrição não informada.");
            }

            $inscricao = new Inscricao($id_inscricao);
            
            $registro = Registro::where('id_inscricao', '=', $inscricao->id_inscricao)->first();

            if (!$registro) {
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
            $cod_validador = $inscricao->cod_validador ?? '';
            $id_registro = $registro->id_registro;

            TTransaction::close();

            CertificadoService::gerarPdfCertificado(
                $conteudo,
                $id_registro,
                $certificadoModelo,
                $cod_validador
            );

            $file_path = 'app/output/certificado_' . $id_registro . '.pdf';

            $window = TWindow::create('Visualização do Certificado Autêntico', 0.8, 0.9);
            $object = new TElement('object');
            $object->data  = $file_path;
            $object->type  = 'application/pdf';
            $object->style = "width: 100%; height: calc(100% - 10px)";
            
            $window->add($object);
            $window->show();

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onShow($param = null)
    {
        if (!empty($_REQUEST['cod_validador'])) {
            $this->onValida($_REQUEST);
        }
    }
}