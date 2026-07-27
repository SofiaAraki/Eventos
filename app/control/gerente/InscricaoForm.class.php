<?php
class InscricaoForm extends TPage
{
    protected $form;

    use Adianti\Base\AdiantiStandardFormTrait;

    public function __construct()
    {
        parent::__construct();

        $this->setDatabase('teste');
        $this->setActiveRecord('Inscricao');

        $this->form = new BootstrapFormBuilder('form_Inscricao');
        $this->form->setFormTitle('Nova Inscrição');
        $this->form->setClientValidation(true);

        $id_inscricao = new TEntry('id_inscricao');
        $id_evento = new TDBUniqueSearch('id_evento', 'teste', 'Evento', 'id_evento', 'titulo_evento');
        $id_usuario = new TDBUniqueSearch('id_usuario', 'teste', 'SystemUser', 'id', 'name');
        $status_inscricao = new TCombo('status_inscricao');
        $status_inscricao->addItems([
            '1' => 'Confirmada',
            '0' => 'Pendente'
        ]);
        $tipo_participacao = new TDBCombo(
            'tipo_participacao',
            'teste',
            'TiposParticipacao',
            'codigo',
            'descricao'
        );

        $id_inscricao->setEditable(FALSE);

        $this->form->addFields([new TLabel('ID', 'red')], [$id_inscricao]);
        $this->form->addFields([new TLabel('Evento', 'red')], [$id_evento]);
        $this->form->addFields([new TLabel('Usuário', 'red')], [$id_usuario]);
        $this->form->addFields(
            [new TLabel('Status Inscrição', 'red')], [$status_inscricao],
            [new TLabel('Tipo Participação', 'red')], [$tipo_participacao]
        );

        $id_evento->addValidation('id_evento', new TRequiredValidator);
        $id_usuario->addValidation('id_usuario', new TRequiredValidator);
        $status_inscricao->addValidation('status_inscricao', new TRequiredValidator);
        $tipo_participacao->addValidation('tipo_participacao', new TRequiredValidator);

        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addActionLink('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
        $this->form->addActionLink('Voltar', new TAction(['InscricaoList', 'onReload']), 'fa:table blue');

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->form);

        parent::add($vbox);
    }

    public function onSave()
    {
        try {
            TTransaction::open('teste');
            $this->form->validate();
            $data = $this->form->getData();

            $is_update = !empty($data->id_inscricao);
            $inscricao = new Inscricao($data->id_inscricao ?? null);
            $inscricao->fromArray((array) $data);

            if (!$is_update) {
                $inscricao->data_inscricao = date('Y-m-d H:i:s');
            }

            $inscricao->store();
            TTransaction::close();
            
            $data->id_inscricao = $inscricao->id_inscricao;
            $this->form->setData($data);

            new TMessage('info', $is_update ? 'Inscrição atualizada com sucesso!' : 'Inscrição criada com sucesso!');

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
            $this->form->setData($this->form->getData());
        }
    }
}