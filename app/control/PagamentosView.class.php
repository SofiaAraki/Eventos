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
class PagamentosView extends TPage
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
        $this->setActiveRecord('Pagamentos');       // defines the active record
        $this->addFilterField('id_pagamento', '=', 'id_pagamento');
        $this->addFilterField('id_evento', '=', 'id_evento');
        $this->addFilterField('id_usuario', '=', 'id_usuario');
        $this->setDefaultOrder('id_pagamento', 'asc');  //acs or desc  // default orderine the default order
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_search_Pagamentos');
        $this->form->setFormTitle('Gerenciamento de Pagamentos');
        
        $id_evento = new TDBUniqueSearch('id_evento', 'test', 'Eventos', 'id_evento', 'titulo_evento');
        $id_usuario = new TDBUniqueSearch('id_usuario', 'test', 'SystemUser', 'id', 'name');

        $this->form->addFields([new TLabel('Evento:', 'red')], [$id_evento]);
        $this->form->addFields([new TLabel('Usuário:', 'red')], [$id_usuario]);

        // add form actions
        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
        $this->form->addActionLink('Novo',  new TAction(['PagamentosFormView', 'onClear']), 'fa:plus-circle green');
        $this->form->addActionLink('Limpar',  new TAction([$this, 'clear']), 'fa:eraser red');
        
        // keep the form filled with the search data
        $this->form->setData( TSession::getValue('PagamentosView_filter_data') );
        
        // creates the DataGrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = "100%";
        $this->datagrid->id = 'Pagamentos_datagrid';//para ajax
        
        // creates the datagrid columns
        $id_pagamento    = new TDataGridColumn('id_pagamento', 'ID', 'left', '5%');
        $id_usuario  = new TDataGridColumn('usuario', 'Nome', 'left', '30%');
        $titulo_evento = new TDataGridColumn('evento', 'Evento', 'left', '30%');
        $valor_evento = new TDataGridColumn('valor_evento', 'Valor', 'left', '5%');
        $data_pagamento = new TDataGridColumn('data_pagamento', 'Data de Pagamento', 'center', '15%');
        $status_pagamento = new TDataGridColumn('status_pagamento', 'Status', 'center', '10%');

        $this->datagrid->addColumn($id_pagamento);
        $this->datagrid->addColumn($id_usuario);
        $this->datagrid->addColumn($titulo_evento);
        $this->datagrid->addColumn($valor_evento);
        $this->datagrid->addColumn($data_pagamento);
        $this->datagrid->addColumn($status_pagamento);

        $status_pagamento->setTransformer(function ($value, $object, $row) {
            $label = ($value == 1) ? 'Confirmado' : 'Pendente';
            $color = ($value == 1) ? 'success' : 'danger';

            $action = new TAction(['PagamentosView', 'onChangeStatus']);
            $action->setParameter('key', $object->id_pagamento);
            $action->setParameter('static', 'form_search_Pagamentos');

            $link = $action->serialize(TRUE);

            return "<a href=\"javascript:__adianti_ajax_exec('{$link}')\" onclick=\"event.stopPropagation();\">
                        <span class=\"btn btn-sm btn-{$color}\">{$label}</span>
                    </a>";
        });

        $data_pagamento->setTransformer(function($value, $object, $row) {
            $date = new DateTime($value);
            return $date->format('d/m/Y H:i');
        });
        
        $action1 = new TDataGridAction(['PagamentosFormView', 'onEdit'], ['key' => '{id_pagamento}']);
        $action2 = new TDataGridAction([$this, 'onDelete'], ['key' => '{id_pagamento}']);
        
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
            if (isset($param['id_pagamento']))
            {
                $key = $param['id_pagamento'];  // get the parameter
                TTransaction::open('test');   // open a transaction with database 'samples'
                $object = new Pagamentos($key);        // instantiates object City
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

    public static function onChangeStatus($param)
    {
        //new TMessage('info', 'Entrou na ação!');
        try {
            TTransaction::open('test');

            $pagamento = new Pagamentos($param['key']);

            // Altera o status
            $pagamento->status_pagamento = ($pagamento->status_pagamento == 1) ? 0 : 1;
            $pagamento->store();

            TTransaction::close();

            // Recarrega a grid
            TScript::create("__adianti_load_page('index.php?class=PagamentosView&method=onReloadManual');");
            
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onReloadManual($param = NULL)
    {
        try
        {
            TTransaction::open('test');

            // Cria o repositório e critério
            $repository = new TRepository('Pagamentos');
            $limit = 10;

            $criteria = new TCriteria;
            $criteria->setProperties($param); // ordenação, offset, etc
            $criteria->setProperty('limit', $limit);

            // Filtros da sessão
            $filter_data = TSession::getValue('PagamentosView_filter_data');

            if (!empty($filter_data->id_usuario)) {
                $criteria->add(new TFilter('id_usuario', '=', $filter_data->id_usuario));
            }

            if (!empty($filter_data->id_evento)) {
                $criteria->add(new TFilter('id_evento', '=', $filter_data->id_evento));
            }

            // Obtém objetos
            $Pagamentos = $repository->load($criteria, FALSE);

            $this->datagrid->clear();

            if ($Pagamentos)
            {
                foreach ($Pagamentos as $pagamento)
                {
                    // Carrega os relacionamentos se necessário
                    $pagamento->usuario = $pagamento->get_usuario()->name ?? ''; // ou use magic getter
                    $pagamento->evento = $pagamento->get_evento()->titulo_evento ?? '';

                    $this->datagrid->addItem($pagamento);
                }
            }

            // Conta total de registros (para paginação)
            $count = $repository->count($criteria);
            $this->pageNavigation->setCount($count);
            $this->pageNavigation->setProperties($param);
            $this->pageNavigation->setLimit($limit);

            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

}
