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
        $id_inscricao = new THidden('id_inscricao');
        $id_certificado = new THidden('id_certificado');
        $descricao_certificado = new TText('descricao_certificado');
        
        $this->form->addFields([$id_registro], [$id_inscricao], [$id_certificado]);
        $this->form->addFields([new TLabel('Descrição do Certificado', 'red')], [$descricao_certificado]);
        $descricao_certificado->setSize('100%', 200);

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

            if (!empty($data->id_registro))
            {
                $object = new Registro($data->id_registro);
            }
            else
            {
                $object = new Registro;
                $object->data_registro = date('Y-m-d H:i:s');
            }

            $object->fromArray((array) $data);

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