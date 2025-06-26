<?php
class EventosAbertosView extends TStandardList
{
    protected $datagrid;

    public function __construct()
    {
        parent::__construct();

        parent::setDatabase('test');
        parent::setActiveRecord('Eventos');

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);

        $titulo_evento   = new TDataGridColumn('titulo_evento', 'Evento', 'left', '30%');
        $data_inicio     = new TDataGridColumn('data_inicio_evento', 'Data de Início', 'left', '30%'); 
        $gerente_evento  = new TDataGridColumn('gerente_evento_name', 'Gerente do Evento', 'left', '30%');

        $data_inicio->setTransformer(function($value) {
            $date = new DateTime($value);
            return $date->format('d/m/Y H:i');
        });

        $this->datagrid->addColumn($titulo_evento);
        $this->datagrid->addColumn($data_inicio); 
        $this->datagrid->addColumn($gerente_evento);

        $action1 = new TDataGridAction([$this, 'onView'], ['id_evento' => '{id_evento}']);
        $action1->setUseButton(true);
        $action1->setButtonClass('btn btn-sm btn-default');

        $this->datagrid->addAction($action1, 'Inscreva-se', 'far:hand-pointer red');

        $this->datagrid->createModel();

        $panel = new TPanelGroup('Eventos Abertos');
        $panel->add($this->datagrid)->style = 'overflow-x:auto';
        $panel->addFooter('Inscrições por tempo limitado!');

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($panel);

        parent::add($vbox);

        $this->onReload();
    }

    public function onReload($param = null)
    {
        try {
            TTransaction::open('test');

            $repository = new TRepository('Eventos');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('status_evento', '=', 1));

            $eventos = $repository->load($criteria);

            $this->datagrid->clear();

            if ($eventos) {
                foreach ($eventos as $evento) {
                    $this->datagrid->addItem($evento);
                }
            }

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onView($param)
    {
        try {
            TTransaction::open('test');

            $user_id = intval(TSession::getValue('userid'));
            $event_id = intval($param['id_evento']);

            // Verifica se já existe inscrição
            $repository = new TRepository('Inscricoes');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('id_usuario', '=', $user_id));
            $criteria->add(new TFilter('id_evento', '=', $event_id));

            $existing = $repository->load($criteria);

            if ($existing) {
                new TMessage('info', 'Você já está inscrito neste evento.');
            } else {
                // Busca o evento para pegar o valor
                $evento = new Eventos($event_id);

                $inscricao = new Inscricoes;
                $inscricao->id_evento       = $event_id;
                $inscricao->id_usuario      = $user_id;
                $inscricao->data_inscricao  = date('Y-m-d H:i:s');
                $inscricao->status_inscricao = 0;
                $inscricao->store();

                // cria o pagamento
                $pagamento = new Pagamentos;
                $pagamento->id_inscricao     = $inscricao->id_inscricao;
                $pagamento->data_pagamento   = date('Y-m-d H:i:s');

                // Verifica o valor do evento
                if (floatval($evento->valor_evento) == 0.00) {
                    $pagamento->status_pagamento = 1; // Confirmado automaticamente
                } else {
                    $pagamento->status_pagamento = 0; // Pendente
                }
                
                $pagamento->store();

                new TMessage('info', 'Inscrição realizada com sucesso!');
            }

            TTransaction::close();

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}
