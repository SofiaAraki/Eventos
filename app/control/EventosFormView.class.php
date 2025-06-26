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
class EventosFormView extends TPage
{
    protected $form;
    use Adianti\Base\AdiantiStandardFormTrait;

    public function __construct()
    {
        parent::__construct();

        $this->setDatabase('test');
        $this->setActiveRecord('Eventos');

        $this->form = new BootstrapFormBuilder('form_Evento');
        $this->form->setFormTitle('Novo Evento');
        $this->form->setClientValidation(true);

        $this->createFields();
        $this->createActions();

        $vbox = new TVBox;
        $vbox->style = 'width:100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->form);

        parent::add($vbox);
    }

    /**
     * Criação e layout dos campos do formulário
     */
    private function createFields()
    {
        $id                = new THidden('id_evento');
        $titulo_evento     = new TEntry('titulo_evento');
        $local_evento      = new TEntry('local_evento');
        $status_evento     = new TCombo('status_evento');
        $data_inicio_evento= new TDateTime('data_inicio_evento');
        $data_fim_evento   = new TDateTime('data_fim_evento');
        $valor_evento      = new TEntry('valor_evento');
        $descricao_evento  = new TText('descricao_evento');
        $gerente_evento    = new THidden('gerente_evento');

        $status_evento->addItems(['1' => 'Ativo', '0' => 'Inativo']);
        $titulo_evento->addValidation('Título', new TRequiredValidator);

        $this->form->addFields([$id]);
        $this->form->addFields([new TLabel('Evento', 'red')], [$titulo_evento]);
        $this->form->addFields(
            [new TLabel('Local', 'red')], [$local_evento],
            [new TLabel('Status', 'red')], [$status_evento]
        );
        $this->form->addFields(
            [new TLabel('Data de Início', 'red')], [$data_inicio_evento],
            [new TLabel('Data de Fim', 'red')], [$data_fim_evento],
            [new TLabel('Valor da Inscrição', 'red')], [$valor_evento]
        );
        $this->form->addFields([new TLabel('Descrição', 'red')], [$descricao_evento]);
        $this->form->addFields([$gerente_evento]);
    }

    /**
     * Botões de ação
     */
    private function createActions()
    {
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addActionLink('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
        $this->form->addActionLink('Voltar', new TAction(['EventosView', 'onReload']), 'fa:table blue');
    }

    /**
     * Salva o evento
     */
    public function onSave()
    {
        try {
            TTransaction::open('test');

            $this->form->validate();

            $data = $this->form->getData();
            $data->gerente_evento = SystemUser::newFromLogin(TSession::getValue('login'))->id;

            // verifica se já existe o evento
            $is_update = !empty($data->id_evento); 

            // carrega objeto existente ou novo
            $evento = new Eventos($data->id_evento ?? null); 
            $evento->fromArray((array) $data);
            $evento->store();

            $this->form->setData($evento);

            TTransaction::close();

            // define a mensagem personalizada
            $mensagem = $is_update ? 'Evento atualizado com sucesso!' : 'Evento criado com sucesso!';
            new TMessage('info', $mensagem);

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
            $this->form->setData($this->form->getData());
        }
    }

    /**
     * Carrega o evento para edição
     */
    public function onEdit($param)
    {
        try {
            TTransaction::open('test');
            $evento = new Eventos($param['id_evento']);
            $this->form->setData($evento);
            TTransaction::close();
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}
