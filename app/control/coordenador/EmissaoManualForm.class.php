<?php
class EmissaoManualForm extends TPage
{
    protected $form;

    use Adianti\Base\AdiantiStandardFormTrait;

    public function __construct()
    {
        parent::__construct();

        $this->setDatabase('teste');
        $this->setActiveRecord('EmissaoManual');

        $this->form = new BootstrapFormBuilder('form_EmissaoManual');
        $this->form->setFormTitle('Emissão Manual de Certificado');
        $this->form->setClientValidation(true);

        $id_emissao = new THidden('id_emissao');
        
        $id_evento = new TDBUniqueSearch('id_evento', 'teste', 'Evento', 'id_evento', 'titulo_evento');
        $nome_pessoa = new TEntry('nome_pessoa');
        $nome_pessoa->setExitAction(new TAction([$this, 'onAtualizaTexto']));
        
        $bg_frente_certificado = new TCombo('bg_frente_certificado');
        $bg_frente_certificado->addItems([
            'fundacao.png' => 'Modelo FE',
            'fafram.png'   => 'Modelo FAFRAM',
            'gegrao.png'   => 'Modelo GEGRAO',
            'gecaf.png'    => 'Modelo GECAF',
        ]);
        $bg_frente_certificado->setValue('fafram.png');

        $data_extenso_hoje = CertificadoService::formatarDataExtenso(date('Y-m-d'));
        $texto_padrao = "A FAFRAM - Faculdade Dr. Francisco Maeda, pelo presente, certifica que <b>[NOME DO PARTICIPANTE]</b> participou do evento <b>[TÍTULO DO EVENTO]</b>, realizado em [DATA DO EVENTO], com carga horária de XX horas.<br><br>Ituverava, {$data_extenso_hoje}.";

        $descricao_certificado = new THtmlEditor('descricao_certificado');
        $descricao_certificado->setSize('100%', 200);
        $descricao_certificado->setValue($texto_padrao);

        $this->form->addFields([$id_emissao]);
        
        $this->form->addFields([new TLabel('Evento', 'red')], [$id_evento]);
        $this->form->addFields([new TLabel('Nome do Favorecido', 'red')], [$nome_pessoa]);
        $this->form->addFields([new TLabel('Modelo do Fundo', 'red')], [$bg_frente_certificado]);
        $this->form->addFields([new TLabel('Texto do Certificado', 'red')], [$descricao_certificado]);

        $id_evento->addValidation('Evento', new TRequiredValidator);
        $nome_pessoa->addValidation('Nome', new TRequiredValidator);
        $descricao_certificado->addValidation('Texto do Certificado', new TRequiredValidator);

        $this->form->addAction('Salvar Certificado', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addActionLink('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
        $this->form->addActionLink('Voltar', new TAction(['EmissaoManualList', 'onReload']), 'fa:table blue');

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

            $emissao = new EmissaoManual();
            $emissao->fromArray((array) $data);

            if (empty($data->id_emissao)) {
                $emissao->data_emissao = date('Y-m-d H:i:s');
                $emissao->coordenador_id = TSession::getValue('userid');
            }

            $emissao->store();

            TTransaction::close();

            $this->form->setData($emissao);
            new TMessage('info', 'Certificado salvo com sucesso!');

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
            $this->form->setData($this->form->getData());
        }
    }

    public function onEdit($param)
    {
        try {
            if (isset($param['key'])) {
                TTransaction::open('teste');
                $object = new EmissaoManual($param['key']);
                $this->form->setData($object);
                TTransaction::close();
            } else {
                $this->form->clear(true);
            }
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onAtualizaTexto($param)
    {
        if (!empty($param['id_emissao'])) {
            return;
        }

        $nome = !empty($param['nome_pessoa']) ? $param['nome_pessoa'] : '[NOME DO PARTICIPANTE]';
        $titulo_evento = '[TÍTULO DO EVENTO]';
        $periodo_evento = 'realizado no dia DD/MM/AAAA';

        if (!empty($param['id_evento'])) {
            try {
                TTransaction::open('teste');
                $evento = new Evento($param['id_evento']);
                if (!empty($evento->titulo_evento)) {
                    $titulo_evento = $evento->titulo_evento;
                }
                
                if (!empty($evento->data_inicio_evento) && !empty($evento->data_fim_evento)) {
                    $periodo_evento = CertificadoService::formatarPeriodoEvento($evento->data_inicio_evento, $evento->data_fim_evento);
                }
                
                TTransaction::close();
            } catch (Exception $e) {
                TTransaction::rollback();
            }
        }
        
        $data_emissao_extenso = CertificadoService::formatarDataExtenso(date('Y-m-d'));
        
        $obj = new stdClass;
        $obj->descricao_certificado = "A FAFRAM - Faculdade Dr. Francisco Maeda, pelo presente, certifica que <b>{$nome}</b> participou do evento <b>{$titulo_evento}</b>, {$periodo_evento}, com carga horária de XX horas.<br><br>Ituverava, {$data_emissao_extenso}.";
        
        TForm::sendData('form_EmissaoManual', $obj);
    }
}