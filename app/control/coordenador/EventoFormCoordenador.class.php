<?php
class EventoFormCoordenador extends TPage
{
    protected $form;
    
    use Adianti\Base\AdiantiStandardFormTrait;

    public function __construct()
    {
        parent::__construct();

        $this->setDatabase('teste');
        $this->setActiveRecord('Evento');

        $this->form = new BootstrapFormBuilder('form_Evento');
        $this->form->setFormTitle('Novo Evento');
        $this->form->setClientValidation(true);

        $id = new TEntry('id_evento');
        $titulo_evento = new TEntry('titulo_evento');
        $local_evento = new TEntry('local_evento');
        $data_inicio_evento = new TDateTime('data_inicio_evento');
        $data_fim_evento = new TDateTime('data_fim_evento');
        $descricao_evento = new TText('descricao_evento');
        $monitor_evento = new TDBMultiSearch('monitor_evento', 'teste', 'SystemUser', 'id', 'name');

        $id->setEditable(FALSE);

        $this->form->addFields(
            [new TLabel('ID', 'red')], [$id],
        );
        $this->form->addFields(
            [new TLabel('Evento', 'red')], [$titulo_evento]
        );
        $this->form->addFields(
            [new TLabel('Local', 'red')], [$local_evento]            
        );
        $this->form->addFields(
            [new TLabel('Data de Início', 'red')], [$data_inicio_evento],
            [new TLabel('Data de Fim', 'red')], [$data_fim_evento],
        );
        $this->form->addFields([new TLabel('Descrição/Instrução', 'red')], [$descricao_evento]);
        $this->form->addFields([new TLabel('Monitores', 'red')], [$monitor_evento]);

        $titulo_evento->addValidation('Título', new TRequiredValidator);

        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addActionLink('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
        $this->form->addActionLink('Voltar', new TAction(['SolicitacaoEventoList', 'onReload']), 'fa:table blue');

        $vbox = new TVBox;
        $vbox->style = 'width:100%';
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

            if (!empty($data->data_fim_evento) && !empty($data->data_inicio_evento)) {
                if (new DateTime($data->data_fim_evento) < new DateTime($data->data_inicio_evento)) {
                    throw new Exception('A data de fim não pode ser anterior à data de início.');
                }
            }

            $is_update = !empty($data->id_evento);
            $evento = new Evento($data->id_evento ?? null);
            $evento->fromArray((array) $data);
            
            $evento->gerente_evento = TSession::getValue('userid');
            $evento->store();

            $this->syncMonitores($evento, (array) ($data->monitor_evento ?? []));

            $this->form->setData($evento);

            TTransaction::close();

            new TMessage('info', $is_update ? 'Atualizado com sucesso!' : 'Criado com sucesso!');

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
            $this->form->setData($this->form->getData());
        }
    }

    private function syncMonitores(Evento $evento, array $ids): void
    {
        $repo = new TRepository('Monitor');

        $criteria = new TCriteria;
        $criteria->add(new TFilter('id_evento', '=', $evento->id_evento));

        $repo->delete($criteria);

        $ids = array_unique($ids);

        foreach ($ids as $userId)
        {
            if (!empty($userId))
            {
                $m = new Monitor;
                $m->id_evento = $evento->id_evento;
                $m->id_usuario = $userId;
                $m->store();
            }
        }
    }
}