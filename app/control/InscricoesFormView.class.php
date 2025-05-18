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
        $this->setActiveRecord('Inscricoes');   // defines the active record
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_Inscricoes');
        $this->form->setFormTitle('Nova Inscrição');
        $this->form->setClientValidation(true);
        
        // create the form fields
        $id       = new TEntry('id');
        $id->setEditable(FALSE);
        try {
            TTransaction::open('test');

            $id_evento = new TDBUniqueSearch('id_evento', 'test', 'Eventos', 'id_evento', 'titulo_evento');

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
        try {
            TTransaction::open('test');

            $id_usuario = new TDBUniqueSearch('id_usuario', 'test', 'SystemUser', 'id', 'name');

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
        $data_inscricao = new TDate('data_inscricao');
        $status_inscricao = new TCombo('status_inscricao');
        $status_inscricao->addItems([
            '1' => 'Confirmada',
            '0' => 'Pendente'
        ]);
        $status_inscricao->addValidation('Status', new TRequiredValidator);
        
        // add the form fields
        $this->form->addFields( [new TLabel('ID')], [$id] );
        $this->form->addFields( [new TLabel('Evento', 'red')], [$id_evento] );
        $this->form->addFields( [new TLabel('Usuário', 'red')], [$id_usuario] );
        $this->form->addFields( [new TLabel('Data Inscrição', 'red')], [$data_inscricao] );
        $this->form->addFields( [new TLabel('Status Inscrição', 'red')], [$status_inscricao] );
        
        $id_evento->addValidation( 'Evento', new TRequiredValidator);
        
        // define the form action
        $this->form->addAction('Save', new TAction(array($this, 'onSave')), 'fa:save green');
        $this->form->addActionLink('Clear',  new TAction(array($this, 'onClear')), 'fa:eraser red');
        $this->form->addActionLink('Listing',  new TAction(array('InscricoesView', 'onReload')), 'fa:table blue');

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
            
            $object = new Inscricoes;  // create an empty object
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
    
}
