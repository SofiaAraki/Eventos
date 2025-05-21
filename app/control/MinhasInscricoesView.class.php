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

        $titulo_evento = new TDataGridColumn('evento', 'Evento', 'center', '40%');
        $data_inscricao   = new TDataGridColumn('data_inscricao', 'Data de Inscrição', 'center', '30%');
        $status_inscricao = new TDataGridColumn('status_inscricao', 'Status', 'center', '20%');

        $this->datagrid->addColumn($titulo_evento);
        $this->datagrid->addColumn($data_inscricao);
        $this->datagrid->addColumn($status_inscricao);

        $data_inscricao->setTransformer(function($value, $object, $row) {
            $date = new DateTime($value);
            return $date->format('d/m/Y H:i');
        });
        $status_inscricao->setTransformer(function ($value, $object, $row) {
            switch ($value) {
                case 0: return '<span class="label label-danger">Pendente</span>';
                case 1: return '<span class="label label-success">Confirmada</span>';
                default: return $value;
            }
        });

        $action = new TDataGridAction([$this, 'onEmitirCertificado'], ['id_inscricao' => '{id_inscricao}']);
        $action->setUseButton(true);
        $action->setButtonClass('btn btn-default');
        $action->setLabel('Emitir Certificado');
        $action->setImage('fa:certificate blue');

        $this->datagrid->addAction($action);

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

    public function onReload($param = null)
    {
        try {
            TTransaction::open('test');

            $repository = new TRepository('Inscricoes');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('id_usuario', '=', TSession::getValue('userid')));

            $inscricoes = $repository->load($criteria);

            $this->datagrid->clear();

            if ($inscricoes) {
                foreach ($inscricoes as $inscricao) {
                    $this->datagrid->addItem($inscricao);
                }
            }

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function show()
    {
        $this->onReload();
        parent::show();
    }

    public function onEmitirCertificado($param)
    {   
        TTransaction::open('test');
        
        $inscricao = new Inscricoes($param['id_inscricao']);

        if ($inscricao->status_inscricao != 1) {
            new TMessage('warning', 'O certificado estará disponível apenas após a confirmação da sua inscrição.');
            return;
        }

        TTransaction::close();

        try {
            $id = isset($param['id_inscricao']) ? (int) $param['id_inscricao'] : null;

            if ($id > 0) {
                $url = "index.php?class=EmitirCertificados&id_inscricao={$id}";
                TScript::create("window.open('{$url}', '_blank');");
            } else {
                throw new Exception('Inscrição não encontrada.');
            }
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }

}
