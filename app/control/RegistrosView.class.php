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
class RegistrosView extends TPage
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
        $this->setActiveRecord('Registros');       // defines the active record
        $this->setDefaultOrder('id_registro', 'acs');//acs or desc  // default orderine the default order
        
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_search_Registro');
        $this->form->setFormTitle('Registro de Certificados');
        
        $id_evento = new TDBUniqueSearch('id_evento', 'test', 'Eventos', 'id_evento', 'titulo_evento');
        $id_usuario = new TDBUniqueSearch('id_usuario', 'test', 'SystemUser', 'id', 'name');
        
        $this->form->addFields([new TLabel('Evento:', 'red')], [$id_evento]);
        $this->form->addFields([new TLabel('Usuário:', 'red')], [$id_usuario]);
                
        // add form actions
        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
        $this->form->addActionLink('Novo',  new TAction(['RegistrosFormView', 'onClear']), 'fa:plus-circle green');
        $this->form->addActionLink('Limpar',  new TAction([$this, 'clear']), 'fa:eraser red');
        
        // keep the form filled with the search data
        $this->form->setData( TSession::getValue('RegistrosView_filter_data') );
        
        // creates the DataGrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = "100%";
        
        // creates the datagrid columns
        $id_registro    = new TDataGridColumn('id_registro', 'ID', 'left', '5%');
        $titulo_certificado  = new TDataGridColumn('certificado', 'Certificado', 'center', '20%');
        $id_evento = new TDataGridColumn('evento', 'Evento', 'center', '25%');
        $id_usuario = new TDataGridColumn('nome', 'Participante', 'center', '25%');
        $tipo_certificado = new TDataGridColumn('tipo_certificado', 'Tipo', 'center', '10%');
        $data_emissao = new TDataGridColumn('data_emissao', 'Data de Emissão', 'center', '15%');
                
        $this->datagrid->addColumn($id_registro);
        $this->datagrid->addColumn($titulo_certificado);
        $this->datagrid->addColumn($id_evento);
        $this->datagrid->addColumn($id_usuario);
        $this->datagrid->addColumn($tipo_certificado);
        $this->datagrid->addColumn($data_emissao);
        
        $data_emissao->setTransformer(function($value, $object, $row) {
            $date = new DateTime($value);
            return $date->format('d/m/Y H:i');
        });

        $data_emissao->setAction( new TAction([$this, 'onReload']), ['order' => 'data_emissao']);
        
        $action1 = new TDataGridAction(['RegistrosFormView', 'onEdit'],   ['key' => '{id_registro}'] );
        $action2 = new TDataGridAction([$this, 'onDelete'],   ['key' => '{id_registro}'] );
        
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

    public function onReload($param = null)
    {
        try {
            TTransaction::open('test');

            $repository = new TRepository('Registros');
            $criteria   = new TCriteria;

            // carrega os dados do filtro
            $filter_data = TSession::getValue('RegistrosView_filter_data');

            if ($filter_data) {
                $ids_inscricao = [];

                // se evento ou usuário foram filtrados
                if (!empty($filter_data->id_evento) || !empty($filter_data->id_usuario)) {
                    $insc_repo = new TRepository('Inscricoes');
                    $insc_crit = new TCriteria;

                    if (!empty($filter_data->id_evento)) {
                        $insc_crit->add(new TFilter('id_evento', '=', $filter_data->id_evento));
                    }

                    if (!empty($filter_data->id_usuario)) {
                        $insc_crit->add(new TFilter('id_usuario', '=', $filter_data->id_usuario));
                    }

                    $inscricoes = $insc_repo->load($insc_crit);

                    if ($inscricoes) {
                        foreach ($inscricoes as $insc) {
                            $ids_inscricao[] = (int) $insc->id_inscricao;
                        }
                    }

                    // aplica o filtro
                    if ($ids_inscricao) {
                        $criteria->add(new TFilter('id_inscricao', 'IN', $ids_inscricao));
                    } else {
                        // nenhuma inscrição encontrada para o filtro
                        $criteria->add(new TFilter('id_inscricao', '=', 0));
                    }
                }
            }

            $criteria->setProperties($param); 

            $registros = $repository->load($criteria);  
            $this->datagrid->clear();

            if ($registros) {
                foreach ($registros as $registro) {
                    $this->datagrid->addItem($registro);
                }
            }

            $count = $repository->count($criteria);
            $this->pageNavigation->setCount($count);
            $this->pageNavigation->setProperties($param);

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }


}
