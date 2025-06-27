<?php

use Adianti\Base\AdiantiStandardListTrait;
use eventos\Widget\TInfoBox;

class RelatorioEventosView extends TPage
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;

    public function __construct($param = null)
    {
        parent::__construct();

        $this->buildInfoBoxes($param);
        $this->buildDatagrid();
        $this->buildPage();
    }

    private function buildInfoBoxes($param)
    {
        TTransaction::open('test');

        $id_evento = $param['id_evento'] ?? TSession::getValue('eventoid');
        $evento = new Eventos($id_evento);
        TSession::setValue('eventoid', $id_evento);

        $repository = new TRepository('Inscricoes');
        $criteriaBase = new TCriteria;
        $criteriaBase->add(new TFilter('id_evento', '=', $evento->id_evento));

        $criteriaConfirmados = clone $criteriaBase;
        $criteriaConfirmados->add(new TFilter('status_inscricao', '=', "1"));

        $criteriaPendentes = clone $criteriaBase;
        $criteriaPendentes->add(new TFilter('status_inscricao', '=', "0"));

        $countTodos       = $repository->count($criteriaBase);
        $countConfirmados = $repository->count($criteriaConfirmados);
        $countPendentes   = $repository->count($criteriaPendentes);

        $valorTotal = $countConfirmados * (float) $evento->valor_evento;

        TTransaction::close();

        $infoRow = new TElement("div");
        $infoRow->class = "row";
        $infoRow->add(new TInfoBox("blue", "fas fa-users", "INSCRITOS", $countTodos, "", ""));
        $infoRow->add(new TInfoBox("red", "fas fa-times", "PENDENTES", $countPendentes, "", ""));
        $infoRow->add(new TInfoBox("green", "fas fa-check", "CONFIRMADOS", $countConfirmados, "", ""));
        $infoRow->add(new TInfoBox("yellow", "fas fa-money-bill-wave", "VALOR RECEBIDO", 'R$ ' . number_format($valorTotal, 2, ',', '.'), "", ""));

        $header = new TElement("section");
        $header->class = "content-header";
        $header->style = "padding: 0px";
        $header->add("<h1>Relatório de Inscrições - {$evento->titulo_evento}</h1><br>");

        parent::add($header);
        parent::add($infoRow);
    }

    private function buildDatagrid()
    {
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = '100%';

        $this->datagrid->addColumn(new TDataGridColumn('id_inscricao', 'ID', 'center', '10%'));
        $this->datagrid->addColumn(new TDataGridColumn('usuario', 'Nome', 'left', '30%'));
        $this->datagrid->addColumn(new TDataGridColumn('status_inscricao', 'Status', 'center', '20%'));
        $this->datagrid->addColumn(new TDataGridColumn('data_inscricao', 'Data', 'center', '20%'));

        $this->datagrid->createModel();
    }

    private function buildPage()
    {
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));

        $this->form = new BootstrapFormBuilder('form_list_relatorio');
        $this->form->setFormTitle('Lista de Inscritos');

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add($this->datagrid);
        $vbox->add($this->pageNavigation);

        // Aqui está o ajuste importante:
        $row = $this->form->addFields([$vbox]);
        $row->style = 'width: 100%';

        parent::add($this->form);

        $this->onReload();
    }

    public function onReload($param = null)
    {
        TTransaction::open('test');

        $this->datagrid->clear();

        $criteria = new TCriteria;
        $criteria->add(new TFilter('id_evento', '=', TSession::getValue('eventoid')));
        $criteria->setProperties($param);
        $criteria->setProperty('limit', 10);

        $repository = new TRepository('Inscricoes');
        $inscricoes = $repository->load($criteria);

        if ($inscricoes) {
            foreach ($inscricoes as $inscricao) {
                $user = new SystemUser($inscricao->system_user_id);
                $inscricao->usuario = $user->name;
                $inscricao->status_inscricao = $inscricao->status_inscricao == '1' ? 'Confirmado' : 'Pendente';
                $this->datagrid->addItem($inscricao);
            }
        }

        $criteria->resetProperties();
        $count = $repository->count($criteria);

        $this->pageNavigation->setCount($count);
        $this->pageNavigation->setProperties($param);
        $this->pageNavigation->setLimit(10);

        TTransaction::close();
    }

    public function onPrint($param)
    {
        // PDF export logic
    }

    public function onExportaExcel($param)
    {
        // Excel export logic
    }
}
