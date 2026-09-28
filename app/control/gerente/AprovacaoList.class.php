<?php
class AprovacaoList extends TPage
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
        $this->form->setFormTitle('Painel Geral de Aprovação de Eventos e Certificados');

        $evento = new TEntry('titulo_evento');
        $coordenador = new TDBUniqueSearch('gerente_evento', 'teste', 'SystemUser', 'id', 'name');

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

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = '100%';

        $col_id          = new TDataGridColumn('id_evento', 'ID', 'center', '5%');
        $col_titulo      = new TDataGridColumn('titulo_evento', 'Evento', 'left', '30%');
        $col_gerente     = new TDataGridColumn('gerente_evento', 'Coordenador', 'left', '25%');
        $col_inicio      = new TDataGridColumn('data_inicio_evento', 'Data Prevista', 'center', '20%');
        $col_status      = new TDataGridColumn('status_aprovacao', 'Status', 'center', '20%');

        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_titulo);
        $this->datagrid->addColumn($col_gerente);
        $this->datagrid->addColumn($col_inicio);
        $this->datagrid->addColumn($col_status);

        $col_gerente->setTransformer(function($value) {
            if (!empty($value)) {
                TTransaction::open('teste');
                $user = new SystemUser($value);
                TTransaction::close();
                return $user->name ?? $value;
            }
            return '-';
        });

        $col_inicio->setTransformer(fn($v) => $v ? (new DateTime($v))->format('d/m/Y H:i') : '-');

        $col_status->enableHtmlConversion();
        $col_status->setTransformer(function($value) {
            switch ((int) $value) {
                case 1:
                    return '<span class="label label-success" style="background-color:#28a745; padding: 4px 8px; color:#fff; border-radius:3px;">Aprovado</span>';
                case 2:
                    return '<span class="label label-danger" style="background-color:#dc3545; padding: 4px 8px; color:#fff; border-radius:3px;">Rejeitado</span>';
                default:
                    return '<span class="label label-warning" style="background-color:#ffc107; padding: 4px 8px; color:#000; border-radius:3px;">Pendente</span>';
            }
        });

        $action_ver      = new TDataGridAction([$this, 'onVisualizar'], ['id_evento' => '{id_evento}']);
        $action_aprovar  = new TDataGridAction([$this, 'onAprovar'], ['id_evento' => '{id_evento}']);
        $action_rejeitar = new TDataGridAction([$this, 'onRejeitarModal'], ['id_evento' => '{id_evento}']);

        $this->datagrid->addAction($action_ver, 'Ver Detalhes', 'fa:eye blue');
        $this->datagrid->addAction($action_aprovar, 'Aprovar Solicitação', 'fa:check-circle green');
        $this->datagrid->addAction($action_rejeitar, 'Rejeitar Solicitação', 'fa:times-circle red');

        $this->datagrid->createModel();

        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));

        $this->form->setData(TSession::getValue(__CLASS__ . '_filter_data'));

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->form);
        $vbox->add(TPanelGroup::pack('', $this->datagrid, $this->pageNavigation));

        parent::add($vbox);
    }

    public static function onVisualizar($param)
    {
        try {
            TTransaction::open('teste');
            $evento = new Evento($param['id_evento']);

            $certificados = Certificado::where('id_evento', '=', $evento->id_evento)->load();
            $cert = $certificados ? reset($certificados) : null;

            $panel = new TElement('div');
            $panel->style = 'padding: 15px; font-size: 14px;';

            $html = "<h4><b>1. Dados do Evento</b></h4>";
            $html .= "<b>Título:</b> {$evento->titulo_evento}<br>";
            $html .= "<b>Local:</b> {$evento->local_evento}<br>";
            $html .= "<b>Início:</b> " . ($evento->data_inicio_evento ? (new DateTime($evento->data_inicio_evento))->format('d/m/Y H:i') : '-') . "<br>";
            $html .= "<b>Fim:</b> " . ($evento->data_fim_evento ? (new DateTime($evento->data_fim_evento))->format('d/m/Y H:i') : '-') . "<br>";
            $html .= "<b>Descrição:</b> {$evento->descricao_evento}<br><hr>";

            $html .= "<h4><b>2. Regras do Certificado</b></h4>";
            if ($cert) {
                $html .= "<b>Nome do Certificado:</b> {$cert->titulo_certificado}<br>";
                $html .= "<b>Carga Horária:</b> {$cert->carga_horaria_certificado} hs<br>";
                $html .= "<b>Presença Mínima:</b> {$cert->presenca_minima_certificado} min<br>";
                $html .= "<b>Fundo do Certificado:</b> {$cert->bg_frente_certificado}<br>";
            } else {
                $html .= "<i>Sem regras de certificado cadastradas para este evento.</i><br>";
            }

            $panel->add($html);

            $window = TWindow::create('Detalhes da Solicitação', 0.6, null);
            $window->add($panel);
            $window->show();

            TTransaction::close();
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onAprovar($param)
    {
        $action = new TAction([$this, 'onConfirmarAprovacao'], $param);
        new TQuestion('Deseja realmente aprovar esta solicitação de evento e liberação de certificado?', $action);
    }

    public function onConfirmarAprovacao($param)
    {
        try {
            TTransaction::open('teste');
            $id = $param['id_evento'];
            
            $evento = new Evento($id);
            $evento->status_aprovacao = 1;
            
            $eTcc = Tcc::where('id_evento', '=', $id)->first();

            if ($eTcc) {
                $evento->status_evento = 0; 
            } else {
                $evento->status_evento = 1; 
            }

            $evento->observacao_aprovacao = 'Solicitação aprovada pela administração.';
            $evento->store();

            TccService::gerarInscricoesECertificadosAprovados($id);

            TTransaction::close();
            new TMessage('info', 'Solicitação aprovada! Inscrições e certificados liberados com sucesso.');
            $this->onReload($param);
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onRejeitarModal($param)
    {
        $form = new BootstrapFormBuilder('form_rejeicao');
        $form->setFormTitle('Rejeitar Solicitação de Evento / Certificado');

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
            $evento->status_aprovacao = 2; 
            $evento->status_evento    = 0; 
            $evento->observacao_aprovacao = $param['observacao_aprovacao'];
            $evento->store();

            TTransaction::close();
            
            TWindow::closeWindow();
            new TMessage('info', 'Solicitação rejeitada com sucesso!');
            TApplication::loadPage('AprovacaoList', 'onReload');
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