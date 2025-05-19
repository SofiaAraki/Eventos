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
        $id       = new THidden('id');
        $id->setEditable(FALSE);
        $titulo_evento     = new TEntry('titulo_evento');
        $data_inicio_evento     = new TDateTime('data_inicio_evento'); $data_inicio_evento->setMask('dd/mm/yyyy hh:ii');
        $data_fim_evento     = new TDateTime('data_fim_evento'); $data_fim_evento->setMask('dd/mm/yyyy hh:ii');
        $local_evento     = new TEntry('local_evento');
        $descricao_evento     = new TText('descricao_evento');
        $status_evento     = new TCombo('status_evento');
        $status_evento->addItems(['1' => 'Ativo', '0' => 'Inativo']);
        

        // add the form fields
        $this->form->addFields( [$id] );
        $this->form->addFields( [new TLabel('Evento', 'red')], [$titulo_evento] );
        $this->form->addFields( [new TLabel('Local', 'red')], [$local_evento] );
        $this->form->addFields( 
            [new TLabel('Data de Inicio', 'red')], [$data_inicio_evento],
            [new TLabel('Data de Fim', 'red')], [$data_fim_evento],
            [new TLabel('Status', 'red')], [$status_evento]
        );  
        $this->form->addFields( [new TLabel('Descrição', 'red')], [$descricao_evento] );
        
        $titulo_evento->addValidation( 'Titulo', new TRequiredValidator);
        
        // define the form action
        $this->form->addAction('Save', new TAction(array($this, 'onSave')), 'fa:save green');
        $this->form->addActionLink('Clear',  new TAction(array($this, 'onClear')), 'fa:eraser red');
        $this->form->addActionLink('Listing',  new TAction(array('EventosView', 'onReload')), 'fa:table blue');

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
            // open a transaction with database 
            TTransaction::open('test');
            
            $this->form->validate(); // run form validation
            
            $data = $this->form->getData(); // get form data as array
            
            $object = new Eventos;  // create an empty object
            $object->fromArray( (array) $data); // load the object with data
            $object->store(); // save the object
            
            // fill the form with the active record data
            $this->form->setData($object);
            
            TTransaction::close();  // close the transaction
            
            // shows the success message
            new TMessage('info', 'Evento criado com sucesso!');
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            $this->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback(); // undo all pending operations
        }
    }
    
}
