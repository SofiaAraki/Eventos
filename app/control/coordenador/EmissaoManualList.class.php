<?php
class EmissaoManualList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;

    use Adianti\Base\AdiantiStandardListTrait;

    public function __construct()
    {
        parent::__construct();

        $this->setDatabase('teste');
        $this->setActiveRecord('EmissaoManual');
        $this->setDefaultOrder('id_emissao', 'desc');

        $criteria = new TCriteria;
        $criteria->add(new TFilter('coordenador_id', '=', TSession::getValue('userid')));
        $this->setCriteria($criteria);

        $this->form = new BootstrapFormBuilder('form_search_EmissaoManual');
        $this->form->setFormTitle('Certificados Emitidos Manualmente');

        $id_evento = new TDBUniqueSearch('id_evento', 'teste', 'Evento', 'id_evento', 'titulo_evento');
        $id_evento->SetSize('80%');
        $nome_pessoa = new TEntry('nome_pessoa');
        $nome_pessoa->SetSize('80%');

        $this->form->addFields([new TLabel('Evento')], [$id_evento]);
        $this->form->addFields([new TLabel('Nome do Favorecido')], [$nome_pessoa]);

        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
        $this->form->addAction('Novo Certificado', new TAction(['EmissaoManualForm', 'onClear']), 'fa:plus green');

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        $col_id          = new TDataGridColumn('id_emissao', 'ID', 'center', '10%');
        $col_evento      = new TDataGridColumn('evento->titulo_evento', 'Evento', 'left', '30%');
        $col_nome        = new TDataGridColumn('nome_pessoa', 'Favorecido', 'left', '30%');
        $col_data        = new TDataGridColumn('data_emissao', 'Data Emissão', 'center', '20%');

        $col_data->setTransformer(function($value) {
            return !empty($value) ? date('d/m/Y H:i', strtotime($value)) : '';
        });

        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_evento);
        $this->datagrid->addColumn($col_nome);
        $this->datagrid->addColumn($col_data);

        $action_edit = new TDataGridAction(['EmissaoManualForm', 'onEdit'], ['key' => '{id_emissao}']);
        $action_edit->setButtonClass('btn btn-default btn-sm');
        $action_edit->setImage('fa:edit blue');
        $this->datagrid->addAction($action_edit);

        $action_print = new TDataGridAction([$this, 'onImprimir'], ['key' => '{id_emissao}']);
        $action_print->setButtonClass('btn btn-default btn-sm');
        $action_print->setImage('fa:print green');
        $this->datagrid->addAction($action_print);

        $action_del = new TDataGridAction([$this, 'onDelete'], ['key' => '{id_emissao}']);
        $action_del->setButtonClass('btn btn-default btn-sm');
        $action_del->setImage('fa:trash red');
        $this->datagrid->addAction($action_del);

        $this->datagrid->createModel();

        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->form);
        $vbox->add($this->datagrid);
        $vbox->add($this->pageNavigation);

        parent::add($vbox);
    }

    public function onImprimir($param)
    {
        try {
            if (isset($param['key'])) {
                TTransaction::open('teste');
                
                $emissao = new EmissaoManual($param['key']);
                
                TTransaction::close();

                CertificadoService::gerarPdfManual($emissao);
            }
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}