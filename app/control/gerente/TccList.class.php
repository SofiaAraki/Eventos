<?php
class TccList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;

    // trait with onReload, onSearch, onDelete...
    use Adianti\Base\AdiantiStandardListTrait;

    public function __construct()
    {
        parent::__construct();

        $this->setDatabase('teste');
        $this->setActiveRecord('Tcc');
        $this->addFilterField('titulo_tcc', 'like', 'titulo_tcc');
        $this->addFilterField('id_orientador', '=', 'id_orientador');
        $this->setDefaultOrder('id_tcc', 'desc');

        $this->form = new BootstrapFormBuilder('form_search_Tcc');
        $this->form->setFormTitle('Gerenciamento de TCC');

        $titulo_tcc = new TEntry('titulo_tcc');
        $orientador = new TDBUniqueSearch('id_orientador', 'teste', 'SystemUser', 'id', 'name');

        $this->form->addFields([new TLabel('Tema:', 'red')], [$titulo_tcc]);
        $this->form->addFields([new TLabel('Orientador:', 'red')], [$orientador]);

        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
        $this->form->addActionLink('Novo', new TAction(['TccForm', 'onClear']), 'fa:plus-circle green');
        $this->form->addActionLink('Limpar', new TAction([$this, 'clear']), 'fa:eraser red');

        $this->form->setData(TSession::getValue(__CLASS__ . '_filter_data'));

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = '100%';

        $id_tcc        = new TDataGridColumn('id_tcc', 'ID', 'left');
        $titulo_tcc    = new TDataGridColumn('titulo_tcc', 'Tema', 'center');
        $data_tcc      = new TDataGridColumn('data_tcc', 'Data', 'center');
        $orientador    = new TDataGridColumn('orientador_name', 'Orientador', 'center');

        $data_tcc->setTransformer(fn($value) => !empty($value) ? (new DateTime($value))->format('d/m/Y H:i') : '-');

        $this->datagrid->addColumn($id_tcc);
        $this->datagrid->addColumn($titulo_tcc);
        $this->datagrid->addColumn($data_tcc);
        $this->datagrid->addColumn($orientador);

        $data_tcc->setAction(new TAction([$this, 'onReload']), ['order' => 'data_tcc']);

        $action1   = new TDataGridAction(['TccForm', 'onEdit'], ['key' => '{id_tcc}']);
        $action2 = new TDataGridAction([$this, 'onDelete'], ['key' => '{id_tcc}']);

        $this->datagrid->addAction($action1, 'Editar', 'far:edit blue');
        $this->datagrid->addAction($action2, 'Excluir', 'far:trash-alt red');

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

    function clear()
    {
        $this->clearFilters();
        $this->onReload();
    }
}