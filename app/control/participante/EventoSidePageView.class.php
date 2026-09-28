<?php
class EventoSidePageView extends TPage
{
    private $html;

    public function __construct()
    {
        parent::__construct();
        parent::setTargetContainer('adianti_right_panel');
        $this->html = new THtmlRenderer('app/resources/evento_side.html');
    }

    public function onOpen($param)
    {
        try
        {
            if (isset($param['id']))
            {
                TTransaction::open('teste');

                $id = $param['id'];
                $evento = new Evento($id);

                $arte = $evento->get_arte_evento_url();
                $isFallback = (empty($evento->arte_evento) || !file_exists($evento->arte_evento));

                $data_inicio = !empty($evento->data_inicio_evento) 
                    ? (new DateTime($evento->data_inicio_evento))->format('d/m/Y H:i') 
                    : 'A definir';
                
                $data_fim = !empty($evento->data_fim_evento) 
                    ? (new DateTime($evento->data_fim_evento))->format('d/m/Y H:i') 
                    : 'A definir';

                $valor = ($evento->valor_evento > 0) 
                    ? 'R$ ' . number_format($evento->valor_evento, 2, ',', '.') 
                    : 'Gratuito';

                $replaces = [
                    'id'                   => $evento->id_evento,
                    'titulo'               => $evento->titulo_evento ?? 'Evento sem título',
                    'local'                => $evento->local_evento ?? 'Local não informado',
                    'data_inicio'          => $data_inicio,
                    'data_fim'             => $data_fim,
                    'gerente'              => $evento->get_gerente_evento_name(),
                    'valor'                => $valor,
                    'observacao_aprovacao' => !empty($evento->observacao_aprovacao) ? $evento->observacao_aprovacao : null,
                    'descricao'            => !empty($evento->descricao_evento) ? $evento->descricao_evento : 'Nenhuma descrição informada para este evento.',
                    'arte'                 => $arte,
                    'img_class'            => $isFallback ? 'img-fallback' : 'img-cover'
                ];

                $this->html->enableSection('main', $replaces);

                if ($evento->status_evento == '1') {
                    $this->html->enableSection('status_aberto');
                } else if ($evento->status_evento == '2') {
                    $this->html->enableSection('status_andamento');
                } else if ($evento->status_evento == '3') {
                    $this->html->enableSection('status_encerrado');
                } else {
                    $this->html->enableSection('status_inativo');
                }

                if (!empty($evento->observacao_aprovacao)) {
                    $this->html->enableSection('bloco_observacao', ['observacao' => $evento->observacao_aprovacao]);
                }

                parent::add($this->html);

                TTransaction::close();
            }
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public static function onClose($param)
    {
        TScript::create("Template.closeRightPanel()");
    }
}