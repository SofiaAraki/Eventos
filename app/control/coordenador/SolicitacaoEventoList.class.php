<?php
class SolicitacaoEventoList extends TPage
{
    protected $datagrid;
    protected $pageNavigation;

    use Adianti\Base\AdiantiStandardListTrait;

    public function __construct()
    {
        parent::__construct();

        $this->setDatabase('teste');
        $this->setActiveRecord('Evento');

        // Mostra APENAS solicitações criadas pelo Coordenador Logado
        $loggedUser = TSession::getValue('userid');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('gerente_evento', '=', $loggedUser));
        $this->setCriteria($criteria);

        $this->setDefaultOrder('id_evento', 'desc');

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = '100%';

        $col_id = new TDataGridColumn('id_evento', 'ID', 'center', '5%');
        $col_titulo = new TDataGridColumn('titulo_evento', 'Evento', 'left', '35%');
        $col_inicio = new TDataGridColumn('data_inicio_evento', 'Data Início', 'center', '20%');
        $col_status_aprovacao = new TDataGridColumn('status_aprovacao', 'Status da Análise', 'center', '20%');
        $col_obs = new TDataGridColumn('observacao_aprovacao', 'Observação / Parecer', 'left', '20%');

        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_titulo);
        $this->datagrid->addColumn($col_inicio);
        $this->datagrid->addColumn($col_status_aprovacao);
        $this->datagrid->addColumn($col_obs);

        $col_inicio->setTransformer(fn($v) => $v ? (new DateTime($v))->format('d/m/Y H:i') : '-');

        // Formatação visual do Status de Aprovação
        $col_status_aprovacao->enableHtmlConversion();
        $col_status_aprovacao->setTransformer(function($value) {
            switch ((int) $value) {
                case 1:
                    return '<span class="badge badge-success" style="background-color:#28a745;padding:5px 10px;">Aprovado</span>';
                case 2:
                    return '<span class="badge badge-danger" style="background-color:#dc3545;padding:5px 10px;">Rejeitado</span>';
                default:
                    return '<span class="badge badge-warning" style="background-color:#ffc107;padding:5px 10px;">Aguardando Análise</span>';
            }
        });

        // Ações para o Coordenador
        $action_edit = new TDataGridAction(['EventoFormCoordenador', 'onEdit'], ['key' => '{id_evento}']);
        $this->datagrid->addAction($action_edit, 'Editar Solicitação', 'far:edit blue');

        $this->datagrid->createModel();

        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));

        $panel = new TPanelGroup('Acompanhamento de Solicitações de Eventos');
        $panel->addHeaderActionLink('Nova Solicitação', new TAction(['EventoFormCoordenador', 'onClear']), 'fa:plus-circle green');
        $panel->add($this->datagrid);
        $panel->addFooter($this->pageNavigation);

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($panel);

        parent::add($vbox);
    }
}