<?php
class EventoList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;

    use Adianti\Base\AdiantiStandardListTrait;

    public function __construct()
    {
        parent::__construct();

        $this->setDatabase('teste');
        $this->setActiveRecord('Evento');
        $this->addFilterField('titulo_evento', 'like', 'titulo_evento');
        $this->addFilterField('status_evento', '=', 'status_evento');
        $this->setDefaultOrder('id_evento', 'desc');

        $this->form = new BootstrapFormBuilder('form_search_Evento');
        $this->form->setFormTitle(('Gerenciamento de Evento'));

        $titulo_evento = new TEntry('titulo_evento');
        $this->form->addFields([new TLabel('Evento:', 'red')], [$titulo_evento]);
        $status_evento = new TCombo('status_evento');
        $status_evento->addItems(['1' => 'Aberto', '0' => 'Fechado']);
        $this->form->addFields([new TLabel('Status:', 'red')], [$status_evento]);

        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
        $this->form->addActionLink('Novo', new TAction(['EventoForm', 'onClear']), 'fa:plus-circle green');
        $this->form->addActionLink('Limpar', new TAction([$this, 'clear']), 'fa:eraser red');

        $this->form->setData(TSession::getValue('EventoList_filter_data'));

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = "100%";

        $id_evento       = new TDataGridColumn('id_evento', 'ID', 'left');
        $col_titulo_evento    = new TDataGridColumn('titulo_evento', 'Evento', 'left');
        $col_data_inicio_evento      = new TDataGridColumn('data_inicio_evento', 'Data de Início', 'center');
        $col_data_fim_evento         = new TDataGridColumn('data_fim_evento', 'Data de Fim', 'center');
        $col_status_evento    = new TDataGridColumn('status_evento', 'Status', 'center');
        $col_gerente_evento   = new TDataGridColumn('gerente_evento_name', 'Gerente', 'left');

        $this->datagrid->addColumn($id_evento);
        $this->datagrid->addColumn($col_titulo_evento);
        $this->datagrid->addColumn($col_data_inicio_evento);
        $this->datagrid->addColumn($col_data_fim_evento);
        $this->datagrid->addColumn($col_status_evento);
        $this->datagrid->addColumn($col_gerente_evento);

        $col_data_inicio_evento->setTransformer(function($value) {
        if (empty($value)) return '—';
            try {
                return (new DateTime($value))->format('d/m/Y H:i');
            } catch (Exception $e) {
                return '—';
            }
        });
        $col_data_fim_evento->setTransformer(function($value) {
            if (empty($value)) return '—';
            try {
                return (new DateTime($value))->format('d/m/Y H:i');
            } catch (Exception $e) {
                return '—';
            }
        });
        $col_status_evento->enableHtmlConversion();
        $col_status_evento->setTransformer(fn($value) =>
            (int) $value === 1
                ? '<span class="label label-success">Aberto</span>'
                : '<span class="label label-danger">Fechado</span>'
        );

        $id_evento->setAction(new TAction([$this, 'onReload']), ['order' => 'id_evento']);
        $col_titulo_evento->setAction(new TAction([$this, 'onReload']), ['order' => 'titulo_evento']);
        $col_data_inicio_evento->setAction(new TAction([$this, 'onReload']), ['order' => 'data_inicio_evento']);
        $col_data_fim_evento->setAction(new TAction([$this, 'onReload']), ['order' => 'data_fim_evento']);
        $col_status_evento->setAction(new TAction([$this, 'onReload']), ['order' => 'status_evento']);

        $action1 = new TDataGridAction(['EventoForm', 'onEdit'], ['key' => '{id_evento}']);
        $action2 = new TDataGridAction([$this, 'onDelete'], ['key' => '{id_evento}']);
        $action3 = new TDataGridAction(['EventoList', 'onRelatorioEvento'], ['key' => '{id_evento}']);

        $this->datagrid->addAction($action1, 'Edit', 'far:edit blue');
        $this->datagrid->addAction($action2, 'Delete', 'far:trash-alt red');
        $this->datagrid->addAction($action3, 'Relatório', 'fa:cubes green fa-lg');

        $this->datagrid->createModel();

        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));

        $vbox = new TVBox;
        $vbox->style = 'width:100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->form);
        $vbox->add(TPanelGroup::pack('', $this->datagrid, $this->pageNavigation));

        parent::add($vbox);
    }

    public function clear()
    {
        $this->form->clear(TRUE);
        $this->clearFilters();
        $this->onReload();
    }

    public function show()
    {
        if (!$this->loaded) {
            $this->onReload();
        }
        parent::show();
    }

    public static function onRelatorioEvento($param)
    {
        try {
            if (!isset($param['id_evento'])) {
                throw new Exception('Evento não informado');
            }

            TApplication::loadPage('RelatorioEvento', 'onReload', ['id_evento' => $param['id_evento']]);
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
}
