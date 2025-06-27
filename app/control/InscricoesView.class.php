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
    protected $form;
    protected $datagrid;
    protected $pageNavigation;

    use Adianti\Base\AdiantiStandardListTrait;

    public function __construct()
    {
        parent::__construct();

        $this->setDatabase('test');
        $this->setActiveRecord('Inscricoes');
        $this->addFilterField('id_usuario', '=', 'id_usuario');
        $this->addFilterField('id_evento', '=', 'id_evento');
        $this->addFilterField('status_inscricao', '=', 'status_inscricao');
        $this->setDefaultOrder('id_inscricao', 'desc');

        $this->createSearchForm();
        $this->createDataGrid();
        $this->createPageNavigation();

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->form);
        $vbox->add(TPanelGroup::pack('', $this->datagrid, $this->pageNavigation));

        parent::add($vbox);
    }

    private function createSearchForm()
    {
        $this->form = new BootstrapFormBuilder('form_search_Inscricoes');
        $this->form->setFormTitle('Gerenciamento de Inscrições');

        $id_evento = new TDBUniqueSearch('id_evento', 'test', 'Eventos', 'id_evento', 'titulo_evento');
        $id_usuario = new TDBUniqueSearch('id_usuario', 'test', 'SystemUser', 'id', 'name');
        $status_inscricao = new TCombo('status_inscricao');
        $status_inscricao->addItems(['1' => 'Confirmado', '0' => 'Pendente']);

        $this->form->addFields([new TLabel('Evento:', 'red')], [$id_evento]);
        $this->form->addFields([new TLabel('Usuário:', 'red')], [$id_usuario]);
        $this->form->addFields([new TLabel('Status:', 'red')], [$status_inscricao]);

        $this->addFormActions();
        $this->form->setData(TSession::getValue('InscricoesView_filter_data'));
    }

    private function addFormActions()
    {
        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
        $this->form->addActionLink('Novo', new TAction(['InscricoesFormView', 'onClear']), 'fa:plus-circle green');
        $this->form->addActionLink('Limpar', new TAction([$this, 'clear']), 'fa:eraser red');
    }

    private function createDataGrid()
    {
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = '100%';
        $this->datagrid->id = 'inscricoes_datagrid';

        $col_id = new TDataGridColumn('id_inscricao', 'ID', 'left', '5%');
        $col_tipo = new TDataGridColumn('tipo_participacao', 'Tipo', 'left', '10%');
        $col_usuario = new TDataGridColumn('usuario', 'Nome', 'left', '30%');
        $col_evento = new TDataGridColumn('evento', 'Evento', 'left', '30%');
        $col_data = new TDataGridColumn('data_inscricao', 'Data de Inscrição', 'center', '15%');
        $col_status = new TDataGridColumn('status_inscricao', 'Status', 'center', '10%');

        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_tipo);
        $this->datagrid->addColumn($col_usuario);
        $this->datagrid->addColumn($col_evento);
        $this->datagrid->addColumn($col_data);
        $this->datagrid->addColumn($col_status);

        $col_status->setTransformer([$this, 'formatStatus']);
        $col_data->setTransformer(fn($v) => (new DateTime($v))->format('d/m/Y H:i'));

        $col_id->setAction(new TAction([$this, 'onReload']), ['order' => 'id_evento']);
        $col_usuario->setAction(new TAction([$this, 'onReload']), ['order' => 'id_usuario']);
        $col_data->setAction(new TAction([$this, 'onReload']), ['order' => 'data_inscricao']);

        $this->datagrid->addAction(new TDataGridAction(['InscricoesFormView', 'onEdit'], ['key' => '{id_inscricao}']), 'Edit', 'far:edit blue');
        $this->datagrid->addAction(new TDataGridAction([$this, 'onDelete'], ['key' => '{id_inscricao}']), 'Delete', 'far:trash-alt red');

        $this->datagrid->createModel();
    }

    private function createPageNavigation()
    {
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
    }

    public function clear()
    {
        $this->clearFilters();
        $this->onReload();
    }

    public static function onChangeStatus($param)
    {
        try {
            TTransaction::open('test');

            $pagamento = Pagamentos::where('id_inscricao', '=', $param['key'])->first();

            if ($pagamento && $pagamento->status_pagamento == 0) {
                new TMessage('warning', 'Só é possível confirmar inscrições com pagamento aprovado.');
            } else {
                $inscricao = new Inscricoes($param['key']);
                $inscricao->status_inscricao = ($inscricao->status_inscricao == 1) ? 0 : 1;
                $inscricao->store();
                TTransaction::close();

                TScript::create("__adianti_load_page('index.php?class=InscricoesView&method=onReloadManual');");
            }

        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onReloadManual($param = null)
    {
        try {
            TTransaction::open('test');

            $repository = new TRepository('Inscricoes');
            $limit = 10;

            $criteria = new TCriteria;
            $criteria->setProperties($param);
            $criteria->setProperty('limit', $limit);

            $filter_data = TSession::getValue('InscricoesView_filter_data');
            if (!empty($filter_data->id_usuario)) {
                $criteria->add(new TFilter('id_usuario', '=', $filter_data->id_usuario));
            }
            if (!empty($filter_data->id_evento)) {
                $criteria->add(new TFilter('id_evento', '=', $filter_data->id_evento));
            }

            $inscricoes = $repository->load($criteria, false);
            $this->datagrid->clear();

            if ($inscricoes) {
                foreach ($inscricoes as $inscricao) {
                    $inscricao->usuario = $inscricao->getUsuario()->name ?? '';
                    $inscricao->evento = $inscricao->getEvento()->titulo_evento ?? '';
                    $this->datagrid->addItem($inscricao);
                }
            }

            $this->pageNavigation->setCount($repository->count($criteria));
            $this->pageNavigation->setProperties($param);
            $this->pageNavigation->setLimit($limit);

            TTransaction::close();

        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function formatStatus($value, $object, $row)
    {
        $label = ($value == 1) ? 'Confirmada' : 'Pendente';
        $color = ($value == 1) ? 'success' : 'danger';

        $action = new TAction(['InscricoesView', 'onChangeStatus']);
        $action->setParameter('key', $object->id_inscricao);
        $action->setParameter('static', 'form_search_Inscricoes');
        $link = $action->serialize(true);

        return "<a href=\"javascript:__adianti_ajax_exec('{$link}')\" onclick=\"event.stopPropagation();\">
                    <span class=\"btn btn-sm btn-{$color}\">{$label}</span>
                </a>";
    }
}

