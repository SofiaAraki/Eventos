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
    
    /**
     * Class constructor
     * Creates the page and the registration form
     */
    function __construct()
    {
        parent::__construct();
        
        $this->setDatabase('test');    // defines the database
        $this->setActiveRecord('Eventos');   // defines the active record
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_Evento');
        $this->form->setFormTitle(_t('Novo Evento'));
        $this->form->setClientValidation(true);
        
        // create the form fields
        $id       = new TEntry('id');
        $id->setEditable(FALSE);
        $titulo_evento     = new TEntry('titulo_evento');

        // add the form fields
        $this->form->addFields( [new TLabel('ID')], [$id] );
        $this->form->addFields( [new TLabel('Titulo', 'red')], [$titulo_evento] );
        
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
            // open a transaction with database 'samples'
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
            new TMessage('info', 'Record saved');
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            $this->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback(); // undo all pending operations
        }
    }
    
    /**
     * Clear form
     */
    public function onClear()
    {
        $this->form->clear( TRUE );
    }
    
    /**
     * method onEdit()
     * Executed whenever the user clicks at the edit button da datagrid
     */
    
}
