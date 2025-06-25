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
    protected $form;     // registration form
    protected $datagrid; // listing
    protected $pageNavigation;
    
    // trait with onReload, onSearch, onDelete...
    use Adianti\Base\AdiantiStandardListTrait;

    public function __construct()
    {
        parent::__construct();
        
        $this->setDatabase('test');        // defines the database
        $this->setActiveRecord('Tccs');       // defines the active record
        $this->addFilterField('titulo_tcc', 'like', 'titulo_tcc'); // filter field, operator, form field
        $this->setDefaultOrder('id_tcc', 'acs');//acs or desc  // default orderine the default order
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_search_Tccs');
        $this->form->setFormTitle('Gerenciamento de Tccs');
        
        $titulo_tcc = new TEntry('titulo_tcc');
        $this->form->addFields( [new TLabel('Tema:', 'red')], [$titulo_tcc] );
                
        // add form actions
        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
        $this->form->addActionLink('Novo',  new TAction(['TccsFormView', 'onClear']), 'fa:plus-circle green');
        $this->form->addActionLink('Limpar',  new TAction([$this, 'clear']), 'fa:eraser red');
        
        // keep the form filled with the search data
        $this->form->setData( TSession::getValue('TccsView_filter_data') );
        
        // creates the DataGrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = "100%";
        
        // creates the datagrid columns
        $id_tcc    = new TDataGridColumn('id_tcc', 'ID', 'left', '5%');
        $titulo_tcc  = new TDataGridColumn('titulo_tcc', 'Tema', 'center', '50%');
        $data_tcc = new TDataGridColumn('data_tcc', 'Data', 'center', '15%');
        $id_orientador = new TDataGridColumn('orientador', 'Orientador', 'center', '30%');
                
        $this->datagrid->addColumn($id_tcc);
        $this->datagrid->addColumn($titulo_tcc);
        $this->datagrid->addColumn($data_tcc);
        $this->datagrid->addColumn($id_orientador);
        
        $data_tcc->setTransformer(function($value, $object, $row) {
            $date = new DateTime($value);
            return $date->format('d/m/Y H:i');
        });

        $data_tcc->setAction( new TAction([$this, 'onReload']), ['order' => 'data_tcc']);
        
        $action1 = new TDataGridAction(['TccsFormView', 'onEdit'], ['key' => '{id_tcc}'] );
        $action2 = new TDataGridAction([$this, 'onDelete'],   ['key' => '{id_tcc}'] );
        
        $this->datagrid->addAction($action1, 'Edit',   'far:edit blue');
        $this->datagrid->addAction($action2, 'Delete', 'far:trash-alt red');
        
        // create the datagrid model
        $this->datagrid->createModel();
        
        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        
        // creates the page structure using a table
        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->form);
        $vbox->add(TPanelGroup::pack('', $this->datagrid, $this->pageNavigation));
        
        // add the table inside the page
        parent::add($vbox);
    }
    
    /**
     * Clear filters
     */
    function clear()
    {
        $this->clearFilters();
        $this->onReload();
    }

    function onEdit($param)
    {
        try {
            if (isset($param['key'])) {
                TTransaction::open('test');

                $object = new Tccs($param['key']);

                $data = $object->toArray();
                $data['autores'] = array_values($object->getAutores());
                $data['banca']   = array_values($object->getBanca());

                // // debug:
                // echo '<pre>';
                // var_dump($data['autores'], $data['banca']);
                // echo '</pre>';

                $this->form->setData((object)$data);

                TTransaction::close();
            } else {
                $this->form->clear(true);
            }
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }


}
