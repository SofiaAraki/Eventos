<?php

class RelatorioEvento extends TPage
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
            $id_evento = $param['id_evento'] ?? TSession::getValue('eventoid');

            if (empty($id_evento)) {
                throw new Exception('Evento não identificado.');
            }

            TSession::setValue('eventoid', $id_evento);

            TTransaction::open('teste');
            $this->evento = new Evento($id_evento);

            // --- 1. CONSULTAS ESTATÍSTICAS ---
            $registrosTodos = ViewRelatorioParticipantes::where('id_evento', '=', $id_evento)->load();
            
            $countTodos          = count($registrosTodos ?? []);
            $countPresentes      = 0;
            $countSairam         = 0;
            $somaPermanenciaMin  = 0;
            
            $tiposParticipacaoCounts = [];
            $cursosCounts            = []; // <- Nova estatística
            $picoEntradasCounts      = [];
            $picoSaidasCounts        = [];
            $dias                    = ['' => 'Todos os Dias'];

            if ($registrosTodos) {
                foreach ($registrosTodos as $reg) {
                    if ((int)$reg->esta_presente > 0) {
                        $countPresentes++;
                    }
                    if ((int)$reg->ja_saiu > 0) {
                        $countSairam++;
                    }

                    $minutosReg = (int)($reg->total_minutos ?? $reg->permanencia_min ?? 0);
                    $somaPermanenciaMin += $minutosReg;

                    // Agrupamento por Descrição do Perfil
                    $tipoDesc = !empty($reg->tipo_participacao_descricao) ? $reg->tipo_participacao_descricao : 'Indefinido';
                    $tiposParticipacaoCounts[$tipoDesc] = ($tiposParticipacaoCounts[$tipoDesc] ?? 0) + 1;

                    // Agrupamento por Curso
                    $cursoNome = !empty($reg->usuario_curso) ? $reg->usuario_curso : 'Não Informado / Outros';
                    $cursosCounts[$cursoNome] = ($cursosCounts[$cursoNome] ?? 0) + 1;

                    // Fluxo por Horário (Entrada)
                    if (!empty($reg->ultima_entrada)) {
                        $horaEntrada = date('H:00', strtotime($reg->ultima_entrada));
                        $picoEntradasCounts[$horaEntrada] = ($picoEntradasCounts[$horaEntrada] ?? 0) + 1;
                    }

                    // Fluxo por Horário (Saída)
                    if (!empty($reg->ultima_saida)) {
                        $horaSaida = date('H:00', strtotime($reg->ultima_saida));
                        $picoSaidasCounts[$horaSaida] = ($picoSaidasCounts[$horaSaida] ?? 0) + 1;
                    }

                    if (!empty($reg->dia_evento)) {
                        $data = date('Y-m-d', strtotime($reg->dia_evento));
                        $dias[$data] = date('d/m/Y', strtotime($data));
                    }
                }
            }

            // Mapeamento das horas combinadas para o gráfico comparativo (Entrada x Saída)
            $todasHoras = array_unique(array_merge(array_keys($picoEntradasCounts), array_keys($picoSaidasCounts)));
            sort($todasHoras);

            $dataEntradasPlot = [];
            $dataSaidasPlot   = [];

            foreach ($todasHoras as $h) {
                $dataEntradasPlot[] = $picoEntradasCounts[$h] ?? 0;
                $dataSaidasPlot[]   = $picoSaidasCounts[$h] ?? 0;
            }
            
            ksort($dias);

            $countCompareceram = $countPresentes + $countSairam;
            $taxaPresenca      = $countTodos > 0 ? round(($countCompareceram / $countTodos) * 100, 1) : 0;
            $taxaNoShow        = $countTodos > 0 ? round((($countTodos - $countCompareceram) / $countTodos) * 100, 1) : 0;
            
            $mediaPermanenciaMin = $countCompareceram > 0 ? round($somaPermanenciaMin / $countCompareceram) : 0;
            $horasMed = floor($mediaPermanenciaMin / 60);
            $minMed   = $mediaPermanenciaMin % 60;
            $permanenciaTexto = sprintf('%02dh %02dmin', $horasMed, $minMed);

            TTransaction::close();

            // --- 2. CARDS DE INDICADORES ---
            $row_ind1 = new TElement('div');
            $row_ind1->class = 'row';
            $row_ind1->style = 'margin-bottom: 15px;';

            $ind1 = new TNumericIndicator;
            $ind1->setTitle('INSCRITOS');
            $ind1->setValue($countTodos);
            $ind1->setIcon('users');
            $ind1->setColor('blue');
            $ind1->setNumericMask(0, '', '.');

            $ind2 = new TNumericIndicator;
            $ind2->setTitle('PRESENTES AGORA');
            $ind2->setValue($countPresentes);
            $ind2->setIcon('check-circle');
            $ind2->setColor('green');
            $ind2->setNumericMask(0, '', '.');

            $ind3 = new TNumericIndicator;
            $ind3->setTitle('JÁ SAÍRAM');
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

            $row_ind1->add($d1 = TElement::tag('div', $ind1));
            $row_ind1->add($d2 = TElement::tag('div', $ind2));
            $row_ind1->add($d3 = TElement::tag('div', $ind3));
            $row_ind1->add($d4 = TElement::tag('div', $ind4));
            $d1->class = $d2->class = $d3->class = $d4->class = 'col-sm-3 col-xs-12';

            $row_ind2 = new TElement('div');
            $row_ind2->class = 'row';
            $row_ind2->style = 'margin-bottom: 25px;';

            $ind5 = new TNumericIndicator;
            $ind5->setTitle('TEMPO MÉDIO NO EVENTO');
            $ind5->setValue($permanenciaTexto);
            $ind5->setIcon('clock');
            $ind5->setColor('cyan');
            $ind5->setNumericMask(0, '', '.');

            $ind6 = new TNumericIndicator;
            $ind6->setTitle('TAXA DE AUSÊNCIA (NO-SHOW)');
            $ind6->setValue($taxaNoShow);
            $ind6->setIcon('user-slash');
            $ind6->setColor('red');
            $ind6->setNumericMask(1, ',', '.', ' %');

            $ind7 = new TNumericIndicator;
            $ind7->setTitle('COMPARECERAM (TOTAL)');
            $ind7->setValue($countCompareceram);
            $ind7->setIcon('user-check');
            $ind7->setColor('indigo');
            $ind7->setNumericMask(0, '', '.');

            $row_ind2->add($d5 = TElement::tag('div', $ind5));
            $row_ind2->add($d6 = TElement::tag('div', $ind6));
            $row_ind2->add($d7 = TElement::tag('div', $ind7));
            $d5->class = $d6->class = $d7->class = 'col-sm-4 col-xs-12';

            // --- 3. GRÁFICOS (CHART.JS) ---
            $chartDiv = new TElement('div');
            $chartDiv->class = 'row';
            $chartDiv->style = 'margin-bottom: 25px;';

            $cardChart1 = new TElement('div');
            $cardChart1->class = 'col-md-4';
            $cardChart1->add('
                <div class="panel panel-default" style="border-radius:6px; background:#22252a; border:1px solid #333;">
                    <div class="panel-heading" style="background:#1a1c20; color:#fff; font-weight:bold;">
                        <i class="fa fa-chart-pie"></i> Participação por Perfil
                    </div>
                    <div class="panel-body"><canvas id="chartTipos" height="200"></canvas></div>
                </div>
            ');

            $cardChartCursos = new TElement('div');
            $cardChartCursos->class = 'col-md-4';
            $cardChartCursos->add('
                <div class="panel panel-default" style="border-radius:6px; background:#22252a; border:1px solid #333;">
                    <div class="panel-heading" style="background:#1a1c20; color:#fff; font-weight:bold;">
                        <i class="fa fa-graduation-cap"></i> Participação por Curso
                    </div>
                    <div class="panel-body"><canvas id="chartCursos" height="200"></canvas></div>
                </div>
            ');

            $cardChart2 = new TElement('div');
            $cardChart2->class = 'col-md-4';
            $cardChart2->add('
                <div class="panel panel-default" style="border-radius:6px; background:#22252a; border:1px solid #333;">
                    <div class="panel-heading" style="background:#1a1c20; color:#fff; font-weight:bold;">
                        <i class="fa fa-chart-line"></i> Fluxo Entradas x Saídas
                    </div>
                    <div class="panel-body"><canvas id="chartHorarios" height="200"></canvas></div>
                </div>
            ');

            $chartDiv->add($cardChart1);
            $chartDiv->add($cardChartCursos);
            $chartDiv->add($cardChart2);

            $labelsTipos   = json_encode(array_keys($tiposParticipacaoCounts));
            $valuesTipos   = json_encode(array_values($tiposParticipacaoCounts));

            $labelsCursos  = json_encode(array_keys($cursosCounts));
            $valuesCursos  = json_encode(array_values($cursosCounts));

            $labelsHoras   = json_encode($todasHoras);
            $valuesEntradas = json_encode($dataEntradasPlot);
            $valuesSaidas   = json_encode($dataSaidasPlot);

            $scriptChart = new TElement('script');
            $scriptChart->add("
                if (typeof Chart === 'undefined') {
                    var script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
                    script.onload = renderCharts;
                    document.head.appendChild(script);
                } else {
                    renderCharts();
                }

                function renderCharts() {
                    new Chart(document.getElementById('chartTipos'), {
                        type: 'doughnut',
                        data: {
                            labels: {$labelsTipos},
                            datasets: [{
                                data: {$valuesTipos},
                                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#6366f1', '#14b8a6', '#f97316']
                            }]
                        },
                        options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { color: '#ccc' } } } }
                    });

                    new Chart(document.getElementById('chartCursos'), {
                        type: 'pie',
                        data: {
                            labels: {$labelsCursos},
                            datasets: [{
                                data: {$valuesCursos},
                                backgroundColor: ['#06b6d4', '#8b5cf6', '#f59e0b', '#10b981', '#3b82f6', '#ec4899', '#64748b', '#e11d48']
                            }]
                        },
                        options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { color: '#ccc' } } } }
                    });

                    new Chart(document.getElementById('chartHorarios'), {
                        type: 'line',
                        data: {
                            labels: {$labelsHoras},
                            datasets: [
                                {
                                    label: 'Entradas',
                                    data: {$valuesEntradas},
                                    borderColor: '#10b981',
                                    backgroundColor: 'rgba(16, 185, 129, 0.2)',
                                    fill: true,
                                    tension: 0.3
                                },
                                {
                                    label: 'Saídas',
                                    data: {$valuesSaidas},
                                    borderColor: '#ef4444',
                                    backgroundColor: 'rgba(239, 68, 68, 0.2)',
                                    fill: true,
                                    tension: 0.3
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                x: { ticks: { color: '#ccc' } },
                                y: { ticks: { color: '#ccc', stepSize: 1 } }
                            },
                            plugins: { legend: { labels: { color: '#ccc' } } }
                        }
                    });
                }
            ");

            // --- 4. RESUMO EXECUTIVO ---
            $rowResumos = new TElement('div');
            $rowResumos->class = 'row';
            $rowResumos->style = 'margin-bottom: 25px;';

            // Tabela Perfil
            $tableResumo = new TTable;
            $tableResumo->class = 'table table-bordered table-striped';
            $tableResumo->style = 'background: #22252a; color: #eee;';
            $tableResumo->addRowSet(['<b>Perfil / Tipo</b>', '<b>Inscritos</b>', '<b>%</b>'], true);

            foreach ($tiposParticipacaoCounts as $tipoStr => $qtd) {
                $porcentagem = $countTodos > 0 ? round(($qtd / $countTodos) * 100, 1) : 0;
                $tableResumo->addRowSet([
                    $tipoStr,
                    $qtd,
                    "<span class='label label-info'>{$porcentagem}%</span>"
                ]);
            }

            $panelResumo = new TPanelGroup('Resumo Executivo por Categoria');
            $panelResumo->add($tableResumo);

            // Tabela Curso
            $tableResumoCurso = new TTable;
            $tableResumoCurso->class = 'table table-bordered table-striped';
            $tableResumoCurso->style = 'background: #22252a; color: #eee;';
            $tableResumoCurso->addRowSet(['<b>Curso</b>', '<b>Inscritos</b>', '<b>%</b>'], true);

            foreach ($cursosCounts as $cursoStr => $qtd) {
                $porcentagem = $countTodos > 0 ? round(($qtd / $countTodos) * 100, 1) : 0;
                $tableResumoCurso->addRowSet([
                    $cursoStr,
                    $qtd,
                    "<span class='label label-success'>{$porcentagem}%</span>"
                ]);
            }

            $panelResumoCurso = new TPanelGroup('Resumo Executivo por Curso');
            $panelResumoCurso->add($tableResumoCurso);

            $colRes1 = new TElement('div');
            $colRes1->class = 'col-md-6';
            $colRes1->add($panelResumo);

            $colRes2 = new TElement('div');
            $colRes2->class = 'col-md-6';
            $colRes2->add($panelResumoCurso);

            $rowResumos->add($colRes1);
            $rowResumos->add($colRes2);

            // --- 5. FILTROS ---
            $this->form = new BootstrapFormBuilder('form_busca_gerencia');
            $this->form->setFormTitle('Filtros Detalhados');

            $usuario_name = new TEntry('usuario_name');
            $usuario_name->setSize('100%');

            $usuario_curso = new TEntry('usuario_curso');
            $usuario_curso->setSize('100%');

            $status_filtro = new TCombo('status_filtro');
            $status_filtro->addItems([
                ''           => 'Todos os Status',
                'presente'   => 'Presentes Agora',
                'confirmado' => 'Confirmados',
                'pendente'   => 'Pendentes'
            ]);
            $status_filtro->setSize('100%');

            $dia_evento = new TCombo('dia_evento');
            $dia_evento->addItems($dias);
            $dia_evento->setSize('100%');

            $this->form->addFields([new TLabel('Participante')], [$usuario_name], [new TLabel('Curso')], [$usuario_curso]);
            $this->form->addFields([new TLabel('Status')], [$status_filtro], [new TLabel('Dia do Evento')], [$dia_evento]);

            $this->form->addAction('Filtrar', new TAction([$this, 'onSearch']), 'fa:search blue');
            $this->form->addAction('Limpar', new TAction([$this, 'clearFilters']), 'fa:eraser red');

            $this->form->setData(TSession::getValue(__CLASS__ . '_filter_data'));

            // --- 6. DATAGRID ---
            $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
            $this->datagrid->style = 'width:100%';

            $col_id     = new TDataGridColumn('id_inscricao', 'ID', 'center', '5%');
            $col_nome   = new TDataGridColumn('usuario_name', 'Participante', 'left');
            $col_curso  = new TDataGridColumn('usuario_curso', 'Curso', 'left');
            $col_tipo   = new TDataGridColumn('tipo_participacao_descricao', 'Tipo', 'center');
            $col_status = new TDataGridColumn('status_label', 'Status', 'center');

            $col_id->setAction(new TAction([$this, 'onReload']), ['order' => 'id_inscricao']);
            $col_nome->setAction(new TAction([$this, 'onReload']), ['order' => 'usuario_name']);
            $col_curso->setAction(new TAction([$this, 'onReload']), ['order' => 'usuario_curso']);

            $col_status->setTransformer(function ($value) {
                $class = $value == 'Confirmado' ? 'success' : ($value == 'Pendente' ? 'warning' : 'danger');
                return "<span class='label label-{$class}'>{$value}</span>";
            });

            $col_entrada = new TDataGridColumn('ultima_entrada', 'Entrada', 'center');
            $col_entrada->setTransformer(fn($v) => !empty($v) ? date('d/m/Y H:i', strtotime($v)) : '-');

            $col_saida = new TDataGridColumn('ultima_saida', 'Saída', 'center');
            $col_saida->setTransformer(function($v, $object) {
                if ((int) $object->esta_presente === 1) {
                    return '<span class="label label-info">EM EVENTO</span>';
                }
                if (!empty($v)) {
                    return date('d/m/Y H:i', strtotime($v));
                }
                return '-';
            });

            $col_perm = new TDataGridColumn('total_minutos', 'Permanência', 'center');
            $col_perm->setTransformer(fn($v) => !empty($v) ? "{$v} min" : '0 min');

            $this->datagrid->addColumn($col_id);
            $this->datagrid->addColumn($col_nome);
            $this->datagrid->addColumn($col_curso);
            $this->datagrid->addColumn($col_tipo);
            $this->datagrid->addColumn($col_status);
            $this->datagrid->addColumn($col_entrada);
            $this->datagrid->addColumn($col_saida);
            $this->datagrid->addColumn($col_perm);
            $this->datagrid->addColumn(new TDataGridColumn('responsavel_nome', 'Responsável', 'center'));

            $this->datagrid->createModel();

            $panelGrid = new TPanelGroup("Listagem Detalhada: {$this->evento->titulo_evento}");
            $panelGrid->add($this->datagrid);

            $panelGrid->addHeaderActionLink('Exportar CSV', new TAction([$this, 'exportAsCSV']), 'fa:file-excel blue');

            $this->pageNavigation = new TPageNavigation;
            $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
            $panelGrid->addFooter($this->pageNavigation);

            $vbox = new TVBox;
            $vbox->style = 'width: 100%';
            $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
            $vbox->add($row_ind1);
            $vbox->add($row_ind2);
            $vbox->add($chartDiv);
            $vbox->add($rowResumos);
            $vbox->add($this->form);
            $vbox->add($panelGrid);
            $vbox->add($scriptChart);

            parent::add($vbox);

            $this->onReload($param);

        } catch (Exception $e) {
            if (TTransaction::getDatabase()) {
                TTransaction::rollback();
            }
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

    private function getFilterCriteria()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('id_evento', '=', TSession::getValue('eventoid')));

        $filterData = TSession::getValue(__CLASS__ . '_filter_data');
        if ($filterData) {
            if (!empty($filterData->usuario_name)) {
                $criteria->add(new TFilter('usuario_name', 'like', "%{$filterData->usuario_name}%"));
            }
            if (!empty($filterData->usuario_curso)) {
                $criteria->add(new TFilter('usuario_curso', 'like', "%{$filterData->usuario_curso}%"));
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
            if (!empty($filterData->dia_evento)) {
                $inicioDia = "{$filterData->dia_evento} 00:00:00";
                $fimDia    = "{$filterData->dia_evento} 23:59:59";
                $criteria->add(new TFilter('ultima_entrada', '>=', $inicioDia));
                $criteria->add(new TFilter('ultima_entrada', '<=', $fimDia));
            }
        }

        return $criteria;
    }

    public function onReload($param = null)
    {
        try {
            if (!$this->datagrid) {
                return;
            }

            TTransaction::open('teste');
            $this->datagrid->clear();

            $repository = new TRepository('ViewRelatorioParticipantes');
            $criteria = $this->getFilterCriteria();

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
    
            $id_evento  = TSession::getValue('eventoid');
            $repository = new TRepository('ViewRelatorioParticipantes');
            $criteria   = $this->getFilterCriteria();
    
            $criteria->setProperty('order', 'id_inscricao');
            $registros = $repository->load($criteria);
    
            if (!$registros) {
                new TMessage('info', 'Nenhum registro encontrado para exportar.');
                TTransaction::close();
                return;
            }
    
            $file = 'app/output/relatorio_gerencia_evento_' . $id_evento . '.csv';
            $handler = fopen($file, 'w');
            fprintf($handler, chr(0xEF) . chr(0xBB) . chr(0xBF));
            
            fputcsv($handler, ['ID', 'Participante', 'Curso', 'Tipo', 'Status', 'Entrada', 'Saída', 'Permanência (min)', 'Responsável'], ';', '"', "");
    
            foreach ($registros as $reg) {
                $saidaTxt = '-';
                if ((int) $reg->esta_presente === 1) {
                    $saidaTxt = 'EM EVENTO';
                } elseif (!empty($reg->ultima_saida)) {
                    $saidaTxt = date('d/m/Y H:i', strtotime($reg->ultima_saida));
                }
    
                $row = [
                    $reg->id_inscricao,
                    $reg->usuario_name,
                    $reg->usuario_curso ?? '-',
                    $reg->tipo_participacao_descricao ?? 'Indefinido',
                    $reg->status_label,
                    !empty($reg->ultima_entrada) ? date('d/m/Y H:i', strtotime($reg->ultima_entrada)) : '-',
                    $saidaTxt,
                    ($reg->total_minutos ?? 0) . ' min',
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