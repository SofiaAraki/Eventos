<?php

class EventoMonitorList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;
    protected $loaded;
    
    use Adianti\Base\AdiantiStandardFormTrait;

    public function __construct()
    {
        parent::__construct();

        $this->setDatabase('teste');
        $this->setActiveRecord('Evento');

        $this->form = new BootstrapFormBuilder("form_search_evento_monitor");
        $this->form->setFormTitle("Meus Eventos (Monitoria)");

        $titulo = new TEntry("titulo_evento");
        $this->form->addFields([new TLabel("Evento")], [$titulo]);

        $this->form->addAction("Buscar", new TAction([$this, "onSearch"]), "fas:search blue");
        
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = "width: 100%";

        $col_id     = new TDataGridColumn("id_evento", "ID", "left");
        $col_titulo = new TDataGridColumn("titulo_evento", "Título do Evento", "left");
        $col_data = new TDataGridColumn("data_inicio_evento", "Data", "center");
        
        $col_data->setTransformer(fn($value) =>
            $value ? TDate::convertToMask($value, 'yyyy-mm-dd', 'dd/mm/yyyy') : '-'
        );

        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_titulo);
        $this->datagrid->addColumn($col_data);

        $action_view = new TDataGridAction(['RelatorioEventoMonitor', 'onReload']);
        $action_view->setLabel("Visualizar Painel");
        $action_view->setImage("fas:chart-line blue");
        $action_view->setField("id_evento");
        
        $this->datagrid->addAction($action_view);
        $this->datagrid->createModel();

        $this->pageNavigation = new TPageNavigation();
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->form);
        $vbox->add(TPanelGroup::pack('', $this->datagrid, $this->pageNavigation));

        parent::add($vbox);
    }

    public function onSearch()
    {
        $data = $this->form->getData();
        TSession::setValue('EventoMonitorList_filter', $data);
        $this->onReload(['offset' => 0, 'first_page' => 1]);
    }

    public function onReload($param = NULL)
    {   
        $data = TSession::getValue('EventoMonitorList_filter') ?? new stdClass;

        try {            
            $limit = 10;
            $data = $this->form->getData();
            $criteria = new TCriteria;
            
            $current_user_id = TSession::getValue('userid');

            TTransaction::open('teste');
            $monitorias = Monitor::where('id_usuario', '=', $current_user_id)->load();
            $ids = array_map(fn($m) => $m->id_evento, $monitorias ?? []);

            if (empty($ids)) {
                $this->datagrid->clear();
                $this->pageNavigation->setCount(0);
                TTransaction::close();
                return;
            }

            $criteria->add(new TFilter('id_evento', 'IN', $ids));

            if (!empty($data->titulo_evento)) {
                $criteria->add(new TFilter('titulo_evento', 'like', "%{$data->titulo_evento}%"));
            }

            $criteria->setProperties($param);
            $criteria->setProperty('limit', $limit);

            $repository = new TRepository($this->activeRecord);
            $objects = $repository->load($criteria);

            $this->datagrid->clear();
            if ($objects) {
                foreach ($objects as $object) {
                    $this->datagrid->addItem($object);
                }
            }

            $criteria->resetProperties();
            $count = $repository->count($criteria);
            
            $this->pageNavigation->setCount($count);
            $this->pageNavigation->setProperties($param);
            $this->pageNavigation->setLimit($limit);
            
            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function show()
    {
        if (!$this->loaded) {
            $this->onReload();
        }
        parent::show();
    }
}