<?php
class RegistroForm extends TPage
{
    protected $form;

    use Adianti\Base\AdiantiStandardFormTrait;

    function __construct()
    {
        parent::__construct();

        $this->setDatabase('teste');
        $this->setActiveRecord('Registro');

        $this->form = new BootstrapFormBuilder('form_Registro');
        $this->form->setFormTitle('Registro de Certificado');
        $this->form->setClientValidation(true);

        $id_registro = new THidden('id_registro');
        
        $id_inscricao = new TDBUniqueSearch('id_inscricao', 'teste', 'Inscricao', 'id_inscricao', 'id_inscricao');
        $id_inscricao->addValidation('Inscrição', new TRequiredValidator);
        
        $id_certificado = new TDBUniqueSearch('id_certificado', 'teste', 'Certificado', 'id_certificado', 'titulo_certificado');
        $id_certificado->addValidation('Certificado', new TRequiredValidator);

        $descricao_certificado = new TText('descricao_certificado');
        $descricao_certificado->setSize('100%', 200);

        $this->form->addFields([$id_registro]);
        $this->form->addFields([new TLabel('Inscrição:', 'red')], [$id_inscricao]);
        $this->form->addFields([new TLabel('Modelo de Certificado:', 'red')], [$id_certificado]);
        $this->form->addFields([new TLabel('Descrição Personalizada do Certificado')], [$descricao_certificado]);

        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addActionLink('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
        $this->form->addActionLink('Voltar', new TAction(['RegistroList', 'onReload']), 'fa:table blue');

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->form);

        parent::add($vbox);
    }

    public function onSave($param)
    {
        try
        {
            TTransaction::open('teste');

            $this->form->validate();
            $data = $this->form->getData();

            $object = new Registro;
            $object->fromArray((array) $data);

            // Preenche a data de emissão no cadastro inicial se não informada
            if (empty($object->id_registro) && empty($object->data_registro))
            {
                $object->data_registro = date('Y-m-d H:i:s');
            }

            $object->store();

            $this->form->setData($object);

            TTransaction::close();

            new TMessage(
                'info',
                !empty($data->id_registro)
                    ? 'Registro atualizado com sucesso!'
                    : 'Registro criado com sucesso!'
            );
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}