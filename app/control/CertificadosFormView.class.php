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
// class CertificadosFormView extends TPage
// {
//     protected $form; // form
    
//     use Adianti\Base\AdiantiStandardFormTrait;
    
//     function __construct()
//     {
//         parent::__construct();
        
//         $this->setDatabase('test');          // define database
//         $this->setActiveRecord('Certificados');  // define Active Record
        
//         // cria o formulário
//         $this->form = new BootstrapFormBuilder('form_Certificados');
//         $this->form->setFormTitle('Novo Certificado');
//         $this->form->setClientValidation(true);
        
//         // campos
//         $id_certificado = new TEntry('id_certificado');
//         $id_certificado->setEditable(FALSE);
        
//         // Abrir transação para carregar campo relacional
//         TTransaction::open('test');
//         $id_evento = new TDBUniqueSearch('id_evento', 'test', 'Eventos', 'id_evento', 'titulo_evento');
//         TTransaction::close();
        
//         $titulo_certificado = new TEntry('titulo_certificado');
//         $data_emissao_certificado = new TDate('data_emissao_certificado');
//         $data_emissao_certificado->setMask('dd/mm/yyyy');
//         $data_emissao_certificado->setDatabaseMask('yyyy-mm-dd');
        
//         // adiciona campos no formulário
//         $this->form->addFields([new TLabel('ID')], [$id_certificado]);
//         $this->form->addFields([new TLabel('Evento', 'red')], [$id_evento]);
//         $this->form->addFields([new TLabel('Nome do Certificado', 'red')], [$titulo_certificado]);
//         $this->form->addFields([new TLabel('Data de Emissão', 'red')], [$data_emissao_certificado]);
        
//         // validação obrigatória
//         $id_evento->addValidation('Evento', new TRequiredValidator);
//         $titulo_certificado->addValidation('Nome do Certificado', new TRequiredValidator);
//         $data_emissao_certificado->addValidation('Data de Emissão', new TRequiredValidator);
        
//         // ações do formulário
//         $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
//         $this->form->addActionLink('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
//         $this->form->addActionLink('Listagem', new TAction(['CertificadosView', 'onReload']), 'fa:table blue');
        
//         // monta a página
//         $vbox = new TVBox;
//         $vbox->style = 'width: 100%';
//         $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
//         $vbox->add($this->form);
        
//         parent::add($vbox);
//     }
    
//     public function onSave()
//     {
//         try {
//             TTransaction::open('test');
//             $this->form->validate();
            
//             $data = $this->form->getData();
//             $object = new Certificados;
//             $object->fromArray((array) $data);
//             $object->store();
            
//             $this->form->setData($object);
            
//             TTransaction::close();
//             new TMessage('info', 'Registro salvo com sucesso!');
//         }
//         catch (Exception $e) {
//             new TMessage('error', $e->getMessage());
//             $this->form->setData($this->form->getData());
//             TTransaction::rollback();
//         }
//     }
    
//     public function onClear()
//     {
//         $this->form->clear(TRUE);
//     }
    
//     public function onEdit($param)
//     {
//         try {
//             if (isset($param['key'])) {
//                 TTransaction::open('test');
//                 $object = new Certificados($param['key']);
//                 $this->form->setData($object);
//                 TTransaction::close();
//             }
//         }
//         catch (Exception $e) {
//             new TMessage('error', $e->getMessage());
//             TTransaction::rollback();
//         }
//     }
// }

class CertificadosFormView extends TPage
{
    protected $form;
    use Adianti\Base\AdiantiStandardFormTrait;

    function __construct()
    {
        parent::__construct();

        $this->setDatabase('test');
        $this->setActiveRecord('Certificados');

        $this->form = new BootstrapFormBuilder('form_Certificados');
        $this->form->setFormTitle('Modelo de Certificado');
        $this->form->setClientValidation(true);

        $id_certificado = new TEntry('id_certificado');
        $id_certificado->setEditable(FALSE);

        $id_evento = new TDBUniqueSearch('id_evento', 'test', 'Eventos', 'id_evento', 'titulo_evento');
        $titulo_certificado = new TEntry('titulo_certificado');
        $descricao = new TText('descricao');
        $data_emissao = new TDate('data_emissao'); $carga_horaria_certificado = new TEntry('carga_horaria');
        $orientacao_pagina = new TCombo('orientacao_pagina');
        $orientacao_pagina->addItems(['portrait' => 'Retrato', 'landscape' => 'Paisagem']);

        $mostra_verso = new TCombo('mostra_verso');
        $mostra_verso->addItems([0 => 'Não', 1 => 'Sim']);

        $bg_frente = new TFile('bg_frente');
        $bg_verso = new TFile('bg_verso');

        $margem_esquerda = new TSpinner('margem_esquerda');
        $margem_direita = new TSpinner('margem_direita');

        $this->form->addFields([new TLabel('ID')], [$id_certificado]);
        $this->form->addFields([new TLabel('Evento', 'red')], [$id_evento]);
        $this->form->addFields([new TLabel('Título', 'red')], [$titulo_certificado]);
        $this->form->addFields([new TLabel('Descrição')], [$descricao]);
        $this->form->addFields([new TLabel('Data de Emissão', 'red')], [$data_emissao]); $this->form->addFields([new TLabel('Carga Horária')], [$carga_horaria_certificado]);
        $this->form->addFields([new TLabel('Orientação')], [$orientacao_pagina]);
        $this->form->addFields([new TLabel('Mostrar Verso')], [$mostra_verso]);
        $this->form->addFields([new TLabel('Margem Esquerda')], [$margem_esquerda]);
        $this->form->addFields([new TLabel('Margem Direita')], [$margem_direita]);
        $this->form->addFields([new TLabel('Imagem de Fundo - Frente')], [$bg_frente]);
        $this->form->addFields([new TLabel('Imagem de Fundo - Verso')], [$bg_verso]);

        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addActionLink('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
        $this->form->addActionLink('Listar', new TAction(['CertificadosView', 'onReload']), 'fa:table blue');

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->form);

        parent::add($vbox);
    }
}
