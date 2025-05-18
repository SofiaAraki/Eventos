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
class EventosView extends TPage
{
    protected $form;     // registration form
    protected $datagrid; // listing
    protected $pageNavigation;
    
    // trait with onReload, onSearch, onDelete...
    use Adianti\Base\AdiantiStandardListTrait;
    
    /**
     * Class constructor
     * Creates the page, the form and the listing
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->setDatabase('test');        // defines the database
        $this->setActiveRecord('Eventos');       // defines the active record
        $this->addFilterField('titulo_evento', 'like', 'titulo_evento'); // filter field, operator, form field
        $this->setDefaultOrder('id_evento', 'acs');//acs or desc  // default orderine the default order
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_search_Evento');
        $this->form->setFormTitle('Gerenciamento de Eventos');
        
        $titulo_evento = new TEntry('titulo_evento');
        $this->form->addFields( [new TLabel('Evento:')], [$titulo_evento] );
                
        // add form actions
        $this->form->addAction('Find', new TAction([$this, 'onSearch']), 'fa:search blue');
        $this->form->addActionLink('New',  new TAction(['EventosFormView', 'onClear']), 'fa:plus-circle green');
        $this->form->addActionLink('Clear',  new TAction([$this, 'clear']), 'fa:eraser red');
        
        // keep the form filled with the search data
        $this->form->setData( TSession::getValue('EventosView_filter_data') );
        
        // creates the DataGrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = "100%";
        
        // creates the datagrid columns
        $id_evento    = new TDataGridColumn('id_evento', 'ID', 'left', '5%');
        $titulo_evento  = new TDataGridColumn('titulo_evento', 'Evento', 'left', '30%');
        $data_inicio_evento = new TDataGridColumn('data_inicio_evento', 'Data de Inicio', 'center', '15%');
        $data_fim_evento = new TDataGridColumn('data_fim_evento', 'Data de Fim', 'center', '15%');
        $status_evento = new TDataGridColumn('status_evento', 'Status', 'center', '10%');
        $gerente_evento = new TDataGridColumn('gerente_evento_name', 'Gerente', 'left', '30%');
                
        $this->datagrid->addColumn($id_evento);
        $this->datagrid->addColumn($titulo_evento);
        $this->datagrid->addColumn($data_inicio_evento);
        $this->datagrid->addColumn($data_fim_evento);
        $this->datagrid->addColumn($status_evento);
        $this->datagrid->addColumn($gerente_evento);

        $status_evento->setTransformer(function ($value, $object, $row) {
            switch ($value) {
                case 0: return '<span class="label label-danger">Fechado</span>';
                case 1: return '<span class="label label-success">Aberto</span>';
                default: return $value;
            }
        });
        
        $id_evento->setAction( new TAction([$this, 'onReload']),   ['order' => 'id_evento']);
        $titulo_evento->setAction( new TAction([$this, 'onReload']), ['order' => 'titulo_evento']);
        
        $action1 = new TDataGridAction(['EventosFormView', 'onEdit'],   ['key' => '{id_evento}'] );
        $action2 = new TDataGridAction([$this, 'onDelete'],   ['key' => '{id_evento}'] );
        
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
        try
        {
            if (isset($param['id']))
            {
                $key = $param['id'];  // get the parameter
                TTransaction::open('test');   // open a transaction with database 'samples'
                $object = new Eventos($key);        // instantiates object City
                $this->form->setData($object);   // fill the form with the active record data
                TTransaction::close();           // close the transaction
            }
            else
            {
                $this->form->clear( true );
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }
}
