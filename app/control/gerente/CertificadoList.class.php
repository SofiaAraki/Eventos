    <?php
    class CertificadoList extends TPage
    {
        protected $form;
        protected $datagrid;
        protected $pageNavigation;

        use Adianti\Base\AdiantiStandardListTrait;

        public function __construct()
        {
            parent::__construct();

            $this->setDatabase('teste');
            $this->setActiveRecord('Certificado');
            $this->addFilterField('titulo_certificado', 'like', 'titulo_certificado');
            $this->setDefaultOrder('id_certificado', 'desc');

            $this->form = new BootstrapFormBuilder('form_search_Certificado');
            $this->form->setFormTitle('Gerenciamento de Certificado');

            $titulo_certificado = new TEntry('titulo_certificado');
            $titulo_certificado->SetSize('80%');

            $this->form->addFields(
                [new TLabel('Modelo', 'red')],
                [$titulo_certificado]
            );

            $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
            $this->form->addActionLink('Novo', new TAction(['CertificadoForm', 'onClear']), 'fa:plus-circle green');
            $this->form->addActionLink('Limpar', new TAction([$this, 'clear']), 'fa:eraser red');

            $this->form->setData(TSession::getValue('CertificadoList_filter_data'));

            $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
            $this->datagrid->width = "100%";

            $col_id_certificado = new TDataGridColumn('id_certificado', 'ID', 'left', '5%');
            $col_titulo_certificado = new TDataGridColumn('titulo_certificado', 'Modelo', 'center', '30%');
            $col_data_emissao_certificado = new TDataGridColumn('data_emissao_certificado', 'Data de Emissão', 'center', '15%');
            $col_evento = new TDataGridColumn('evento_name', 'Evento', 'center', '25%');
            $col_carga_horaria_certificado = new TDataGridColumn('carga_horaria_certificado', 'Carga Horária', 'center', '10%');
            $col_tipo_participacao = new TDataGridColumn('tipo_participacao', 'Tipo', 'center', '10%');

            $col_data_emissao_certificado->setTransformer(function ($value) {
                if (empty($value)) return '-';
                try {
                    return (new DateTime($value))->format('d/m/Y H:i');
                } catch (Exception $e) {
                    return '-';
                }
            });
            
            $col_tipo_participacao->setTransformer(function($value) {
                return ucfirst($value);
            });

            $this->datagrid->addColumn($col_id_certificado);
            $this->datagrid->addColumn($col_titulo_certificado);
            $this->datagrid->addColumn($col_data_emissao_certificado);
            $this->datagrid->addColumn($col_evento);
            $this->datagrid->addColumn($col_carga_horaria_certificado);
            $this->datagrid->addColumn($col_tipo_participacao);

            $col_id_certificado->setAction(new TAction([$this, 'onReload']), ['order' => 'id_certificado']);
            $col_data_emissao_certificado->setAction(new TAction([$this, 'onReload']), ['order' => 'data_emissao_certificado']);
            $col_carga_horaria_certificado->setAction(new TAction([$this, 'onReload']), ['order' => 'carga_horaria_certificado']);

            $action1 = new TDataGridAction(['CertificadoForm', 'onEdit'], ['key' => '{id_certificado}']);
            $action2 = new TDataGridAction([$this, 'onDelete'], ['key' => '{id_certificado}']);

            $this->datagrid->addAction($action1, 'Edit', 'far:edit blue');
            $this->datagrid->addAction($action2, 'Delete', 'far:trash-alt red');

            $this->datagrid->createModel();

            $this->pageNavigation = new TPageNavigation;
            $this->pageNavigation->setAction(new TAction([$this, 'onReload']));

            $vbox = new TVBox;
            $vbox->style = 'width: 100%';
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
    }