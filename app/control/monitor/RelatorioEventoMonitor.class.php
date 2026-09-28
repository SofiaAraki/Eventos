<?php

class RelatorioEventoMonitor extends TPage
{
    protected $datagrid;
    protected $pageNavigation;
    protected $evento;
    protected $form;

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

            $countTodos     = ViewRelatorioParticipantes::where('id_evento', '=', $id_evento)->count();
            $countPresentes = ViewRelatorioParticipantes::where('id_evento', '=', $id_evento)->where('esta_presente', '>', 0)->count();
            $countSairam    = ViewRelatorioParticipantes::where('id_evento', '=', $id_evento)->where('ja_saiu', '>', 0)->count();
            $countCompareceram = $countPresentes + $countSairam;
            
            $taxaPresenca = $countTodos > 0 ? round(($countCompareceram / $countTodos) * 100, 1) : 0;
            //$taxaPresenca = $countTodos > 0 ? round(($countPresentes / $countTodos) * 100, 1) : 0;

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

            $ind4 = new TNumericIndicator;
            $ind4->setTitle('TAXA DE PRESENÇA');
            $ind4->setValue($taxaPresenca);
            $ind4->setIcon('chart-pie');
            $ind4->setColor('purple');
            $ind4->setNumericMask(1, ',', '.', ' %');

            $row_indicators->add($div1 = TElement::tag('div', $ind1));
            $row_indicators->add($div2 = TElement::tag('div', $ind2));
            $row_indicators->add($div3 = TElement::tag('div', $ind3));
            $row_indicators->add($div4 = TElement::tag('div', $ind4));
            $div1->class = $div2->class = $div3->class = $div4->class = 'col-sm-3 col-xs-12';

            $this->form = new BootstrapFormBuilder('form_busca_relatorio');
            $this->form->setFormTitle('Filtros do Relatório');

            $usuario_name = new TEntry('usuario_name');

            $status_filtro = new TCombo('status_filtro');
            $status_filtro->addItems([
                ''          => 'Todos os Status',
                'presente'  => 'Presentes Agora',
                'confirmado' => 'Confirmados',
                'pendente'  => 'Pendentes'
            ]);

            $this->form->addFields([new TLabel('Participante')], [$usuario_name]);
            $this->form->addFields([new TLabel('Status')], [$status_filtro]);

            $this->form->addAction('Filtrar', new TAction([$this, 'onSearch']), 'fa:search blue');
            $this->form->addAction('Limpar', new TAction([$this, 'clearFilters']), 'fa:eraser red');

            $this->form->setData(TSession::getValue(__CLASS__ . '_filter_data'));

            $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
            $this->datagrid->style = 'width:100%';

            $col_id     = new TDataGridColumn('id_inscricao', 'ID', 'center', '5%');
            $col_nome   = new TDataGridColumn('usuario_name', 'Nome', 'left');
            $col_status = new TDataGridColumn('status_label', 'Status', 'center');

            $col_id->setAction(new TAction([$this, 'onReload']), ['order' => 'id_inscricao']);
            $col_nome->setAction(new TAction([$this, 'onReload']), ['order' => 'usuario_name']);

            $col_status->setTransformer(function ($value) {
                $class = $value == 'Confirmado' ? 'success' : ($value == 'Pendente' ? 'warning' : 'danger');
                return "<span class='label label-{$class}'>{$value}</span>";
            });

            $col_entrada = new TDataGridColumn('ultima_entrada', 'Entrada', 'center');
            $col_entrada->setTransformer(fn($v) => !empty($v) ? (new DateTime($v))->format('d/m/Y H:i') : '-');

            $col_saida = new TDataGridColumn('ultima_saida', 'Saída', 'center');
            $col_saida->setTransformer(function($v, $object) {
                if ((int) $object->esta_presente === 1) {
                    return '<span class="label label-info">EM EVENTO</span>';
                }
                if (!empty($v)) {
                    return (new DateTime($v))->format('d/m/Y H:i');
                }
                return '-';
            });

            $col_perm = new TDataGridColumn('permanencia_total', 'Permanência', 'center');

            $this->datagrid->addColumn($col_id);
            $this->datagrid->addColumn($col_nome);
            $this->datagrid->addColumn($col_status);
            $this->datagrid->addColumn($col_entrada);
            $this->datagrid->addColumn($col_saida);
            $this->datagrid->addColumn($col_perm);
            $this->datagrid->addColumn(new TDataGridColumn('responsavel_nome', 'Responsável', 'center'));

