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
    protected $form;
    protected $datagrid;
    protected $pageNavigation;

    use Adianti\Base\AdiantiStandardListTrait;

    public function __construct()
    {
        parent::__construct();

        $this->setDatabase('test');
        $this->setActiveRecord('Eventos');

        $this->addFilterField('titulo_evento', 'like', 'titulo_evento');
        $this->addFilterField('status_evento', '=', 'status_evento');
        $this->setDefaultOrder('id_evento', 'desc');

        $this->createSearchForm();
        $this->createDataGrid();
        $this->createPageNavigation();

        $vbox = new TVBox;
        $vbox->style = 'width:100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->form);
        $vbox->add(TPanelGroup::pack('', $this->datagrid, $this->pageNavigation));
        parent::add($vbox);
    }

    /**
     * Formulário de busca
     */
    private function createSearchForm()
    {
        $this->form = new BootstrapFormBuilder('form_search_Evento');
        $this->form->setFormTitle('Gerenciamento de Eventos');

        $titulo_evento = new TEntry('titulo_evento');
        $this->form->addFields([new TLabel('Evento:', 'red')], [$titulo_evento]);
        $status_evento = new TCombo('status_evento');
        $status_evento->addItems(['1' => 'Aberto', '0' => 'Fechado']);
        $this->form->addFields([new TLabel('Status:', 'red')], [$status_evento]);

        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
        $this->form->addActionLink('Novo', new TAction(['EventosFormView', 'onClear']), 'fa:plus-circle green');
        $this->form->addActionLink('Limpar', new TAction([$this, 'clear']), 'fa:eraser red');

        $this->form->setData(TSession::getValue('EventosView_filter_data'));
    }

    /**
     * Datagrid e suas colunas
     */
    private function createDataGrid()
    {
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = "100%";

        $id_evento       = new TDataGridColumn('id_evento', 'ID', 'left', '5%');
        $titulo_evento    = new TDataGridColumn('titulo_evento', 'Evento', 'left', '30%');
        $data_inicio      = new TDataGridColumn('data_inicio_evento', 'Data de Início', 'center', '15%');
        $data_fim         = new TDataGridColumn('data_fim_evento', 'Data de Fim', 'center', '15%');
        $valor_evento     = new TDataGridColumn('valor_evento', 'Valor', 'center', '5%');
        $status_evento    = new TDataGridColumn('status_evento', 'Status', 'center', '10%');
        $gerente_evento   = new TDataGridColumn('gerente_evento_name', 'Gerente', 'left', '30%');

        $this->datagrid->addColumn($id_evento);
        $this->datagrid->addColumn($titulo_evento);
        $this->datagrid->addColumn($data_inicio);
        $this->datagrid->addColumn($data_fim);
        $this->datagrid->addColumn($valor_evento);
        $this->datagrid->addColumn($status_evento);
        $this->datagrid->addColumn($gerente_evento);

        $status_evento->setTransformer(
            fn($value) => $value == 1 ? '<span class="label label-success">Aberto</span>'
                                      : '<span class="label label-danger">Fechado</span>'
        );

        $data_inicio->setTransformer(fn($value) => (new DateTime($value))->format('d/m/Y H:i'));
        $data_fim->setTransformer(fn($value) => (new DateTime($value))->format('d/m/Y H:i'));

        $id_evento->setAction(new TAction([$this, 'onReload']), ['order' => 'id_evento']);
        $titulo_evento->setAction(new TAction([$this, 'onReload']), ['order' => 'titulo_evento']);
        $data_inicio->setAction(new TAction([$this, 'onReload']), ['order' => 'data_inicio_evento']);
        $data_fim->setAction(new TAction([$this, 'onReload']), ['order' => 'data_fim_evento']);
        $valor_evento->setAction(new TAction([$this, 'onReload']), ['order' => 'valor_evento']);
        $status_evento->setAction(new TAction([$this, 'onReload']), ['order' => 'status_evento']);

        $this->datagrid->addAction(
            new TDataGridAction(['EventosFormView', 'onEdit'], ['id_evento' => '{id_evento}']),
            'Edit',
            'far:edit blue'
        );

        $this->datagrid->addAction(
            new TDataGridAction([$this, 'onDelete'], ['id_evento' => '{id_evento}']),
            'Delete',
            'far:trash-alt red'
        );

        $this->datagrid->addAction(
            new TDataGridAction(['EventosView', 'onRelatorioEventosView'], ['id_evento' => '{id_evento}']),
            'Relatório',
            'fa:cubes green fa-lg'
        );

        $this->datagrid->createModel();
    }

    /**
     * Navegação de página
     */
    private function createPageNavigation()
    {
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
    }

    /**
     * Limpa os filtros
     */
    public function clear()
    {
        $this->clearFilters();
        $this->onReload();
    }

    /**
     * Edição
     */
    public function onEdit($param)
    {
        try {
            TTransaction::open('test');

            if (isset($param['id_evento'])) {
                $this->form->setData(new Eventos($param['id_evento']));
            } else {
                $this->form->clear(true);
            }

            TTransaction::close();
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onRelatorioEventosView($param)
    {
        try {
            if (!isset($param['id_evento'])) {
                throw new Exception('Evento não informado');
            }

            // Redireciona para a tela do relatório
            TApplication::loadPage('RelatorioEventosView', 'onReload', ['id_evento' => $param['id_evento']]);
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
}
