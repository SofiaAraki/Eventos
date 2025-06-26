<?php
class EventosAbertosView extends TStandardList
{
    protected $datagrid;

    public function __construct()
    {
        parent::__construct();

        parent::setDatabase('test');         // Banco
        parent::setActiveRecord('Eventos');  // Active Record

        $this->buildDataGrid();
        $this->buildPage();

        $this->onReload();
    }

    /**
     * Configura o datagrid e suas colunas
     */
    private function buildDataGrid()
    {
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);

        $col_titulo = new TDataGridColumn('titulo_evento', 'Evento', 'left', '30%');
        $col_data   = new TDataGridColumn('data_inicio_evento', 'Data de Início', 'left', '30%'); 
        $col_gerente= new TDataGridColumn('gerente_evento_name', 'Gerente do Evento', 'left', '30%');

        $col_data->setTransformer(fn($value) => (new DateTime($value))->format('d/m/Y H:i'));

        $this->datagrid->addColumn($col_titulo);
        $this->datagrid->addColumn($col_data); 
        $this->datagrid->addColumn($col_gerente);

        $action = new TDataGridAction([$this, 'onView'], ['id_evento' => '{id_evento}']);
        $action->setUseButton(true);
        $action->setButtonClass('btn btn-sm btn-default');

        $this->datagrid->addAction($action, 'Inscreva-se', 'far:hand-pointer red');
        $this->datagrid->createModel();
    }

    /**
     * Monta a estrutura visual da página
     */
    private function buildPage()
    {
        $panel = new TPanelGroup('Eventos Abertos');
        $panel->add($this->datagrid)->style = 'overflow-x:auto';
        $panel->addFooter('Inscrições por tempo limitado!');

        $vbox = new TVBox;
        $vbox->style = 'width:100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($panel);

        parent::add($vbox);
    }

    /**
     * Carrega os eventos
     */
    public function onReload($param = null)
    {
        try {
            TTransaction::open('test');

            $repo    = new TRepository('Eventos');
            $criteria= new TCriteria;
            $criteria->add(new TFilter('status_evento', '=', 1));

            $this->datagrid->clear();
            foreach ($repo->load($criteria) as $evento) {
                $this->datagrid->addItem($evento);
            }

            TTransaction::close();
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    /**
     * Realiza a inscrição do usuário no evento
     */
    public function onView($param)
    {
        try {
            TTransaction::open('test');

            $user_id  = (int) TSession::getValue('userid');
            $event_id = (int) $param['id_evento'];

            if ($this->jaInscrito($user_id, $event_id)) {
                new TMessage('info', 'Você já está inscrito neste evento.');
                return;
            }

            $evento = new Eventos($event_id);
            $inscricao = $this->criarInscricao($user_id, $event_id);
            $this->criarPagamento($inscricao, $evento->valor_evento);

            new TMessage('info', 'Inscrição realizada com sucesso!');

            TTransaction::close();
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    /**
     * Verifica se o usuário já está inscrito
     */
    private function jaInscrito(int $user_id, int $event_id): bool
    {
        $repo = new TRepository('Inscricoes');
        $crit = new TCriteria;
        $crit->add(new TFilter('id_usuario', '=', $user_id));
        $crit->add(new TFilter('id_evento', '=', $event_id));
        return (bool) $repo->count($crit);
    }

    /**
     * Cria uma inscrição para o evento
     */
    private function criarInscricao(int $user_id, int $event_id): Inscricoes
    {
        $inscricao = new Inscricoes;
        $inscricao->id_evento        = $event_id;
        $inscricao->id_usuario       = $user_id;
        $inscricao->data_inscricao   = date('Y-m-d H:i:s');
        $inscricao->status_inscricao = 0;
        $inscricao->store();

        return $inscricao;
    }

    /**
     * Registra o pagamento da inscrição
     */
    private function criarPagamento(Inscricoes $inscricao, float $valor_evento)
    {
        $pagamento = new Pagamentos;
        $pagamento->id_inscricao     = $inscricao->id_inscricao;
        $pagamento->data_pagamento   = date('Y-m-d H:i:s');
        $pagamento->status_pagamento = ($valor_evento == 0.00) ? 1 : 0;
        $pagamento->store();
    }
}
