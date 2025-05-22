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
    protected $form; // form
    
    // trait with onSave, onClear, onEdit
    use Adianti\Base\AdiantiStandardFormTrait;
    
    function __construct()
    {
        parent::__construct();
        
        $this->setDatabase('test');    // defines the database
        $this->setActiveRecord('Pagamentos');   // defines the active record
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_Pagamentos');
        $this->form->setFormTitle('Novo Pagamento');
        $this->form->setClientValidation(true);
        
        // create the form fields
        $id = new THidden('id_pagamento');
        $id->setEditable(FALSE);

        // Campo de seleção de inscrição (relacionado com evento e usuário)
        $id_inscricao = new TDBUniqueSearch('id_inscricao', 'test', 'Inscricoes', 'id_inscricao', 'id_inscricao');
        $id_inscricao->setMinLength(1);
        $id_inscricao->setMask('{id_inscricao}');
        $id_inscricao->setSize('100%');

        $data_pagamento = new TDate('data_pagamento');
        $status_pagamento = new TCombo('status_pagamento');
        $status_pagamento->addItems([
            '1' => 'Confirmado',
            '0' => 'Pendente'
        ]);
        
        // add the form fields
        $this->form->addFields( [$id] );
        $this->form->addFields([new TLabel('Inscrição', 'red')], [$id_inscricao]);
        $this->form->addFields( 
            [new TLabel('Data Pagamento', 'red')], [$data_pagamento],
            [new TLabel('Status Pagamento', 'red')], [$status_pagamento]
        );
        
        
        // define the form action
        $this->form->addAction('Salvar', new TAction(array($this, 'onSave')), 'fa:save green');
        $this->form->addActionLink('Limpar',  new TAction(array($this, 'onClear')), 'fa:eraser red');
        $this->form->addActionLink('Voltar',  new TAction(array('PagamentosView', 'onReload')), 'fa:table blue');

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
            
            $object = new Pagamentos;  // create an empty object
            $object->fromArray( (array) $data); // load the object with data
            $object->store(); // save the object
            
            // fill the form with the active record data
            $this->form->setData($object);
            
            TTransaction::close();  // close the transaction
            
            // shows the success message
            new TMessage('info', 'Pagamento confirmado!');
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
