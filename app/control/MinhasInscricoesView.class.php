<?php
/**
 * DatagridBootstrapView
 *
 * @version    1.0
 * @package    samples
 * @subpackage tutor
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    https://adiantiframework.com.br/license-tutor
 */
class MinhasInscricoesView extends TStandardList
{
    protected $datagrid;

    public function __construct()
    {
        parent::__construct();

        parent::setDatabase('test');
        parent::setActiveRecord('Inscricoes');

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = '100%';

        $this->configureColumns();
        $this->configureActions();

        $this->datagrid->createModel();

        $panel = new TPanelGroup('Minhas Inscrições');
        $panel->add($this->datagrid)->style = 'overflow-x:auto';
        $panel->addFooter('O Certificado é liberado após a confirmação da presença!');

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($panel);

        parent::add($vbox);
    }

    private function configureColumns()
    {
        $col_evento  = new TDataGridColumn('evento', 'Evento', 'center', '40%');
        $col_data    = new TDataGridColumn('data_inscricao', 'Data de Inscrição', 'center', '30%');
        $col_status  = new TDataGridColumn('status_inscricao', 'Status', 'center', '20%');

        $col_data->setTransformer(fn($v) => (new DateTime($v))->format('d/m/Y H:i'));
        $col_status->setTransformer([$this, 'formatStatus']);

        $this->datagrid->addColumn($col_evento);
        $this->datagrid->addColumn($col_data);
        $this->datagrid->addColumn($col_status);
    }

    private function configureActions()
    {
        $action = new TDataGridAction([$this, 'onEmitirCertificado'], ['id_inscricao' => '{id_inscricao}']);
        $action->setUseButton(true);
        $action->setButtonClass('btn btn-default');
        $action->setLabel('Emitir Certificado');
        $action->setImage('fa:certificate blue');

        $this->datagrid->addAction($action);
    }

    public function onReload($param = null)
    {
        try {
            TTransaction::open('test');

            $userId = TSession::getValue('userid');
            if (!$userId) {
                throw new Exception('Usuário não autenticado.');
            }

            $criteria = new TCriteria;
            $criteria->add(new TFilter('id_usuario', '=', $userId));

            $repository = new TRepository('Inscricoes');
            $inscricoes = $repository->load($criteria);

            $this->datagrid->clear();

            if ($inscricoes) {
                foreach ($inscricoes as $inscricao) {
                    $this->datagrid->addItem($inscricao);
                }
            }

            TTransaction::close();
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function show()
    {
        $this->onReload();
        parent::show();
    }

    public function onEmitirCertificado($param)
    {
        try {
            TTransaction::open('test');

            $id = (int) ($param['id_inscricao'] ?? 0);
            $inscricao = new Inscricoes($id);

            if ($inscricao->status_inscricao != 1) {
                throw new Exception('O certificado estará disponível apenas após a confirmação da sua inscrição.');
            }

            TTransaction::close();

            $url = "index.php?class=EmitirCertificados&id_inscricao={$id}";
            TScript::create("window.open('{$url}', '_blank');");

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('warning', $e->getMessage());
        }
    }

    public function formatStatus($value)
    {
        return match($value) {
            0 => '<span class="label label-danger">Pendente</span>',
            1 => '<span class="label label-success">Confirmada</span>',
            default => $value,
        };
    }
}

