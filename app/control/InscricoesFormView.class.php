<?php
/**
 * StandardFormView Registration
 *
 * @version    1.0
 * @package    samples
 * @subpackage tutor
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    https://adiantiframework.com.br/license-tutor
 */
class InscricoesFormView extends TPage
{
    protected $form;

    use Adianti\Base\AdiantiStandardFormTrait;

    public function __construct()
    {
        parent::__construct();

        $this->setDatabase('test');
        $this->setActiveRecord('Inscricoes');

        $this->form = new BootstrapFormBuilder('form_Inscricoes');
        $this->form->setFormTitle('Nova Inscrição');
        $this->form->setClientValidation(true);

        $this->createFormFields();
        $this->addFormActions();

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->form);

        parent::add($vbox);
    }

    private function createFormFields()
    {
        $id = new THidden('id_inscricao');
        $id->setEditable(false);

        $id_evento = $this->createDBUniqueSearch('id_evento', 'Eventos', 'id_evento', 'titulo_evento');
        $id_usuario = $this->createDBUniqueSearch('id_usuario', 'SystemUser', 'id', 'name');

        $data_inscricao = new TDate('data_inscricao');

        $status_inscricao = new TCombo('status_inscricao');
        $status_inscricao->addItems([
            '1' => 'Confirmada',
            '0' => 'Pendente'
        ]);

        $tipo_participacao = new TCombo('tipo_participacao');
        $tipo_participacao->addItems([
            'aluno' => 'Aluno',
            'palestrante' => 'Palestrante',
            'banca' => 'Banca',
            'orientador' => 'Orientador',
            'autor' => 'Autor'
        ]);

        // Adiciona os campos ao formulário
        $this->form->addFields([$id]);
        $this->form->addFields([new TLabel('Evento', 'red')], [$id_evento]);
        $this->form->addFields([new TLabel('Usuário', 'red')], [$id_usuario]);
        $this->form->addFields(
            [new TLabel('Data Inscrição', 'red')], [$data_inscricao],
            [new TLabel('Status Inscrição', 'red')], [$status_inscricao],
            [new TLabel('Tipo Participação', 'red')], [$tipo_participacao]
        );
    }

    private function createDBUniqueSearch($field, $model, $key, $label)
    {
        try {
            TTransaction::open('test');
            $search = new TDBUniqueSearch($field, 'test', $model, $key, $label);
            TTransaction::close();
            return $search;
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            return new TEntry($field); // fallback input
        }
    }

    private function addFormActions()
    {
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addActionLink('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
        $this->form->addActionLink('Voltar', new TAction(['InscricoesView', 'onReload']), 'fa:table blue');
    }

    public function onSave()
    {
        try {
            TTransaction::open('test');

            $this->form->validate();
            $data = $this->form->getData();

            $inscricao = new Inscricoes;
            $inscricao->fromArray((array) $data);
            $inscricao->store();

            $pagamento = new Pagamentos;
            $pagamento->id_inscricao = $inscricao->id_inscricao;
            $pagamento->status_pagamento = '0';
            $pagamento->data_pagamento = null;
            $pagamento->store();

            $this->form->setData($inscricao);

            TTransaction::close();

            new TMessage('info', 'Inscrição criada com sucesso!');
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            $this->form->setData($this->form->getData());
            TTransaction::rollback();
        }
    }

    public function onClear()
    {
        $this->form->clear(true);
    }
}

