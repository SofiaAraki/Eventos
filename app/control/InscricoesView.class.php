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
class InscricoesView extends TPage
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
        $this->setActiveRecord('Inscricoes');       // defines the active record
        $this->addFilterField('id_usuario', 'like', 'id_usuario'); // filter field, operator, form field
        $this->addFilterField('id_evento', 'like', 'id_evento'); // filter field, operator, form field
        $this->setDefaultOrder('id_inscricao', 'acs');//acs or desc  // default orderine the default order
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_search_Evento');
        $this->form->setFormTitle('Gerenciamento de Inscrições');
        
        //arrumar o filtro por nome de usuario (chave id_usuario vinculada ao systemusers)
        //arrumar o filtro por nome do evento (chave id_evento vinculada ao eventos)
        $id_usuario = new TEntry('id_usuario');
        $this->form->addFields( [new TLabel('Nome:')], [$id_usuario] );
        $id_evento = new TEntry('evento');
        $this->form->addFields( [new TLabel('Evento:')], [$id_evento] );
                
        // add form actions
        $this->form->addAction('Find', new TAction([$this, 'onSearch']), 'fa:search blue');
        $this->form->addActionLink('New',  new TAction(['InscricoesFormView', 'onClear']), 'fa:plus-circle green');
        $this->form->addActionLink('Clear',  new TAction([$this, 'clear']), 'fa:eraser red');
        
        // keep the form filled with the search data
        $this->form->setData( TSession::getValue('InscricaoView_filter_data') );
        
        // creates the DataGrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = "100%";
        
        // creates the datagrid columns
        $id_evento    = new TDataGridColumn('id_inscricao', 'ID', 'left', '5%');
        $id_usuario  = new TDataGridColumn('usuario', 'Nome', 'left', '40%');
        $titulo_evento = new TDataGridColumn('evento', 'Evento', 'left', '30%');
        $data_inscricao = new TDataGridColumn('data_inscricao', 'Data de Inscrição', 'center', '15%');
        $status_inscricao = new TDataGridColumn('status_inscricao', 'Status', 'center', '10%');
        //$gerente_inscricao = new TDataGridColumn('gerente_evento_name', 'Gerente', 'left', '30%');
                
        $this->datagrid->addColumn($id_evento);
        $this->datagrid->addColumn($id_usuario);
        $this->datagrid->addColumn($titulo_evento);
        $this->datagrid->addColumn($data_inscricao);
        $this->datagrid->addColumn($status_inscricao);
        //$this->datagrid->addColumn($gerente_inscricao);

        $status_inscricao->setTransformer(function ($value, $object, $row) {
            switch ($value) {
                case 0: return '<span class="label label-danger">Pendente</span>';
                case 1: return '<span class="label label-success">Confirmada</span>';
                default: return $value;
            }
        });
        
        $id_evento->setAction( new TAction([$this, 'onReload']),   ['order' => 'id_evento']);
        $id_usuario->setAction( new TAction([$this, 'onReload']), ['order' => 'id_usuario']);
        
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
                $object = new Inscricoes($key);        // instantiates object City
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
