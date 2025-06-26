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
    protected $form;
    protected $datagrid;
    protected $pageNavigation;

    use Adianti\Base\AdiantiStandardListTrait;

    public function __construct()
    {
        parent::__construct();

        $this->setDatabase('test');
        $this->setActiveRecord('Pagamentos');
        $this->setDefaultOrder('id_pagamento', 'asc');

        $this->form = new BootstrapFormBuilder('form_search_Pagamentos');
        $this->form->setFormTitle('Gerenciamento de Pagamentos');

        $id_evento = new TDBUniqueSearch('id_evento', 'test', 'Eventos', 'id_evento', 'titulo_evento');
        $id_usuario = new TDBUniqueSearch('id_usuario', 'test', 'SystemUser', 'id', 'name');

        $this->form->addFields([new TLabel('Evento:', 'red')], [$id_evento]);
        $this->form->addFields([new TLabel('Usuário:', 'red')], [$id_usuario]);

        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
        $this->form->addActionLink('Novo', new TAction(['PagamentosFormView', 'onClear']), 'fa:plus-circle green');
        $this->form->addActionLink('Limpar', new TAction([$this, 'clear']), 'fa:eraser red');

        $this->form->setData(TSession::getValue('PagamentosView_filter_data'));

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = "100%";
        $this->datagrid->id = 'Pagamentos_datagrid';

        $this->createDatagridColumns();
        $this->createDatagridActions();

        $this->datagrid->createModel();

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

    private function createDatagridColumns()
    {
        $columns = [
            new TDataGridColumn('id_pagamento', 'ID', 'left', '5%'),
            new TDataGridColumn('usuario', 'Nome', 'left', '30%'),
            new TDataGridColumn('evento', 'Evento', 'left', '30%'),
            new TDataGridColumn('valor_evento', 'Valor', 'left', '5%'),
            new TDataGridColumn('data_pagamento', 'Data de Pagamento', 'center', '15%'),
            new TDataGridColumn('status_pagamento', 'Status', 'center', '10%'),
        ];

        $columns[4]->setTransformer(fn($value) => (new DateTime($value))->format('d/m/Y H:i'));

        $columns[5]->setTransformer(function ($value, $object) {
            $label = ($value == 1) ? 'Confirmado' : 'Pendente';
            $color = ($value == 1) ? 'success' : 'danger';

            $action = new TAction([__CLASS__, 'onChangeStatus']);
            $action->setParameter('key', $object->id_pagamento);
            $action->setParameter('static', 'form_search_Pagamentos');

            $link = $action->serialize(TRUE);

            return "<a href=\"javascript:__adianti_ajax_exec('{$link}')\" onclick=\"event.stopPropagation();\">
                        <span class=\"btn btn-sm btn-{$color}\">{$label}</span>
                    </a>";
        });

        foreach ($columns as $col) {
            $this->datagrid->addColumn($col);
        }
    }

    private function createDatagridActions()
    {
        $edit   = new TDataGridAction(['PagamentosFormView', 'onEdit'], ['key' => '{id_pagamento}']);
        $delete = new TDataGridAction([$this, 'onDelete'], ['key' => '{id_pagamento}']);

        $this->datagrid->addAction($edit, 'Editar', 'far:edit blue');
        $this->datagrid->addAction($delete, 'Excluir', 'far:trash-alt red');
    }

    public static function onChangeStatus($param)
    {
        try {
            TTransaction::open('test');
            $pagamento = new Pagamentos($param['key']);
            $pagamento->status_pagamento = ($pagamento->status_pagamento == 1) ? 0 : 1;
            $pagamento->store();
            TTransaction::close();

            TScript::create("__adianti_load_page('index.php?class=PagamentosView&method=onReload')");
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onReload($param = null)
    {
        try {
            TTransaction::open('test');

            $criteria = new TCriteria;
            $criteria->setProperties($param);
            $criteria->setProperty('limit', 10);

            $filter_data = TSession::getValue('PagamentosView_filter_data');
            $ids_inscricao = [];

            if ($filter_data && (!empty($filter_data->id_usuario) || !empty($filter_data->id_evento))) {
                $insc_repo = new TRepository('Inscricoes');
                $insc_crit = new TCriteria;

                if (!empty($filter_data->id_usuario)) {
                    $insc_crit->add(new TFilter('id_usuario', '=', $filter_data->id_usuario));
                }

                if (!empty($filter_data->id_evento)) {
                    $insc_crit->add(new TFilter('id_evento', '=', $filter_data->id_evento));
                }

                $inscricoes = $insc_repo->load($insc_crit);
                foreach ($inscricoes as $insc) {
                    $ids_inscricao[] = (int) $insc->id_inscricao;
                }

                $criteria->add(new TFilter('id_inscricao', 'IN', $ids_inscricao ?: [0]));
            }

            $repository = new TRepository('Pagamentos');
            $items = $repository->load($criteria, FALSE);

            $this->datagrid->clear();
            if ($items) {
                foreach ($items as $item) {
                    $item->usuario = $item->get_usuario()->name ?? '';
                    $item->evento = $item->get_evento()->titulo_evento ?? '';
                    $this->datagrid->addItem($item);
                }
            }

            $this->pageNavigation->setCount($repository->count($criteria));
            $this->pageNavigation->setProperties($param);
            $this->pageNavigation->setLimit(10);

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}
