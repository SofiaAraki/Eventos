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

    public function __construct()
    {
        parent::__construct();
        
        $this->setDatabase('test');        // defines the database
        $this->setActiveRecord('Inscricoes');       // defines the active record
        $this->addFilterField('id_usuario', '=', 'id_usuario');
        $this->addFilterField('id_evento', '=', 'id_evento');
        $this->setDefaultOrder('id_inscricao', 'asc');  //acs or desc  // default orderine the default order
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_search_Inscricoes');
        $this->form->setFormTitle('Gerenciamento de Inscrições');

        $id_evento = new TDBUniqueSearch('id_evento', 'test', 'Eventos', 'id_evento', 'titulo_evento');
        $id_usuario = new TDBUniqueSearch('id_usuario', 'test', 'SystemUser', 'id', 'name');
        
        $this->form->addFields([new TLabel('Evento:', 'red')], [$id_evento]);
        $this->form->addFields([new TLabel('Usuário:', 'red')], [$id_usuario]);
                
        // add form actions
        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
        $this->form->addActionLink('Novo',  new TAction(['InscricoesFormView', 'onClear']), 'fa:plus-circle green');
        $this->form->addActionLink('Limpar',  new TAction([$this, 'clear']), 'fa:eraser red');
        
        // keep the form filled with the search data
        $this->form->setData( TSession::getValue('InscricoesView_filter_data') );
        
        // creates the DataGrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = "100%";
        $this->datagrid->id = 'inscricoes_datagrid';//para ajax
        
        // creates the datagrid columns
        $id_evento    = new TDataGridColumn('id_inscricao', 'ID', 'left', '5%');
        $id_usuario  = new TDataGridColumn('usuario', 'Nome', 'left', '40%');
        $titulo_evento = new TDataGridColumn('evento', 'Evento', 'left', '30%');
        $data_inscricao = new TDataGridColumn('data_inscricao', 'Data de Inscrição', 'center', '15%');
        $status_inscricao = new TDataGridColumn('status_inscricao', 'Status', 'center', '10%');
                
        $this->datagrid->addColumn($id_evento);
        $this->datagrid->addColumn($id_usuario);
        $this->datagrid->addColumn($titulo_evento);
        $this->datagrid->addColumn($data_inscricao);
        $this->datagrid->addColumn($status_inscricao);

        $status_inscricao->setTransformer(function ($value, $object, $row) {
            $label = ($value == 1) ? 'Confirmada' : 'Pendente';
            $color = ($value == 1) ? 'success' : 'danger';

            $action = new TAction(['InscricoesView', 'onChangeStatus']);
            $action->setParameter('key', $object->id_inscricao);
            $action->setParameter('static', 'form_search_Inscricoes');

            $link = $action->serialize(TRUE);

            return "<a href=\"javascript:__adianti_ajax_exec('{$link}')\" onclick=\"event.stopPropagation();\">
                        <span class=\"btn btn-sm btn-{$color}\">{$label}</span>
                    </a>";
        });
        
        $data_inscricao->setTransformer(function($value, $object, $row) {
            $date = new DateTime($value);
            return $date->format('d/m/Y H:i');
        });
        
        $id_evento->setAction( new TAction([$this, 'onReload']),   ['order' => 'id_evento']);
        $id_usuario->setAction( new TAction([$this, 'onReload']), ['order' => 'id_usuario']);
        
        $action1 = new TDataGridAction(['InscricoesFormView', 'onEdit'], ['key' => '{id_inscricao}']);
        $action2 = new TDataGridAction([$this, 'onDelete'], ['key' => '{id_inscricao}']);
        
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

    public static function onChangeStatus($param)
    {
        try {
            TTransaction::open('test');
            
            $pagamento = Pagamentos::where('id_inscricao', '=', $param['key'])->first();

            if ($pagamento->status_pagamento == 0) {
                new TMessage('warning', 'Só é possível confirmar inscrições com pagamento aprovado.');
            }else {

                $inscricao = new Inscricoes($pagamento->id_inscricao);

                // Altera o status
                $inscricao->status_inscricao = ($inscricao->status_inscricao == 1) ? 0 : 1;
                $inscricao->store();

                TTransaction::close();

                // Recarrega a grid
                TScript::create("__adianti_load_page('index.php?class=InscricoesView&method=onReloadManual');");
            }

            
            
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
            $repository = new TRepository('Inscricoes');
            $limit = 10;

            $criteria = new TCriteria;
            $criteria->setProperties($param); // ordenação, offset, etc
            $criteria->setProperty('limit', $limit);

            // Filtros da sessão
            $filter_data = TSession::getValue('InscricoesView_filter_data');

            if (!empty($filter_data->id_usuario)) {
                $criteria->add(new TFilter('id_usuario', '=', $filter_data->id_usuario));
            }

            if (!empty($filter_data->id_evento)) {
                $criteria->add(new TFilter('id_evento', '=', $filter_data->id_evento));
            }

            // Obtém objetos
            $inscricoes = $repository->load($criteria, FALSE);

            $this->datagrid->clear();

            if ($inscricoes)
            {
                foreach ($inscricoes as $inscricao)
                {
                    // Carrega os relacionamentos se necessário
                    $inscricao->usuario = $inscricao->getUsuario()->name ?? ''; // ou use magic getter
                    $inscricao->evento = $inscricao->getEvento()->titulo_evento ?? '';

                    $this->datagrid->addItem($inscricao);
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
