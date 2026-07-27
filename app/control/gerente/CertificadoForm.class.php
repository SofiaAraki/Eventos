<?php
class CertificadoForm extends TPage
{
    protected $form;

    use Adianti\Base\AdiantiStandardFormTrait;

    public function __construct()
    {
        parent::__construct();

        $this->setDatabase('teste');
        $this->setActiveRecord('Certificado');

        $this->form = new BootstrapFormBuilder('form_Certificado');
        $this->form->setFormTitle('Novo Certificado');
        $this->form->setClientValidation(true);

        $id_certificado = new TEntry('id_certificado');
        $id_evento = new TDBUniqueSearch('id_evento', 'teste', 'Evento', 'id_evento', 'titulo_evento');
        $titulo_certificado = new TEntry('titulo_certificado');

        $id_certificado->setEditable(FALSE);

        $carga_horaria_certificado = new TEntry('carga_horaria_certificado');
        $carga_horaria_certificado->addValidation('Carga Horária', new TNumericValidator);
        $presenca_minima_certificado = new TEntry('presenca_minima_certificado');
        $presenca_minima_certificado->addValidation('Presença Mínima', new TNumericValidator);
        $presenca_minima_certificado->setValue(0);
        $bg_frente_certificado = new TCombo('bg_frente_certificado');
        $bg_frente_certificado->addItems([
            'fundacao.png' => 'Modelo FE',
            'fafram.png'   => 'Modelo FAFRAM',
            'gegrao.png'   => 'Modelo GEGRAO',
        ]);
        $tipo_participacao = new TDBCombo(
            'tipo_participacao',
            'teste',
            'TiposParticipacao',
            'codigo',
            'descricao'
        );

        $this->form->addFields([new TLabel('ID', 'red')], [$id_certificado]);
        $this->form->addFields([new TLabel('Evento', 'red')], [$id_evento]);
        $this->form->addFields([new TLabel('Nome do Certificado', 'red')], [$titulo_certificado]);
        $this->form->addFields(
            [new TLabel('Carga Horária', 'red')], [$carga_horaria_certificado],
            [new TLabel('Presença Mínima (min)', 'red')], [$presenca_minima_certificado],
        );
        $this->form->addFields(
            [new TLabel('Imagem de Fundo', 'red')], [$bg_frente_certificado],
            [new TLabel('Tipo do Certificado', 'red')], [$tipo_participacao],
        );

        $titulo_certificado->addValidation('Título do certificado', new TRequiredValidator);
        $tipo_participacao->addValidation('Tipo', new TRequiredValidator);

        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addActionLink('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
        $this->form->addActionLink('Voltar', new TAction(['CertificadoList', 'onReload']), 'fa:table blue');

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->form);

        parent::add($vbox);
    }

    public function onSave()
    {
        try {
            TTransaction::open('teste');

            $this->form->validate();
            $data = $this->form->getData();

            $is_edit = !empty($data->id_certificado);

            $criterio = new TCriteria;
            $criterio->add(new TFilter('id_evento', '=', $data->id_evento));
            $criterio->add(new TFilter('tipo_participacao', '=', $data->tipo_participacao));

            if ($is_edit) {
                $criterio->add(new TFilter('id_certificado', '!=', $data->id_certificado));
            }

            $repo = new TRepository('Certificado');
            if ($repo->count($criterio) > 0) {
                throw new Exception('Já existe um certificado para esse evento com esse tipo.');
            }

            $bgPermitidos = ['fundacao.png', 'fafram.png', 'gegrao.png'];
            if (!in_array($data->bg_frente_certificado, $bgPermitidos, true)) {
                throw new Exception('Imagem de fundo inválida.');
            }

            $object = new Certificado($data->id_certificado ?? null); 
            $object->fromArray((array) $data);   
            $object->store();

            $data->id_certificado = $object->id_certificado;
            $this->form->setData($data);
            
            TTransaction::close();

            $message = $is_edit 
                ? 'Certificado atualizado com sucesso!' 
                : 'Certificado criado com sucesso!';
                
            new TMessage('info', $message);

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
            $this->form->setData($this->form->getData());
        }
    }
}