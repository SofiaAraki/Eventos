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
        $id       = new THidden('id_inscricao');
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
        
        // add the form fields
        $this->form->addFields( [$id] );
        $this->form->addFields( [new TLabel('Evento', 'red')], [$id_evento] );
        $this->form->addFields( [new TLabel('Usuário', 'red')], [$id_usuario] );
        $this->form->addFields( 
            [new TLabel('Data Inscrição', 'red')], [$data_inscricao],
            [new TLabel('Status Inscrição', 'red')], [$status_inscricao]
        );
        
        
        // define the form action
        $this->form->addAction('Salvar', new TAction(array($this, 'onSave')), 'fa:save green');
        $this->form->addActionLink('Limpar',  new TAction(array($this, 'onClear')), 'fa:eraser red');
        $this->form->addActionLink('Voltar',  new TAction(array('InscricoesView', 'onReload')), 'fa:table blue');

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
            
            $this->form->validate();
            $data = $this->form->getData();

            // Cria a inscrição
            $inscricao = new Inscricoes;
            $inscricao->fromArray( (array) $data );
            $inscricao->store(); // grava e gera o ID da inscrição

            // Cria o pagamento vinculado
            $pagamento = new Pagamentos;
            $pagamento->id_inscricao = $inscricao->id_inscricao;
            $pagamento->status_pagamento = '0'; // Pendente por padrão
            $pagamento->data_pagamento = null;  // ou date('Y-m-d') se quiser registrar a tentativa
            $pagamento->store();

            // Preenche o formulário com os dados atualizados
            $this->form->setData($inscricao);

            TTransaction::close();
            
            new TMessage('info', 'Inscrição criada com sucesso!');
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            $this->form->setData( $this->form->getData() );
            TTransaction::rollback();
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
