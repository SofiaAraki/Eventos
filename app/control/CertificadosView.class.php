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
class CertificadosView extends TPage
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
        $this->setActiveRecord('Certificados');       // defines the active record
        $this->addFilterField('titulo_certificado', 'like', 'titulo_certificado'); // filter field, operator, form field
        $this->setDefaultOrder('id_certificado', 'acs');//acs or desc  // default orderine the default order
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_search_Evento');
        $this->form->setFormTitle('Gerenciamento de Certificados');
        
        $titulo_certificado = new TEntry('titulo_certificado');
        $this->form->addFields( [new TLabel('Modelo:', 'red')], [$titulo_certificado] );
                
        // add form actions
        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
        $this->form->addActionLink('Novo',  new TAction(['CertificadosFormView', 'onClear']), 'fa:plus-circle green');
        $this->form->addActionLink('Limpar',  new TAction([$this, 'clear']), 'fa:eraser red');
        
        // keep the form filled with the search data
        $this->form->setData( TSession::getValue('CertificadosView_filter_data') );
        
        // creates the DataGrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = "100%";
        
        // creates the datagrid columns
        $id_certificado    = new TDataGridColumn('id_certificado', 'ID', 'left', '5%');
        $titulo_certificado  = new TDataGridColumn('titulo_certificado', 'Modelo', 'center', '30%');
        $data_emissao_certificado = new TDataGridColumn('data_emissao_certificado', 'Data de Emissão', 'center', '15%');
        $id_evento = new TDataGridColumn('evento', 'Evento', 'center', '40%');
        $carga_horaria_certificado = new TDataGridColumn('carga_horaria_certificado', 'Carga Horária', 'center', '10%');
                
        $this->datagrid->addColumn($id_certificado);
        $this->datagrid->addColumn($titulo_certificado);
        $this->datagrid->addColumn($data_emissao_certificado);
        $this->datagrid->addColumn($id_evento);
        $this->datagrid->addColumn($carga_horaria_certificado);
        
        $data_emissao_certificado->setTransformer(function($value, $object, $row) {
            $date = new DateTime($value);
            return $date->format('d/m/Y H:i');
        });

        $id_certificado->setAction( new TAction([$this, 'onReload']),   ['order' => 'id_certificado']);
        $titulo_certificado->setAction( new TAction([$this, 'onReload']), ['order' => 'titulo_certificado']);
        
        $action1 = new TDataGridAction(['CertificadosFormView', 'onEdit'],   ['key' => '{id_certificado}'] );
        $action2 = new TDataGridAction([$this, 'onDelete'],   ['key' => '{id_certificado}'] );
        
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
                TTransaction::open('test');   // open a transaction with database 
                $object = new Certificados($key);        // instantiates object
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
