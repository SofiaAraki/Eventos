<?php
class RelatorioEvento extends TPage
{
    protected $datagrid;
    protected $pageNavigation;

    protected $form;

    public function __construct($param = null)
    {
        parent::__construct();

        $id_evento = $param['id_evento'] ?? TSession::getValue('eventoid');
        TSession::setValue('eventoid', $id_evento);

        TTransaction::open('teste');
        $evento = new Evento($id_evento);

        $repository = new TRepository('ViewRelatorioParticipantes');
        $criteriaBase = new TCriteria;
        $criteriaBase->add(new TFilter('id_evento', '=', $evento->id_evento));

        $this->form = new BootstrapFormBuilder('form_filtro');
        $this->form->setFormTitle('Filtros');

        $criteriaDias = new TCriteria;
        $criteriaDias->add(new TFilter('id_evento', '=', $evento->id_evento));

        $registros = (new TRepository('ViewRelatorioParticipantes'))
            ->load($criteriaDias);
        
        $dias = ['' => 'Todos'];

        if ($registros)
        {
            foreach ($registros as $registro)
            {
                if (!empty($registro->data_entrada))
                {
                    $data = date('Y-m-d', strtotime($registro->data_entrada));
                    $dias[$data] = date('d/m/Y', strtotime($data));
                }
            }
        }
        
        ksort($dias);

        TTransaction::close();

        $dia_evento = new TCombo('dia_evento');
        $dia_evento->addItems($dias);
        $dia_evento->setSize('100%');

        $this->form->addFields([new TLabel('Dia do Evento')], [$dia_evento]);
        $this->form->addAction('Filtrar', new TAction([$this, 'onReload']), 'fa:search');

        $object = new stdClass;
        $object->dia_evento = TSession::getValue('filtro_dia_evento');

        $this->form->setData($object);

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width:100%';

        $this->datagrid->addColumn(new TDataGridColumn('id_inscricao', 'ID', 'center'));
        $this->datagrid->addColumn(new TDataGridColumn('usuario_name', 'Participante', 'left'));

        $col_dia = new TDataGridColumn('dia_evento', 'Dia', 'center');
        $col_dia->setTransformer(function($value){
            return date('d/m/Y', strtotime($value));
        });

        $col_entrada = new TDataGridColumn('data_entrada', 'Entrada', 'center');
        $col_entrada->setTransformer(function($value){
            return $value ? date('d/m/Y H:i', strtotime($value)) : '-';
        });

        $col_saida = new TDataGridColumn('data_saida', 'Saída', 'center');
        $col_saida->setTransformer(function($value){
            return $value
                ? date('d/m/Y H:i', strtotime($value))
                : '<span class="badge badge-warning">SEM SAÍDA</span>';
        });

        $col_perm = new TDataGridColumn('permanencia_min', 'Permanência', 'center');
        $col_perm->setTransformer(function($value)
        {
            $value = (int) $value;
            $horas = floor($value / 60);
            $min   = $value % 60;

            return sprintf('%02dh %02dmin', $horas, $min);
        });

        $this->datagrid->addColumn($col_dia);
        $this->datagrid->addColumn($col_entrada);
        $this->datagrid->addColumn($col_saida);
        $this->datagrid->addColumn($col_perm);
        
        $this->datagrid->addColumn(new TDataGridColumn('responsavel', 'Responsável', 'center'));
        $this->datagrid->createModel();

        $panel = new TPanelGroup("$evento->titulo_evento");
        $panel->add($this->datagrid);
        
        $panel->addHeaderActionLink('CSV', new TAction([$this, 'exportAsCSV']), 'fa:table blue');

        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
        $panel->addFooter($this->pageNavigation);

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add($this->form);
        $vbox->add($panel);

        parent::add($vbox);
        $this->onReload($param);
    }

    private function getCriteria($dia_evento = null)
    {
        $criteria = new TCriteria;

        $criteria->add(
            new TFilter(
                'id_evento',
                '=',
                TSession::getValue('eventoid')
            )
        );

        if (!empty($dia_evento))
        {
            $criteria->add(
                new TFilter(
                    'dia_evento',
                    '=',
                    $dia_evento
                )
            );
        }

        return $criteria;
    }

    public function onReload($param = null)
    {
        try
        {
            TTransaction::open('teste');

            $this->datagrid->clear();

            $repository = new TRepository('ViewRelatorioParticipantes');

            $dia_evento = $param['dia_evento']
                ?? TSession::getValue('filtro_dia_evento');

            $object = new stdClass;
            $object->dia_evento = $dia_evento;

            $this->form->setData($object);

            if (isset($param['dia_evento']))
            {
                TSession::setValue('filtro_dia_evento', $param['dia_evento']);
            }

            $criteria = $this->getCriteria($dia_evento);
            $criteria->setProperties($param);
            $criteria->setProperty('limit', 10);

            $registros = $repository->load($criteria);

            if ($registros)
            {
                foreach ($registros as $registro)
                {
                    $this->datagrid->addItem($registro);
                }
            }

            $criteria_count = clone $criteria;
            $criteria_count->resetProperties();

            $count = $repository->count($criteria_count);

            $this->pageNavigation->setCount($count);
            $this->pageNavigation->setProperties($param);

            TTransaction::close();
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function exportAsCSV($param)
    {
        try
        {
            TTransaction::open('teste');

            $repository = new TRepository('ViewRelatorioParticipantes');

            $dia_evento = TSession::getValue('filtro_dia_evento');

            $criteria = $this->getCriteria($dia_evento);

            $criteria->setProperty('order', 'data_entrada');
            $criteria->setProperty('direction', 'desc');

            $registros = $repository->load($criteria);

            if (!$registros)
            {
                TTransaction::close();
                new TMessage('info', 'Nenhum registro encontrado.');
                return;
            }

            $file = 'app/output/relatorio_presenca.csv';

            $handler = fopen($file, 'w');

            fprintf(
                $handler,
                chr(0xEF) . chr(0xBB) . chr(0xBF)
            );

            fputcsv(
                $handler,
                [
                    'Participante',
                    'Dia',
                    'Entrada',
                    'Saída',
                    'Permanência',
                    'Responsável'
                ],
                ';',
                '"',
                ""
            );

            foreach ($registros as $registro)
            {
                $permanenciaMin = (int) $registro->permanencia_min;
                $horas = floor($registro->permanencia_min / 60);
                $min   = $registro->permanencia_min % 60;

                $permanencia = sprintf(
                    '%02dh %02dmin',
                    $horas,
                    $min
                );

                fputcsv(
                    $handler,
                    [
                        $registro->participante,

                        $registro->dia_evento
                            ? date('d/m/Y', strtotime($registro->dia_evento))
                            : '-',

                        $registro->data_entrada
                            ? date('d/m/Y H:i', strtotime($registro->data_entrada))
                            : '-',

                        $registro->data_saida
                            ? date('d/m/Y H:i', strtotime($registro->data_saida))
                            : 'SEM SAÍDA',

                        $permanenciaMin,

                        $registro->responsavel
                    ],
                    ';',
                    '"',
                    ""
                );
            }

            fclose($handler);

            TTransaction::close();

            parent::openFile($file);
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}