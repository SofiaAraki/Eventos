<?php

class MinhasInscricoesList extends TPage
{
    public function __construct()
    {
        parent::__construct();

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));

        try {
            TTransaction::open('teste');

            $userId = TSession::getValue('userid');
            if (!$userId) {
                throw new Exception('Usuário não autenticado.');
            }

            $criteria = new TCriteria;
            $criteria->add(new TFilter('id_usuario', '=', $userId));

            $criteria->setProperty('order', 'id_inscricao');
            $criteria->setProperty('direction', 'desc');

            $repository = new TRepository('Inscricao');
            $inscricoes = $repository->load($criteria);

            $html = new THtmlRenderer('app/resources/card_minhas_inscricoes.html');
            $items = [];

            if ($inscricoes) {
                foreach ($inscricoes as $inscricao) {
                    $evento = new Evento($inscricao->id_evento);

                    $arte = $evento->get_arte_evento_url();
                    $isFallback = (empty($evento->arte_evento) || !file_exists($evento->arte_evento));

                    // Status da Inscrição
                    $isLiberado = ((int)$inscricao->status_inscricao === 1);
                    $status_texto = $isLiberado ? 'Liberado' : 'Pendente';
                    $status_class = $isLiberado 
                        ? 'bg-success-subtle text-success border-success-subtle' 
                        : 'bg-warning-subtle text-warning-emphasis border-warning-subtle';

                    // Cor dinâmica por Tipo de Participação
                    $tipo = strtolower(trim($inscricao->tipo_participacao ?? 'aluno'));
                    $participacao_class = match ($tipo) {
                        'aluno'                   => 'bg-primary-subtle text-primary border-primary-subtle',
                        'professor'               => 'bg-primary-subtle text-primary border-primary-subtle',
                        'palestrante'             => 'bg-success-subtle text-success border-success-subtle',
                        'organizador'             => 'bg-danger-subtle text-danger border-danger-subtle',
                        'monitor'                 => 'bg-danger-subtle text-danger border-danger-subtle',
                        'autor'                   => 'bg-info-subtle text-info-emphasis border-info-subtle',
                        'banca'                   => 'bg-warning-subtle text-warning-emphasis border-warning-subtle',
                        'orientador'              => 'bg-info-subtle text-info-emphasis border-info-subtle',
                        default                   => 'bg-dark-subtle text-dark border-dark-subtle'
                    };

                    $items[] = [
                        'id_inscricao'       => $inscricao->id_inscricao,
                        'evento_name'        => $evento->titulo_evento ?? $inscricao->evento_name ?? 'Evento',
                        'tipo_participacao'  => ucfirst($inscricao->tipo_participacao ?? 'Aluno'),
                        'participacao_class' => $participacao_class,
                        'status_texto'       => $status_texto,
                        'status_class'       => $status_class,
                        'arte'               => $arte,
                        'img_class'          => $isFallback ? 'img-fallback' : 'img-cover',
                    ];
                }
            }

            $html->enableSection('main');
            $html->enableSection('inscricoes', $items, true);

            $vbox->add($html);

            TTransaction::close();
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }

        parent::add($vbox);
    }

    public function onGerarQrCode($param)
    {
        try {
            QrCodeService::gerarPdfCracha($param['key'] ?? null);
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }

    public function onEmitirCertificado($param)
    {
        try {
            $id_inscricao = (int)($param['key'] ?? $param['id_inscricao'] ?? 0);
            CertificadoService::emitir($id_inscricao);
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
}