<?php
class CertificadoFormCoordenador extends TPage
{
    protected $form;
    protected $datagrid;

    public function __construct($param = null)
    {
        parent::__construct();

        $id_evento = $param['id_evento'] ?? TSession::getValue('coordenador_id_evento');
        TSession::setValue('coordenador_id_evento', $id_evento);

        $this->form = new BootstrapFormBuilder('form_solicitacao_certificado');
        $this->form->setFormTitle('Gerenciar Certificados do Evento');
        $this->form->setClientValidation(true);

        $id_certificado              = new THidden('id_certificado');
        $id_evento_field             = new THidden('id_evento');
        $titulo_certificado         = new TEntry('titulo_certificado');
        $carga_horaria_certificado   = new TEntry('carga_horaria_certificado');
        $presenca_minima_certificado = new TEntry('presenca_minima_certificado');
        $bg_frente_certificado       = new TCombo('bg_frente_certificado');
        $tipo_participacao           = new TDBCombo('tipo_participacao', 'teste', 'TiposParticipacao', 'codigo', 'descricao');

        $id_evento_field->setValue($id_evento);
        $carga_horaria_certificado->setValue(0);
        $presenca_minima_certificado->setValue(0);
        $bg_frente_certificado->setValue('fafram.png');

        $bg_frente_certificado->addItems([
            'fafram.png'   => 'Modelo FAFRAM',
            'fundacao.png' => 'Modelo FE',
            'gegrao.png'   => 'Modelo GEGRAO',
            'gecaf.png'    => 'Modelo GECAF',
        ]);

        $this->form->addFields([$id_certificado], [$id_evento_field]);
        $this->form->addFields([new TLabel('Nome do Certificado', 'red')], [$titulo_certificado]);
        $this->form->addFields(
            [new TLabel('Carga Horária (hs)', 'red')], [$carga_horaria_certificado],
            [new TLabel('Presença Mínima (min)', 'red')], [$presenca_minima_certificado]
        );
        $this->form->addFields(
            [new TLabel('Modelo de Fundo', 'red')], [$bg_frente_certificado],
            [new TLabel('Tipo de Participação', 'red')], [$tipo_participacao]
        );

        $titulo_certificado->addValidation('Nome do Certificado', new TRequiredValidator);
        $carga_horaria_certificado->addValidation('Carga Horária', new TNumericValidator);
        $presenca_minima_certificado->addValidation('Presença Mínima', new TNumericValidator);
        $bg_frente_certificado->addValidation('Modelo de Fundo', new TRequiredValidator);
        $tipo_participacao->addValidation('Tipo de Participação', new TRequiredValidator);

        $this->form->addAction('Salvar Certificado', new TAction([$this, 'onSave']), 'far:check-circle green');
        $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
        $this->form->addActionLink('Voltar para Eventos', new TAction(['SolicitacaoListCoordenador', 'onReload']), 'fa:arrow-left orange');

        // Datagrid de certificados existentes para o evento
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%; margin-top: 20px;';

        $col_id     = new TDataGridColumn('id_certificado', 'ID', 'center', '10%');
        $col_titulo = new TDataGridColumn('titulo_certificado', 'Título', 'left', '40%');
        $col_tipo   = new TDataGridColumn('tipo_participacao', 'Tipo Participação', 'center', '25%');
        $col_ch     = new TDataGridColumn('carga_horaria_certificado', 'CH (hs)', 'center', '25%');

        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_titulo);
        $this->datagrid->addColumn($col_tipo);
        $this->datagrid->addColumn($col_ch);

        $action_edit   = new TDataGridAction([$this, 'onEdit'], ['key' => '{id_certificado}']);
        $action_delete = new TDataGridAction([$this, 'onDelete'], ['key' => '{id_certificado}']);

        $this->datagrid->addAction($action_edit, 'Editar', 'far:edit blue');
        $this->datagrid->addAction($action_delete, 'Excluir', 'far:trash-alt red');

        $this->datagrid->createModel();

        $panel = new TPanelGroup('Certificados Cadastrados');
        $panel->add($this->datagrid);

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', 'SolicitacaoListCoordenador'));
        $vbox->add($this->form);
        $vbox->add($panel);

        parent::add($vbox);
    }

    public function onSave()
    {
        try
        {
            $this->form->validate();
            $data = $this->form->getData();

            TTransaction::open('teste');

            $bgPermitidos = ['fundacao.png', 'fafram.png', 'gegrao.png', 'gecaf.png'];
            if (!in_array($data->bg_frente_certificado, $bgPermitidos, true)) {
                throw new Exception('Modelo de imagem de fundo inválido.');
            }

            $certificado = new Certificado($data->id_certificado ?? null);
            $certificado->fromArray((array) $data);
            $certificado->store();

            TTransaction::close();

            new TMessage('info', 'Certificado salvo com sucesso!');
            $this->onClear();
            $this->onReload();
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
            $this->form->setData($this->form->getData());
        }
    }

    public function onReload()
    {
        try {
            TTransaction::open('teste');

            $id_evento = TSession::getValue('coordenador_id_evento');
            $this->datagrid->clear();

            if ($id_evento) {
                $repository = new TRepository('Certificado');
                $criteria   = new TCriteria;
                $criteria->add(new TFilter('id_evento', '=', $id_evento));

                $certificados = $repository->load($criteria);
                if ($certificados) {
                    foreach ($certificados as $cert) {
                        $this->datagrid->addItem($cert);
                    }
                }
            }

            TTransaction::close();
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onEdit($param)
    {
        try {
            if (isset($param['key'])) {
                TTransaction::open('teste');
                $cert = new Certificado($param['key']);
                $this->form->setData($cert);
                TTransaction::close();
            }
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onDelete($param)
    {
        try {
            if (isset($param['key'])) {
                TTransaction::open('teste');
                $cert = new Certificado($param['key']);
                $cert->delete();
                TTransaction::close();

                new TMessage('info', 'Certificado removido.');
                $this->onReload();
            }
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onClear()
    {
        $this->form->clear(true);
        $obj = new stdClass;
        $obj->id_evento = TSession::getValue('coordenador_id_evento');
        $obj->carga_horaria_certificado = 0;
        $obj->presenca_minima_certificado = 0;
        $obj->bg_frente_certificado = 'fafram.png';
        $this->form->setData($obj);
    }

    public function show()
    {
        $this->onReload();
        parent::show();
    }
}