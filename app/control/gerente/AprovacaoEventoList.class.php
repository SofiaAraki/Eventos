<?php
class AprovacaoEventoList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;

    use Adianti\Base\AdiantiStandardListTrait;

    public function __construct()
    {
        parent::__construct();

        $this->setDatabase('teste');
        $this->setActiveRecord('Evento');
        $this->setDefaultOrder('id_evento', 'desc');

        $this->addFilterField('status_aprovacao', '=', 'status_aprovacao');
        $this->addFilterField('data_inicio_evento', '=', 'data_inicio_evento');
        $this->addFilterField('titulo_evento', 'like', 'titulo_evento');
        $this->addFilterField('id_evento', '=', 'id_evento');
        $this->addFilterField('gerente_evento', '=', 'gerente_evento');

        $this->form = new BootstrapFormBuilder('form_search_Aprovacao');
        $this->form->setFormTitle('Painel Geral de Aprovação de Eventos');

        $coordenador = new TDBUniqueSearch('gerente_evento', 'teste', 'SystemUser', 'id', 'name');
        $evento = new TEntry('titulo_evento');

        // Campo de Data com conversão de formato dd/mm/yyyy <-> yyyy-mm-dd
        $data_inicio_evento = new TDate('data_inicio_evento');
        $data_inicio_evento->setMask('dd/mm/yyyy');
        $data_inicio_evento->setDatabaseMask('yyyy-mm-dd');

        $status_aprovacao = new TCombo('status_aprovacao');
        $status_aprovacao->addItems([
            '0' => 'Pendente',
            '1' => 'Aprovado',
            '2' => 'Rejeitado'
        ]);

        $this->form->addFields([new TLabel('Evento:')], [$evento]);
        $this->form->addFields([new TLabel('Coordenador/Solicitante:')], [$coordenador]);
        $this->form->addFields(
            [new TLabel('Status da Solicitação:')], [$status_aprovacao],
            [new TLabel('Data:')], [$data_inicio_evento]
        );

        $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
        $this->form->addAction('Filtrar', new TAction([$this, 'onSearch']), 'fa:search blue');

        // 4. Datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = '100%';

        $col_id      = new TDataGridColumn('id_evento', 'ID', 'center', '5%');
        $col_titulo  = new TDataGridColumn('titulo_evento', 'Evento', 'left', '30%');
        $col_gerente = new TDataGridColumn('gerente_evento_name', 'Coordenador/Solicitante', 'left', '25%');
        $col_inicio  = new TDataGridColumn('data_inicio_evento', 'Data Prevista', 'center', '15%');
        $col_status  = new TDataGridColumn('status_aprovacao', 'Status', 'center', '15%');

        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_titulo);
        $this->datagrid->addColumn($col_gerente);
        $this->datagrid->addColumn($col_inicio);
        $this->datagrid->addColumn($col_status);

        // Formatação da Data na grid
        $col_inicio->setTransformer(fn($v) => $v ? (new DateTime($v))->format('d/m/Y H:i') : '-');

        // Renderização de Badges no Status
        $col_status->enableHtmlConversion();
        $col_status->setTransformer(function($value) {
            switch ((int) $value) {
                case 1:
                    return '<span class="label label-success">Aprovado</span>';
                case 2:
                    return '<span class="label label-danger">Rejeitado</span>';
                default:
                    return '<span class="label label-warning">Pendente</span>';
            }
        });

        // Ações da Grid
        $action_aprovar  = new TDataGridAction([$this, 'onAprovar'], ['id_evento' => '{id_evento}']);
        $action_rejeitar = new TDataGridAction([$this, 'onRejeitarModal'], ['id_evento' => '{id_evento}']);

        $this->datagrid->addAction($action_aprovar, 'Aprovar', 'fa:check-circle green');
        $this->datagrid->addAction($action_rejeitar, 'Rejeitar', 'fa:times-circle red');

        $this->datagrid->createModel();

        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));

        // Mantém os filtros preenchidos na tela ao buscar/paginar
        $this->form->setData(TSession::getValue(__CLASS__ . '_filter_data'));

        // Montagem do Layout
        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->form);
        $vbox->add(TPanelGroup::pack('', $this->datagrid, $this->pageNavigation));

        parent::add($vbox);
    }

    public function onAprovar($param)
    {
        $action = new TAction([$this, 'onConfirmarAprovacao'], $param);
        new TQuestion('Deseja realmente aprovar esta solicitação de evento?', $action);
    }

    public function onConfirmarAprovacao($param)
    {
        try {
            TTransaction::open('teste');
            $id = $param['id_evento'];
            
            $evento = new Evento($id);
            $evento->status_aprovacao = 1; // Aprovado
            $evento->status_evento    = 1; // Ativa evento
            $evento->observacao_aprovacao = 'Solicitação aprovada pela administração.';
            $evento->store();

            TTransaction::close();
            new TMessage('info', 'Evento aprovado e liberado com sucesso!');
            $this->onReload($param);
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onRejeitarModal($param)
    {
        $form = new BootstrapFormBuilder('form_rejeicao');
        $form->setFormTitle('Rejeitar Solicitação de Evento');

        $id_evento = new THidden('id_evento');
        $id_evento->setValue($param['id_evento']);

        $observacao = new TText('observacao_aprovacao');
        $observacao->addValidation('Motivo', new TRequiredValidator);
        $observacao->setSize('100%', '100');

        $form->addFields([$id_evento]);
        $form->addFields([new TLabel('Informe o motivo da rejeição ao coordenador:', 'red')], [$observacao]);

        $form->addAction('Confirmar Rejeição', new TAction([__CLASS__, 'onConfirmarRejeicao']), 'fa:check red');

        $window = TWindow::create('Parecer de Rejeição', 0.5, null);
        $window->add($form);
        $window->show();
    }

    public static function onConfirmarRejeicao($param)
    {
        try {
            TTransaction::open('teste');
            
            $evento = new Evento($param['id_evento']);
            $evento->status_aprovacao = 2; // Rejeitado
            $evento->status_evento    = 0; // Mantém fechado
            $evento->observacao_aprovacao = $param['observacao_aprovacao'];
            $evento->store();

            TTransaction::close();
            
            TWindow::closeWindow();
            new TMessage('info', 'Solicitação rejeitada com sucesso!');
            TApplication::loadPage('AprovacaoEventoList', 'onReload');
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onClear($param = null)
    {
        $this->clearFilters();
        TSession::setValue(__CLASS__ . '_filter_data', null);
        $this->form->clear(true);
        $this->onReload(['offset' => 0, 'first_page' => 1]);
    }
}