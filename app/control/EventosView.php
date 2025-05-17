<?php
/**
 * EventosList
 *
 * @version    1.0
 * @package    sample
 * @subpackage tutor
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class EventosView extends TPage
{
    protected $form;     // registration form
    protected $datagrid; // listing
    protected $pageNavigation;
    
    use Adianti\Base\AdiantiStandardListTrait;
    
    /**
     * Page constructor
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->setDatabase('test');          // defines the database
        $this->setActiveRecord('Eventos');         // defines the active record
        $this->setDefaultOrder('id', 'asc');    // defines the default order
        $this->addFilterField('id', '=', 'id'); // filterField, operator, formField
        $this->addFilterField('customer_id', '=', 'customer_id'); // filterField, operator, formField
        
        // $this->addFilterField('date', '>=', 'date_from', function($value) {
        //     return TDate::convertToMask($value, 'dd/mm/yyyy', 'yyyy-mm-dd');
        // }); // filterField, operator, formField, transformFunction
        
        // $this->addFilterField('date', '<=', 'date_to', function($value) {
        //     return TDate::convertToMask($value, 'dd/mm/yyyy', 'yyyy-mm-dd');
        // }); // filterField, operator, formField, transformFunction
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_search_Eventos');
        $this->form->setFormTitle(_t('Eventos list'));
        
        // create the form fields
        $id        = new TEntry('id');
        // $date_from = new TDate('date_from');
        // $date_to   = new TDate('date_to');
        
        // $customer_id = new TDBUniqueSearch('customer_id', 'test', 'Customer', 'id', 'name');
        // $customer_id->setMinLength(1);
        // $customer_id->setMask('{name} ({id})');
        
        // add the fields
        $this->form->addFields( [new TLabel('Id')],          [$id]); 
        // $this->form->addFields( [new TLabel('Date (from)')], [$date_from],
        //                         [new TLabel('Date (to)')],   [$date_to] );
        // $this->form->addFields( [new TLabel('Customer')],    [$customer_id] );
        
        $id->setSize('50%');
        // $date_from->setSize('100%');
        // $date_to->setSize('100%');
        // $date_from->setMask( 'dd/mm/yyyy' );
        // $date_to->setMask( 'dd/mm/yyyy' );
        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('EventosView_filter_data') );
        
        // add the search form actions
        $this->form->addAction('Find', new TAction([$this, 'onSearch']), 'fa:search');
        $this->form->addActionLink('New',  new TAction(['EventosForm', 'onEdit'], ['register_state' => 'false']), 'fa:plus green');
        
        // creates a DataGrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = '100%';
        //$this->datagrid->disableDefaultClick();
        
        // creates the datagrid columns
        $column_id       = new TDataGridColumn('id', 'Id', 'center', '10%');
        //$column_date     = new TDataGridColumn('date', 'Date', 'center', '10%');
        $column_customer = new TDataGridColumn('customer->name', 'Customer', 'left', '40%');
        $column_status   = new TDataGridColumn('status', 'Status', 'center', '20%');
        $column_total    = new TDataGridColumn('total', 'Total', 'right', '20%');
        
        // define format function
        $format_value = function($value) {
            if (is_numeric($value)) {
                return 'R$ '.number_format($value, 2, ',', '.');
            }
            return $value;
        };
        
        $column_total->setTransformer( $format_value );
        
        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_date);
        $this->datagrid->addColumn($column_customer);
        $this->datagrid->addColumn($column_status);
        $this->datagrid->addColumn($column_total);
        
        $column_status->setTransformer( function($value, $object, $row, $cell) {
            $cell->href='#';
            $dropdown = new TDropDown($object->status->name, '');
            $dropdown->getButton()->style .= ';color:white;border-radius:5px;background:'.$object->status->color;
            
            TTransaction::open('test');
            $statuses = EventosStatus::orderBy('id')->load();
            foreach ($statuses as $status)
            {
                $params = ['id' => $object->id,
                           'status_id' => $status->id, 
                           'offset' => $_REQUEST['offset'] ?? 0,
                           'limit' => $_REQUEST['limit'] ?? 10,
                           'page' => $_REQUEST['page'] ?? 1,
                           'first_page' => $_REQUEST['first_page'] ?? 1,
                           'register_state' => 'false'];
                
                $dropdown->addAction( $status->name, new TAction([$this, 'changeStatus'], $params ), 'fas:circle  ' . $status->color );
            }
            TTransaction::close();
            
            return $dropdown;
        });
        
        // creates the datagrid column actions
        $column_id->setAction(new TAction([$this, 'onReload']),   ['order' => 'id']);
        $column_date->setAction(new TAction([$this, 'onReload']), ['order' => 'date']);
        
        // define the transformer method over date
        $column_date->setTransformer( function($value, $object, $row) {
            $date = new DateTime($value);
            return $date->format('d/m/Y');
        });

        $action_view   = new TDataGridAction(['EventosSidePanelView', 'onView'],   ['key' => '{id}', 'register_state' => 'false'] );
        $action_edit   = new TDataGridAction(['EventosForm', 'onEdit'],   ['key' => '{id}', 'register_state' => 'false'] );
        $action_delete = new TDataGridAction([$this, 'onDelete'],   ['key' => '{id}'] );
        
        $this->datagrid->addAction($action_view, _t('View details'), 'fa:search green fa-fw');
        $this->datagrid->addAction($action_edit, 'Edit',   'far:edit blue fa-fw');
        $this->datagrid->addAction($action_delete, 'Delete', 'far:trash-alt red fa-fw');
        
        // create the datagrid model
        $this->datagrid->createModel();
        
        // create the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add($panel = TPanelGroup::pack('', $this->datagrid, $this->pageNavigation));
        $panel->getBody()->style = 'overflow-x:auto';
        parent::add($container);
    }
    
    /**
     *
     */
    public function changeStatus($param)
    {
        try
        {
            TTransaction::open('test');
            $Eventos = Eventos::find($param['id']);
            $Eventos->status_id = $param['status_id'];
            $Eventos->store();
            TTransaction::close();
            
            $this->onReload($param);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }
}
