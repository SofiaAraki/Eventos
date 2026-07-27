<?php

class RelatorioEventoMonitor extends TPage
{
    protected $datagrid;
    protected $pageNavigation;
    protected $evento;

    use Adianti\Base\AdiantiStandardFormTrait;

    public function __construct($param = null)
    {
        parent::__construct();

        try {
            TTransaction::open('teste');

            $id_evento = $param['id_evento'] ?? TSession::getValue('eventoid');

            if (empty($id_evento)) {
                throw new Exception('Evento não identificado.');
            }

            TSession::setValue('eventoid', $id_evento);
            $this->evento = new Evento($id_evento);

            $countTodos     = Inscricao::where('id_evento', '=', $id_evento)->count();
            $countPresentes = ViewRelatorioParticipantes::where('id_evento', '=', $id_evento)->where('esta_presente', '>', 0)->count();
            $countSairam    = ViewRelatorioParticipantes::where('id_evento', '=', $id_evento)->where('ja_saiu', '>', 0)->count();

            TTransaction::close();

            $row_indicators = new TElement('div');
            $row_indicators->class = 'row';
            $row_indicators->style = 'margin-bottom:20px';

            $ind1 = new TNumericIndicator;
            $ind1->setTitle('INSCRITOS');
            $ind1->setValue($countTodos);
            $ind1->setIcon('users');
            $ind1->setColor('blue');
            $ind1->setNumericMask(0, '', '.');

            $ind2 = new TNumericIndicator;
            $ind2->setTitle('PRESENTES');
            $ind2->setValue($countPresentes);
            $ind2->setIcon('check-circle');
            $ind2->setColor('green');
            $ind2->setNumericMask(0, '', '.');

            $ind3 = new TNumericIndicator;
            $ind3->setTitle('SAÍRAM');
            $ind3->setValue($countSairam);
            $ind3->setIcon('sign-out-alt');
            $ind3->setColor('yellow');
            $ind3->setNumericMask(0, '', '.');

            $row_indicators->add($div1 = TElement::tag('div', $ind1));
            $row_indicators->add($div2 = TElement::tag('div', $ind2));
            $row_indicators->add($div3 = TElement::tag('div', $ind3));
            $div1->class = $div2->class = $div3->class = 'col-sm-4 col-xs-12';

            $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
            $this->datagrid->style = 'width:100%';

            $this->datagrid->addColumn(new TDataGridColumn('id_inscricao', 'ID', 'center', '5%'));
            $this->datagrid->addColumn(new TDataGridColumn('usuario_name', 'Nome', 'left'));
            
            $col_status = new TDataGridColumn('status_label', 'Status', 'center');
            $col_status->setTransformer(function ($value) {
                $class = $value == 'Confirmado' ? 'success' : 'warning';
                return "<span class='badge badge-{$class}'>{$value}</span>";
            });
            $this->datagrid->addColumn($col_status);

            $this->datagrid->addColumn(new TDataGridColumn('ultima_entrada', 'Entrada', 'center'));
            $this->datagrid->addColumn(new TDataGridColumn('ultima_saida', 'Saída', 'center'));
            $this->datagrid->addColumn(new TDataGridColumn('permanencia_total', 'Permanência', 'center'));
            $this->datagrid->addColumn(new TDataGridColumn('responsavel_nome', 'Responsável', 'center'));

            $this->datagrid->createModel();

            $panel = new TPanelGroup($this->evento->titulo_evento);
            $panel->add($this->datagrid);
            $panel->addHeaderActionLink('CSV', new TAction([$this, 'exportAsCSV']), 'fa:table blue');

            $this->pageNavigation = new TPageNavigation;
            $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
            $panel->addFooter($this->pageNavigation);

            $vbox = new TVBox;
            $vbox->style = 'width:100%';
            $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
            $vbox->add($row_indicators);
            $vbox->add($panel);

            parent::add($vbox);
            $this->onReload($param);

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onReload($param = null)
    {
        try {
            TTransaction::open('teste');
            $this->datagrid->clear();

            $repository = new TRepository('Inscricao');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('id_evento', '=', TSession::getValue('eventoid')));
            $criteria->setProperty('limit', 10);
            $criteria->setProperties($param);

            $inscricoes = $repository->load($criteria);

            if ($inscricoes) {
                foreach ($inscricoes as $inscricao) {
                    $this->datagrid->addItem($inscricao);
                }
            }

            $criteria_count = clone $criteria;
            $criteria_count->resetProperties();
            $this->pageNavigation->setCount($repository->count($criteria_count));
            $this->pageNavigation->setProperties($param);

            TTransaction::close();
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function exportAsCSV($param)
    {
        try {
            TTransaction::open('teste');

            $id_evento = TSession::getValue('eventoid');
            $repository = new TRepository('Inscricao');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('id_evento', '=', $id_evento));
            $criteria->setProperty('order', 'id_inscricao');

            $inscricoes = $repository->load($criteria);

            if (!$inscricoes) {
                new TMessage('info', 'Nenhum registro encontrado para exportar.');
                TTransaction::close();
                return;
            }

            $file = 'app/output/relatorio_evento_' . $id_evento . '.csv';
            $handler = fopen($file, 'w');
            fprintf($handler, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handler, ['ID', 'Nome', 'Tipo', 'Status', 'Entrada', 'Saída', 'Permanência', 'Responsável'], ';', '"', "");

            foreach ($inscricoes as $inscricao)
            {
                $row = [
                    $inscricao->id_inscricao,
                    $inscricao->usuario_name,
                    ucfirst($inscricao->tipo_participacao),
                    $inscricao->status_label,
                    $inscricao->ultima_entrada,
                    $inscricao->ultima_saida,
                    $inscricao->permanencia_total,
                    $inscricao->responsavel_nome
                ];
                fputcsv($handler, $row, ';', '"', "");
            }

            fclose($handler);
            TTransaction::close();

            parent::openFile($file);

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', 'Erro ao exportar: ' . $e->getMessage());
        }
    }
}