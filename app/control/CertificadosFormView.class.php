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

        $id_certificado = new THidden('id_certificado');
        $id_evento = new TDBUniqueSearch('id_evento', 'test', 'Eventos', 'id_evento', 'titulo_evento');
        $titulo_certificado = new TEntry('titulo_certificado');
        $descricao_certificado = new TText('descricao_certificado');
        $data_emissao_certificado = new TDate('data_emissao_certificado');
        $carga_horaria_certificado = new TEntry('carga_horaria_certificado');
        $bg_frente = new TFile('bg_frente');

        $this->form->addFields([$id_certificado]);
        $this->form->addFields([new TLabel('Evento', 'red')], [$id_evento]);
        $this->form->addFields([new TLabel('Nome do Certificado', 'red')], [$titulo_certificado]);
        $this->form->addFields(
            [new TLabel('Data de Emissão', 'red')], [$data_emissao_certificado],
            [new TLabel('Carga Horária', 'red')], [$carga_horaria_certificado]);
        $this->form->addFields(
            [new TLabel('Imagem de Fundo', 'red')], [$bg_frente]);
        $this->form->addFields([new TLabel('Placeholder', 'red')], [$descricao_certificado]);
        $descricao_certificado->setSize('100%', 200);

        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addActionLink('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
        $this->form->addActionLink('Voltar', new TAction(['CertificadosView', 'onReload']), 'fa:table blue');

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->form);

        parent::add($vbox);
    }

    public function onSave($param)
    {
        try {
            TTransaction::open('test');
            
            $this->form->validate();               
            $data = $this->form->getData();    

            // Carrega ou cria novo objeto com base no id
            if (!empty($data->id_certificado)) {
                $object = new Certificados($data->id_certificado); // EDITAR
            } else {
                $object = new Certificados;                        // INSERIR
            }

            $object->fromArray((array) $data);   

            $object->store();                

            $this->form->setData($object);    

            TTransaction::close();

            new TMessage('info', 'Certificado criado com sucesso!');
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

}
