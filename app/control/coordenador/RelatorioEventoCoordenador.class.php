<?php

class RelatorioEventoCoordenador extends TPage
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

            $countTodos     = ViewRelatorioParticipantes::where('id_evento', '=', $id_evento)->count();
            $countPresentes = ViewRelatorioParticipantes::where('id_evento', '=', $id_evento)->where('esta_presente', '>', 0)->count();
            $countSairam    = ViewRelatorioParticipantes::where('id_evento', '=', $id_evento)->where('ja_saiu', '>', 0)->count();
            
            $countCompareceram = $countPresentes + $countSairam;
            $taxaPresenca      = $countTodos > 0 ? round(($countCompareceram / $countTodos) * 100, 1) : 0;

            $registrosDias = ViewRelatorioParticipantes::where('id_evento', '=', $id_evento)->load();
            $dias = ['' => 'Todos os Dias'];
            if ($registrosDias) {
                foreach ($registrosDias as $regDia) {
                    $dataValida = $regDia->dia_evento ?? $regDia->data_entrada ?? $regDia->ultima_entrada;
                    if (!empty($dataValida)) {
                        $data = date('Y-m-d', strtotime($dataValida));
                        $dias[$data] = date('d/m/Y', strtotime($data));
                    }
                }
            }
            ksort($dias);

            TTransaction::close();

            $row_indicators = new TElement('div');
            $row_indicators->class = 'row';
            $row_indicators->style = 'margin-bottom: 20px;';

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

            $this->form = new BootstrapFormBuilder('form_busca_coordenador');
            $this->form->setFormTitle('Filtros do Relatório');

            $usuario_name = new TEntry('usuario_name');
            $usuario_name->setSize('100%');

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

            $this->form->addFields([new TLabel('Participante')], [$usuario_name]);
            $this->form->addFields([new TLabel('Status')], [$status_filtro], [new TLabel('Dia do Evento')], [$dia_evento]);

            $this->form->addAction('Filtrar', new TAction([$this, 'onSearch']), 'fa:search blue');
            $this->form->addAction('Limpar', new TAction([$this, 'clearFilters']), 'fa:eraser red');

            $this->form->setData(TSession::getValue(__CLASS__ . '_filter_data'));

            $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
            $this->datagrid->style = 'width:100%';

            $col_id     = new TDataGridColumn('id_inscricao', 'ID', 'center', '5%');
            $col_nome   = new TDataGridColumn('usuario_name', 'Participante', 'left');
            $col_status = new TDataGridColumn('status_label', 'Status', 'center');

            $col_id->setAction(new TAction([$this, 'onReload']), ['order' => 'id_inscricao']);
            $col_nome->setAction(new TAction([$this, 'onReload']), ['order' => 'usuario_name']);

            // Dropdown interativo para alteração direta do status
            $col_status->setTransformer(function ($value, $object, $row, $cell) {
                $cell->setProperty('onclick', 'event.stopPropagation();');

                $isConfirmado = ($value == 'Confirmado');
                $label = $isConfirmado ? 'Confirmado' : 'Pendente';
                $color = $isConfirmado ? '#28a745' : '#dc3545';

                $dropdown = new TDropDown($label, '');
                $dropdown->getButton()->style .= ';color:white;border-radius:5px;background:'.$color;

                $novoStatus = $isConfirmado ? 0 : 1;
                $labelAcao  = $isConfirmado ? 'Marcar como Pendente' : 'Confirmar Inscrição';

                $params = [
                    'id_inscricao' => $object->id_inscricao,
                    'novo_status'  => $novoStatus,
                    'offset'       => $_REQUEST['offset'] ?? 0,
                    'limit'        => $_REQUEST['limit'] ?? 10,
                    'page'         => $_REQUEST['page'] ?? 1,
                    'first_page'   => $_REQUEST['first_page'] ?? 1
                ];

                $dropdown->addAction($labelAcao, new TAction([$this, 'onChangeStatus'], $params));

                return $dropdown;
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

            $col_perm = new TDataGridColumn('permanencia_total', 'Permanência', 'center');

            $this->datagrid->addColumn($col_id);
            $this->datagrid->addColumn($col_nome);
            $this->datagrid->addColumn($col_status);
            $this->datagrid->addColumn($col_entrada);
            $this->datagrid->addColumn($col_saida);
            $this->datagrid->addColumn($col_perm);
            $this->datagrid->addColumn(new TDataGridColumn('responsavel_nome', 'Responsável', 'center'));

            $action_edit = new TDataGridAction([$this, 'onOpenEditInscricaoForm'], ['id_inscricao' => '{id_inscricao}']);
            $this->datagrid->addAction($action_edit, 'Editar Inscrição', 'far:edit blue');

            $action_delete = new TDataGridAction([$this, 'onDeleteInscricao'], ['id_inscricao' => '{id_inscricao}']);
            $this->datagrid->addAction($action_delete, 'Remover Inscrição', 'far:trash-alt red');

            $this->datagrid->createModel();

            // Criar navegação antes do onReload
            $this->pageNavigation = new TPageNavigation;
            $this->pageNavigation->setAction(new TAction([$this, 'onReload']));

            $panel = new TPanelGroup("Relatório: {$this->evento->titulo_evento}");
            $panel->add($this->datagrid);

            $panel->addHeaderActionLink('Inscrever Participante', new TAction([$this, 'onOpenInscricaoForm']), 'fa:user-plus green');
            $panel->addHeaderActionLink('Exportar CSV', new TAction([$this, 'exportAsCSV']), 'fa:file-excel blue');
            $panel->addFooter($this->pageNavigation);

            $vbox = new TVBox;
            $vbox->style = 'width: 100%';
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

    private function getFilterCriteria()
    {
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

    public function onChangeStatus($param)
    {
        try {
            TTransaction::open('teste');
            
            $id_inscricao = $param['id_inscricao'] ?? null;
            $novo_status  = $param['novo_status'] ?? null;

            if ($id_inscricao !== null && $novo_status !== null) {
                $inscricao = new Inscricao($id_inscricao);
                $inscricao->status_inscricao = (int) $novo_status;
                $inscricao->store();

                new TToast('Status da inscrição atualizado com sucesso!', 'info');
            }

            TTransaction::close();
            $this->onReload($param);

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onOpenEditInscricaoForm($param)
    {
        try {
            TTransaction::open('teste');
            
            $id_inscricao = $param['id_inscricao'] ?? null;
            if (empty($id_inscricao)) {
                throw new Exception('Inscrição não informada.');
            }

            $inscricao = new Inscricao($id_inscricao);

            $window = TWindow::create('Editar Inscrição', 0.5, null);
            $form = new BootstrapFormBuilder('form_edit_inscricao');

            $id_inscricao_field = new THidden('id_inscricao');
            
            $status_inscricao = new TCombo('status_inscricao');
            $status_inscricao->addItems([
                '1' => 'Confirmado',
                '0' => 'Pendente'
            ]);
            $status_inscricao->setSize('100%');

            $tipo_participacao = new TDBCombo('tipo_participacao', 'teste', 'TiposParticipacao', 'codigo', 'descricao');
            $tipo_participacao->setSize('100%');

            $form->addFields([$id_inscricao_field]);
            $form->addFields([new TLabel('Status')], [$status_inscricao]);
            $form->addFields([new TLabel('Tipo de Participação')], [$tipo_participacao]);

            $form->setData($inscricao);

            $form->addAction('Salvar Alterações', new TAction([$this, 'onUpdateInscricao']), 'fa:save green');

            $window->add($form);
            $window->show();

            TTransaction::close();
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onUpdateInscricao($param)
    {
        try {
            TTransaction::open('teste');

            $id_inscricao = $param['id_inscricao'] ?? null;
            if (empty($id_inscricao)) {
                throw new Exception('ID da Inscrição não encontrado.');
            }

            $inscricao = new Inscricao($id_inscricao);
            $inscricao->status_inscricao  = $param['status_inscricao'] ?? $inscricao->status_inscricao;
            $inscricao->tipo_participacao = $param['tipo_participacao'] ?? $inscricao->tipo_participacao;
            $inscricao->store();

            TTransaction::close();

            new TMessage('info', 'Inscrição atualizada com sucesso!', new TAction([$this, 'onReload']));
            TWindow::closeWindow();
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onOpenInscricaoForm($param)
    {
        try {
            $window = TWindow::create('Inscrever Novo Participante', 0.5, null);

            $form = new BootstrapFormBuilder('form_nova_inscricao');

            $id_usuario = new TDBUniqueSearch('id_usuario', 'teste', 'SystemUser', 'id', 'name');
            $id_usuario->setSize('100%');

            $tipo_participacao = new TDBCombo('tipo_participacao', 'teste', 'TiposParticipacao', 'codigo', 'descricao');
            $tipo_participacao->setSize('100%');

            $form->addFields([new TLabel('Usuário', 'red')], [$id_usuario]);
            $form->addFields([new TLabel('Tipo de Participação', 'red')], [$tipo_participacao]);

            $id_usuario->addValidation('Usuário', new TRequiredValidator);
            $tipo_participacao->addValidation('Tipo de Participação', new TRequiredValidator);

            $form->addAction('Salvar Inscrição', new TAction([__CLASS__, 'onSaveInscricao']), 'fa:save green');

            $window->add($form);
            $window->show();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onSaveInscricao($param)
    {
        try {
            TTransaction::open('teste');

            $id_evento         = TSession::getValue('eventoid');
            $id_usuario        = $param['id_usuario'] ?? null;
            $tipo_participacao = $param['tipo_participacao'] ?? null;

            if (empty($id_evento) || empty($id_usuario) || empty($tipo_participacao)) {
                throw new Exception('Preencha todos os campos obrigatórios.');
            }

            $repository = new TRepository('Inscricao');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('id_evento', '=', $id_evento));
            $criteria->add(new TFilter('id_usuario', '=', $id_usuario));

            if ($repository->count($criteria) > 0) {
                throw new Exception('Este participante já está inscrito neste evento.');
            }

            $inscricao = new Inscricao;
            $inscricao->id_evento         = (int) $id_evento;
            $inscricao->id_usuario        = (int) $id_usuario;
            $inscricao->tipo_participacao = $tipo_participacao;
            $inscricao->status_inscricao  = 1;
            $inscricao->data_inscricao    = date('Y-m-d H:i:s');
            $inscricao->store();

            TTransaction::close();

            new TMessage('info', 'Participante inscrito com sucesso!', new TAction([__CLASS__, 'onReload']));
            TWindow::closeWindow();
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onDeleteInscricao($param)
    {
        $action = new TAction([$this, 'DeleteInscricaoConfirmada']);
        $action->setParameters($param);

        new TQuestion('Tem certeza que deseja remover esta inscrição do evento?', $action);
    }

    public function DeleteInscricaoConfirmada($param)
    {
        try {
            TTransaction::open('teste');

            $id_inscricao = $param['id_inscricao'] ?? null;
            if ($id_inscricao) {
                $inscricao = new Inscricao($id_inscricao);
                $inscricao->delete();
            }

            TTransaction::close();

            new TMessage('info', 'Inscrição removida com sucesso!');
            $this->onReload();
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

            $file = 'app/output/relatorio_coordenador_evento_' . $id_evento . '.csv';
            $handler = fopen($file, 'w');
            fprintf($handler, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handler, ['ID', 'Participante', 'Status', 'Entrada', 'Saída', 'Permanência', 'Responsável'], ';', '"', "");

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
                    $reg->status_label,
                    !empty($reg->ultima_entrada) ? date('d/m/Y H:i', strtotime($reg->ultima_entrada)) : '-',
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