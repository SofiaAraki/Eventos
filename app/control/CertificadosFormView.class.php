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

    public function __construct()
    {
        parent::__construct();

        $this->setDatabase('test');       // database
        $this->setActiveRecord('Certificados'); // active record

        $this->form = new BootstrapFormBuilder('form_Certificados');
        $this->form->setFormTitle('Novo Certificado');
        $this->form->setClientValidation(true);

        // campos
        $id_certificado               = new THidden('id_certificado');
        $id_evento                    = new TDBUniqueSearch('id_evento', 'test', 'Eventos', 'id_evento', 'titulo_evento');
        $titulo_certificado           = new TEntry('titulo_certificado');
        $data_emissao_certificado     = new TDate('data_emissao_certificado');
        $carga_horaria_certificado    = new TEntry('carga_horaria_certificado');
        $bg_frente = new TCombo('bg_frente');
        $bg_frente->addItems([
            'fundacao.png' => 'Modelo FE',
            'fafram.png'   => 'Modelo FAFRAM',
        ]);
        $tipo_certificado             = new TCombo('tipo_certificado');
        $tipo_certificado->addItems([
            'aluno'       => 'Aluno',
            'banca'       => 'Banca',
            'orientador'  => 'Orientador',
            'palestrante' => 'Palestrante',
            'autor'       => 'Autor'
        ]);

        // validadores
        $id_evento->addValidation('Evento', new TRequiredValidator);
        $titulo_certificado->addValidation('Título do certificado', new TRequiredValidator);
        $data_emissao_certificado->addValidation('Data de emissão', new TRequiredValidator);
        $carga_horaria_certificado->addValidation('Carga horária', new TRequiredValidator);
        $tipo_certificado->addValidation('Tipo', new TRequiredValidator);

        // campos
        $this->form->addFields([$id_certificado]);
        $this->form->addFields([new TLabel('Evento', 'red')], [$id_evento]);
        $this->form->addFields([new TLabel('Nome do Certificado', 'red')], [$titulo_certificado]);
        $this->form->addFields(
            [new TLabel('Data de Emissão', 'red')], [$data_emissao_certificado],
            [new TLabel('Carga Horária', 'red')], [$carga_horaria_certificado]
        );
        $this->form->addFields(
            [new TLabel('Imagem de Fundo', 'red')], [$bg_frente],
            [new TLabel('Tipo do Certificado', 'red')], [$tipo_certificado]
        );

        // ações
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addActionLink('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
        $this->form->addActionLink('Voltar', new TAction(['CertificadosView', 'onReload']), 'fa:table blue');

        // layout
        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->form);

        parent::add($vbox);
    }

    /**
     * Salva o certificado
     */
    public function onSave($param)
    {
        try {
            TTransaction::open('test');

            $this->form->validate();
            $data = $this->form->getData();

            // instanciar objeto
            $object = !empty($data->id_certificado)
                ? new Certificados($data->id_certificado)
                : new Certificados;

            $object->fromArray((array) $data);   
            $object->store();

            // mensagem
            $message = $data->id_certificado
                ? 'Certificado atualizado com sucesso!'
                : 'Certificado criado com sucesso!';
            new TMessage('info', $message);

            $this->form->setData($object);    
            TTransaction::close();

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}
