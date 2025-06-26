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
class PagamentosFormView extends TPage
{
    protected $form;

    use Adianti\Base\AdiantiStandardFormTrait;

    public function __construct()
    {
        parent::__construct();

        $this->setDatabase('test');
        $this->setActiveRecord('Pagamentos');

        $this->createForm();

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->form);

        parent::add($vbox);
    }

    private function createForm()
    {
        $this->form = new BootstrapFormBuilder('form_Pagamentos');
        $this->form->setFormTitle('Novo Pagamento');
        $this->form->setClientValidation(true);

        $id = new THidden('id_pagamento');

        $id_evento = new TDBUniqueSearch('id_evento', 'test', 'Eventos', 'id_evento', 'titulo_evento');
        $id_evento->setMinLength(1);
        $id_evento->setSize('100%');

        $id_usuario = new TDBUniqueSearch('id_usuario', 'test', 'SystemUser', 'id', 'name');
        $id_usuario->setMinLength(1);
        $id_usuario->setSize('100%');

        $data_pagamento = new TDate('data_pagamento');
        $status_pagamento = new TCombo('status_pagamento');
        $status_pagamento->addItems([
            '1' => 'Confirmado',
            '0' => 'Pendente'
        ]);
        $status_pagamento->setSize('100%');

        $this->form->addFields([$id]);
        $this->form->addFields([new TLabel('Evento', 'red')], [$id_evento]);
        $this->form->addFields([new TLabel('Usuário', 'red')], [$id_usuario]);
        $this->form->addFields(
            [new TLabel('Data do Pagamento', 'red')], [$data_pagamento],
            [new TLabel('Status do Pagamento', 'red')], [$status_pagamento]
        );

        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addActionLink('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
        $this->form->addActionLink('Voltar', new TAction(['PagamentosView', 'onReload']), 'fa:table blue');
    }

    public function onSave()
    {
        try {
            TTransaction::open('test');

            $this->form->validate();
            $data = $this->form->getData();

            // Verifica se há inscrição correspondente
            $inscricao = Inscricoes::where('id_evento', '=', $data->id_evento)
                                   ->where('id_usuario', '=', $data->id_usuario)
                                   ->first();

            if (!$inscricao) {
                throw new Exception('Nenhuma inscrição encontrada para este evento e usuário.');
            }

            // Verifica se já existe pagamento para essa inscrição
            $pagamento_existente = Pagamentos::where('id_inscricao', '=', $inscricao->id_inscricao)->first();

            if ($pagamento_existente) {
                throw new Exception('Este usuário já possui um pagamento registrado para este evento.');
            }

            // Cria o novo pagamento
            $pagamento = new Pagamentos;
            $pagamento->id_inscricao = $inscricao->id_inscricao;
            $pagamento->data_pagamento = $data->data_pagamento;
            $pagamento->status_pagamento = $data->status_pagamento;
            $pagamento->store();

            $this->form->setData($data);

            TTransaction::close();

            new TMessage('info', 'Pagamento registrado com sucesso!');
        } catch (Exception $e) {
            TTransaction::rollback();
            $this->form->setData($this->form->getData());
            new TMessage('error', $e->getMessage());
        }
    }

    public function onClear()
    {
        $this->form->clear(true);
    }

    public function onEdit($param)
    {
        try
        {
            if (isset($param['id_pagamento']))
            {
                TTransaction::open('test');
                
                $pagamento = new Pagamentos($param['id_pagamento']);

                // Pega dados relacionados
                $inscricao = new Inscricoes($pagamento->id_inscricao);
                
                $data = new stdClass;
                $data->id_pagamento = $pagamento->id_pagamento;
                $data->id_evento = $inscricao->id_evento;
                $data->id_usuario = $inscricao->id_usuario;
                $data->data_pagamento = $pagamento->data_pagamento;
                $data->status_pagamento = $pagamento->status_pagamento;

                $this->form->setData($data);

                TTransaction::close();
            }
            else
            {
                $this->form->clear(true);
            }
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

}
