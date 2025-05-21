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
    protected $form; // form
    
    // trait with onSave, onClear, onEdit
    use Adianti\Base\AdiantiStandardFormTrait;
    
    function __construct()
    {
        parent::__construct();
        
        $this->setDatabase('test');    // defines the database
        $this->setActiveRecord('Eventos');   // defines the active record
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_Evento');
        $this->form->setFormTitle('Novo Evento');
        $this->form->setClientValidation(true);
        
        // create the form fields
        $id       = new THidden('id_evento');
        $id->setEditable(FALSE);
        $titulo_evento     = new TEntry('titulo_evento');
        $data_inicio_evento     = new TDateTime('data_inicio_evento'); //$data_inicio_evento->setMask('dd/mm/yyyy hh:ii');
        $data_fim_evento     = new TDateTime('data_fim_evento'); //$data_fim_evento->setMask('dd/mm/yyyy hh:ii');
        $local_evento     = new TEntry('local_evento');
        $descricao_evento     = new TText('descricao_evento');
        $status_evento     = new TCombo('status_evento');
        $status_evento->addItems(['1' => 'Ativo', '0' => 'Inativo']);
        $gerente_evento     = new THidden('gerente_evento');
        $valor_evento = new TEntry('valor_evento');
        

        // add the form fields
        $this->form->addFields( [$id] );
        $this->form->addFields( [new TLabel('Evento', 'red')], [$titulo_evento] );
        $this->form->addFields( 
            [new TLabel('Local', 'red')], [$local_evento],
            [new TLabel('Status', 'red')], [$status_evento]
        );
        $this->form->addFields( 
            [new TLabel('Data de Inicio', 'red')], [$data_inicio_evento],
            [new TLabel('Data de Fim', 'red')], [$data_fim_evento],
            [new TLabel('Valor da Inscrição', 'red')], [$valor_evento]
        );  
        $this->form->addFields( [new TLabel('Descrição', 'red')], [$descricao_evento] );
        $this->form->addFields( [$gerente_evento] );
        
        $titulo_evento->addValidation( 'Titulo', new TRequiredValidator);
        
        // define the form action
        $this->form->addAction('Salvar', new TAction(array($this, 'onSave')), 'fa:save green');
        $this->form->addActionLink('Limpar',  new TAction(array($this, 'onClear')), 'fa:eraser red');
        $this->form->addActionLink('Voltar',  new TAction(array('EventosView', 'onReload')), 'fa:table blue');

        // wrap the page content using vertical box
        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->form);

        parent::add($vbox);
    }

    function onSave()
    {
        try
        {
            TTransaction::open('test');

            $login = TSession::getValue('login'); 
            $user = SystemUser::newFromLogin($login); 
            $gerente_id = $user->id; 
            
            $this->form->validate();
            $data = $this->form->getData();

            $data->gerente_evento = $gerente_id;

            // usa o ID para carregar ou criar
            $object = new Eventos($data->id_evento ?? null); // aqui é importante
            $object->fromArray((array) $data);
            $object->store();
            
            $this->form->setData($object);
            TTransaction::close();

            new TMessage('info', 'Evento salvo com sucesso!');
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            $this->form->setData($this->form->getData());
            TTransaction::rollback();
        }
    }

    public function onEdit($param)
    {
        try {
            TTransaction::open('test');
            $object = new Eventos($param['id_evento']);
            $this->form->setData($object);
            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    
}
