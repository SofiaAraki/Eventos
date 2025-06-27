<?php
/**
 * StandardDataGridView Listing
 *
 * @version    1.0
 * @package    samples
 * @subpackage tutor
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    https://adiantiframework.com.br/license-tutor
 */
class TccsView extends TPage
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;

    use Adianti\Base\AdiantiStandardListTrait;

    public function __construct()
    {
        parent::__construct();

        $this->setDatabase('test');
        $this->setActiveRecord('Tccs');
        $this->addFilterField('titulo_tcc', 'like', 'titulo_tcc');
        $this->setDefaultOrder('id_tcc', 'desc');

        $this->buildForm();
        $this->buildDatagrid();
        $this->buildPage();
    }

    private function buildForm()
    {
        $this->form = new BootstrapFormBuilder('form_search_Tccs');
        $this->form->setFormTitle('Gerenciamento de TCCs');

        $titulo_tcc = new TEntry('titulo_tcc');
        $this->form->addFields([new TLabel('Tema:', 'red')], [$titulo_tcc]);
        $orientador = new TEntry('orientador');
        $this->form->addFields([new TLabel('Orientador:', 'red')], [$orientador]);

        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
        $this->form->addActionLink('Novo', new TAction(['TccsFormView', 'onClear']), 'fa:plus-circle green');
        $this->form->addActionLink('Limpar', new TAction([$this, 'clear']), 'fa:eraser red');

        $this->form->setData(TSession::getValue(__CLASS__ . '_filter_data'));
    }

    private function buildDatagrid()
    {
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = '100%';

        $id_tcc        = new TDataGridColumn('id_tcc', 'ID', 'left', '5%');
        $titulo_tcc    = new TDataGridColumn('titulo_tcc', 'Tema', 'center', '50%');
        $data_tcc      = new TDataGridColumn('data_tcc', 'Data', 'center', '15%');
        $orientador    = new TDataGridColumn('orientador', 'Orientador', 'center', '30%');

        $data_tcc->setTransformer(fn($value) => (new DateTime($value))->format('d/m/Y H:i'));
        $data_tcc->setAction(new TAction([$this, 'onReload']), ['order' => 'data_tcc']);

        $this->datagrid->addColumn($id_tcc);
        $this->datagrid->addColumn($titulo_tcc);
        $this->datagrid->addColumn($data_tcc);
        $this->datagrid->addColumn($orientador);

        $editAction   = new TDataGridAction(['TccsFormView', 'onEdit'], ['key' => '{id_tcc}']);
        $deleteAction = new TDataGridAction([$this, 'onDelete'], ['key' => '{id_tcc}']);

        $this->datagrid->addAction($editAction, 'Editar', 'far:edit blue');
        $this->datagrid->addAction($deleteAction, 'Excluir', 'far:trash-alt red');

        $this->datagrid->createModel();
    }

    private function buildPage()
    {
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
        $this->clearFilters();
        $this->onReload();
    }
}