            $this->datagrid->createModel();

            $panel = new TPanelGroup($this->evento->titulo_evento);
            $panel->add($this->datagrid);
            $panel->addHeaderActionLink('Exportar CSV', new TAction([$this, 'exportAsCSV']), 'fa:file-excel green');

            $this->pageNavigation = new TPageNavigation;
            $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
            $panel->addFooter($this->pageNavigation);

            $vbox = new TVBox;
            $vbox->style = 'width:100%';
            $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
            $vbox->add($row_indicators);
            $vbox->add($this->form);
            $vbox->add($panel);

            parent::add($vbox);
            $this->onReload($param);

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onSearch()
    {
        $data = $this->form->getData();
        TSession::setValue(__CLASS__ . '_filter_data', $data);
        $this->onReload(['offset' => 0, 'first_page' => 1]);
    }

    public function clearFilters()
    {
        TSession::setValue(__CLASS__ . '_filter_data', null);
        $this->form->clear();
        $this->onReload();
    }

    public function onReload($param = null)
    {
        try {
            TTransaction::open('teste');
            $this->datagrid->clear();

            $repository = new TRepository('ViewRelatorioParticipantes');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('id_evento', '=', TSession::getValue('eventoid')));

            $filterData = TSession::getValue(__CLASS__ . '_filter_data');
            if ($filterData) {
                if (!empty($filterData->usuario_name)) {
                    $criteria->add(new TFilter('usuario_name', 'like', "%{$filterData->usuario_name}%"));
                }
                if (!empty($filterData->status_filtro)) {
                    if ($filterData->status_filtro == 'presente') {
                        $criteria->add(new TFilter('esta_presente', '>', 0));
                    } elseif ($filterData->status_filtro == 'confirmado') {
                        $criteria->add(new TFilter('status_label', '=', 'Confirmado'));
                    } elseif ($filterData->status_filtro == 'pendente') {
                        $criteria->add(new TFilter('status_label', '=', 'Pendente'));
                    }
                }
            }

            $criteria->setProperty('limit', 10);
            $criteria->setProperties($param);

            $registros = $repository->load($criteria);

            if ($registros) {
                foreach ($registros as $registro) {
                    $this->datagrid->addItem($registro);
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
            $repository = new TRepository('ViewRelatorioParticipantes');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('id_evento', '=', $id_evento));

            $filterData = TSession::getValue(__CLASS__ . '_filter_data');
            if ($filterData) {
                if (!empty($filterData->usuario_name)) {
                    $criteria->add(new TFilter('usuario_name', 'like', "%{$filterData->usuario_name}%"));
                }
                if (!empty($filterData->status_filtro)) {
                    if ($filterData->status_filtro == 'presente') {
                        $criteria->add(new TFilter('esta_presente', '>', 0));
                    } elseif ($filterData->status_filtro == 'confirmado') {
                        $criteria->add(new TFilter('status_label', '=', 'Confirmado'));
                    } elseif ($filterData->status_filtro == 'pendente') {
                        $criteria->add(new TFilter('status_label', '=', 'Pendente'));
                    }
                }
            }

            $criteria->setProperty('order', 'id_inscricao');
            $registros = $repository->load($criteria);

            if (!$registros) {
                new TMessage('info', 'Nenhum registro encontrado para exportar.');
                TTransaction::close();
                return;
            }

            $file = 'app/output/relatorio_evento_' . $id_evento . '.csv';
            $handler = fopen($file, 'w');
            fprintf($handler, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handler, ['ID', 'Nome', 'Status', 'Entrada', 'Saída', 'Permanência', 'Responsável'], ';', '"', "");

            foreach ($registros as $reg)
            {
                $saidaTxt = '-';
                if ((int) $reg->esta_presente === 1) {
                    $saidaTxt = 'EM EVENTO';
                } elseif (!empty($reg->ultima_saida)) {
                    $saidaTxt = (new DateTime($reg->ultima_saida))->format('d/m/Y H:i');
                }

                $row = [
                    $reg->id_inscricao,
                    $reg->usuario_name,
                    $reg->status_label,
                    !empty($reg->ultima_entrada) ? (new DateTime($reg->ultima_entrada))->format('d/m/Y H:i') : '-',
                    $saidaTxt,
                    $reg->permanencia_total,
                    $reg->responsavel_nome ?? '-'
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