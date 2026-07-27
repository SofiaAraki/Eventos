<?php
class EventosAbertosList extends TStandardList
{
    protected $datagrid;

    public function __construct()
    {
        parent::__construct();

        parent::setDatabase('teste');
        parent::setActiveRecord('Evento');

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);

        $col_titulo = new TDataGridColumn('titulo_evento', 'Evento', 'left', '30%');
        $col_data_inicio   = new TDataGridColumn('data_inicio_evento', 'Data de Início', 'left', '20%');
        $col_data_fim   = new TDataGridColumn('data_fim_evento', 'Data de Fim', 'left', '20%');
        $col_desc = new TDataGridColumn('descricao_evento', 'Descrição', 'left', '30%');

        $col_data_inicio->setTransformer(fn($value) => (new DateTime($value))->format('d/m/Y H:i'));
        $col_data_fim->setTransformer(fn($value) => (new DateTime($value))->format('d/m/Y H:i'));

        $this->datagrid->addColumn($col_titulo);
        $this->datagrid->addColumn($col_data_inicio);
        $this->datagrid->addColumn($col_data_fim);
        $this->datagrid->addColumn($col_desc);

        $action = new TDataGridAction([$this, 'onInscricao'], ['id_evento' => '{id_evento}']);
        $action->setUseButton(true);
        $action->setButtonClass('btn btn-sm btn-default');

        $this->datagrid->addAction($action, 'Inscreva-se', 'far:hand-pointer red');
        
        $this->datagrid->createModel();

        $panel = new TPanelGroup('Evento Abertos');
        $panel->add($this->datagrid);
        $panel->addFooter('Inscrições por tempo limitado!');

        $vbox = new TVBox;
        $vbox->style = 'width:100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($panel);

        parent::add($vbox);
    }

    function onReload($param = null)
    {
        try {
            TTransaction::open('teste');

            $repo    = new TRepository('Evento');
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

    public function onInscricao($param)
    {
        try {
            TTransaction::open('teste');

            $user_id  = (int) TSession::getValue('userid');
            $event_id = (int) $param['id_evento'];

            if ($this->jaInscrito($user_id, $event_id)) {
                new TMessage('warning', 'Você já está inscrito neste evento.');
                return;
            }

            $evento = new Evento($event_id);
            $inscricao = $this->criarInscricao($user_id, $event_id);

            new TMessage('info', 'Inscrição realizada com sucesso!');

            TTransaction::close();
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    private function jaInscrito(int $user_id, int $event_id): bool
    {
        $repo = new TRepository('Inscricao');
        $crit = new TCriteria;
        $crit->add(new TFilter('id_usuario', '=', $user_id));
        $crit->add(new TFilter('id_evento', '=', $event_id));
        return (bool) $repo->count($crit);
    }

    private function criarInscricao(int $user_id, int $event_id): Inscricao
    {
        $inscricao = new Inscricao;
        $inscricao->id_evento        = $event_id;
        $inscricao->id_usuario       = $user_id;
        $inscricao->data_inscricao   = date('Y-m-d H:i:s');
        $inscricao->status_inscricao = 0;
        $inscricao->store();

        return $inscricao;
    }

}