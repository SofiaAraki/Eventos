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
class RegistrosFormView extends TPage
{
    protected $form;
    use Adianti\Base\AdiantiStandardFormTrait;

    function __construct()
    {
        parent::__construct();

        $this->setDatabase('test');
        $this->setActiveRecord('Registros');

        $this->form = new BootstrapFormBuilder('form_Registros');
        $this->form->setFormTitle('Registro de Certificado');
        $this->form->setClientValidation(true);

        $id_registro = new THidden('id_registro');
        $id_evento = new Thidden('id_evento');
        $descricao_certificado = new TText('descricao_certificado');
        $tipo_certificado = new TCombo('tipo_certificado');
        $tipo_certificado->addItems([
            'aluno'      => 'Aluno',
            'banca'      => 'Banca',
            'orientador' => 'Orientador',
            'palestrante'=> 'Palestrante',
            'autor'      => 'Autor'
        ]);
        
        $this->form->addFields([$id_registro]);
        $this->form->addFields([$id_evento]);
        $this->form->addFields([new TLabel('Tipo do Certificado', 'red')], [$tipo_certificado]);
        $this->form->addFields([new TLabel('Placeholder', 'red')], [$descricao_certificado]);
        $descricao_certificado->setSize('100%', 200);

        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addActionLink('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
        $this->form->addActionLink('Voltar', new TAction(['RegistrosView', 'onReload']), 'fa:table blue');

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
            if (!empty($data->id_registro)) {
                $object = new Registros($data->id_registro); // EDITAR
                new TMessage('info', 'Registro do Certificado atualizado com sucesso!');
            } else {
                $object = new Registros;                        // INSERIR
                new TMessage('info', 'Registro do Certificado criado com sucesso!');

            }

            $object->fromArray((array) $data);   

            $object->store();                

            $this->form->setData($object);    

            TTransaction::close();

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

}
