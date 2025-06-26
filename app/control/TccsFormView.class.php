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
class TccsFormView extends TPage
{
    protected $form;
    use Adianti\Base\AdiantiStandardFormTrait;

    public function __construct()
    {
        parent::__construct();

        $this->setDatabase('test');
        $this->setActiveRecord('Tccs');

        $this->form = new BootstrapFormBuilder('form_Tccs');
        $this->form->setFormTitle('Novo TCC');
        $this->form->setClientValidation(true);

        $id_tcc      = new THidden('id_tcc');
        $titulo_tcc  = new TEntry('titulo_tcc');
        $autores     = new TDBMultiSearch('autores', 'test', 'SystemUser', 'id', 'name');
        $orientador  = new TDBUniqueSearch('id_orientador', 'test', 'SystemUser', 'id', 'name');
        $banca       = new TDBMultiSearch('banca', 'test', 'SystemUser', 'id', 'name');
        $data_tcc    = new TDate('data_tcc');

        $this->form->addFields([$id_tcc]);
        $this->form->addFields([new TLabel('Tema', 'red')],      [$titulo_tcc]);
        $this->form->addFields([new TLabel('Autores', 'red')],   [$autores]);
        $this->form->addFields([new TLabel('Orientador', 'red')],[$orientador]);
        $this->form->addFields([new TLabel('Banca', 'red')],     [$banca]);
        $this->form->addFields([new TLabel('Data da Defesa', 'red')], [$data_tcc]);

        $this->form->addAction('Salvar',  new TAction([$this, 'onSave']),   'fa:save green');
        $this->form->addActionLink('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
        $this->form->addActionLink('Voltar', new TAction(['TccsView', 'onReload']), 'fa:table blue');

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

            $usuarioLogado = SystemUser::newFromLogin(TSession::getValue('login'));
            $data->gerente_evento = $usuarioLogado->id;

            $novo = empty($data->id_tcc);
            $tcc  = $novo ? new Tccs : new Tccs($data->id_tcc);
            $tcc->fromArray((array) $data);

            if ($novo) {
                $evento = new Eventos;
                $evento->titulo_evento      = $tcc->titulo_tcc;
                $evento->data_inicio_evento = $tcc->data_tcc;
                $evento->data_fim_evento    = $tcc->data_tcc;
                $evento->gerente_evento     = $usuarioLogado->id;
                $evento->status_evento      = 0;
                $evento->store();

                $tcc->id_evento = $evento->id_evento;
            }

            $tcc->store();

            $tcc->setAutores($data->autores ?? []);
            $tcc->setBanca($data->banca ?? []);

            // Registrar inscrições e certificados (para novo e edição)
            $this->registrarInscricoesECertificados($tcc, $data);

            $mensagem = $novo ? 'TCC criado com sucesso!' : 'TCC atualizado com sucesso!';
            new TMessage('info', $mensagem);

            $this->form->setData($tcc);
            TTransaction::close();

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    private function registrarInscricoesECertificados($tcc, $data)
    {
        $inscritos = array_merge($data->autores ?? [], [$data->id_orientador], $data->banca ?? []);
        $inscritos = array_unique($inscritos);
        $tiposCriados = [];

        foreach ($inscritos as $id_usuario) {
            // Verifica se já existe inscrição
            $inscricao_existente = Inscricoes::where('id_evento', '=', $tcc->id_evento)
                                            ->where('id_usuario', '=', $id_usuario)
                                            ->first();

            if ($inscricao_existente) {
                continue; // Pula se já está inscrito
            }

            $tipo = $this->getTipoParticipacao($id_usuario, $data->autores ?? [], $data->banca ?? [], $data->id_orientador);

            $inscricao = new Inscricoes;
            $inscricao->id_evento         = $tcc->id_evento;
            $inscricao->id_usuario        = $id_usuario;
            $inscricao->status_inscricao  = 1;
            $inscricao->data_inscricao    = date('Y-m-d H:i:s');
            $inscricao->tipo_participacao = $tipo;
            $inscricao->store();

            // Verifica se já existe certificado para o tipo
            $certificado_existente = Certificados::where('id_evento', '=', $tcc->id_evento)
                                                ->where('tipo_certificado', '=', $tipo)
                                                ->first();

            if (!$certificado_existente && !in_array($tipo, $tiposCriados)) {
                $certificado = new Certificados;
                $certificado->id_evento                 = $tcc->id_evento;
                $certificado->tipo_certificado          = $tipo;
                $certificado->titulo_certificado        = "Certificado de $tipo - {$tcc->titulo_tcc}";
                $certificado->data_emissao_certificado  = date('Y-m-d H:i:s');
                $certificado->carga_horaria_certificado = 0;
                $certificado->store();

                $tiposCriados[] = $tipo;
            }
        }
    }

    private function getTipoParticipacao(int $id_usuario, array $autores, array $banca, ?int $id_orientador): string
    {
        if (in_array($id_usuario, $autores))      return 'autor';
        if ($id_orientador && $id_usuario == $id_orientador) return 'orientador';
        if (in_array($id_usuario, $banca))        return 'banca';
        return 'aluno';
    }

    public function onEdit($param)
    {
        try {
            if (isset($param['key'])) {
                TTransaction::open('test');

                $tcc = new Tccs($param['key']);
                $data = $tcc->toArray();
                $data['autores'] = array_values($tcc->getAutores());
                $data['banca']   = array_values($tcc->getBanca());

                $this->form->setData((object) $data);
                TTransaction::close();
            } else {
                $this->form->clear(true);
            }
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}
