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

    function __construct()
    {
        parent::__construct();

        $this->setDatabase('test');     // nome da conexão
        $this->setActiveRecord('Tccs'); // nome da classe ActiveRecord correta

        $this->form = new BootstrapFormBuilder('form_Tccs');
        $this->form->setFormTitle('Cadastro de TCC');
        $this->form->setClientValidation(true);

        // campos
        $id_tcc = new THidden('id_tcc');
        $titulo_tcc = new TEntry('titulo_tcc');
        $autores = new TDBMultiSearch('autores', 'test', 'SystemUser', 'id', 'name');
        $orientador = new TDBUniqueSearch('id_orientador', 'test', 'SystemUser', 'id', 'name');
        $banca = new TDBMultiSearch('banca', 'test', 'SystemUser', 'id', 'name');
        $data_tcc = new TDate('data_tcc');

        // adicionando campos no form
        $this->form->addFields([$id_tcc]);
        $this->form->addFields([new TLabel('Tema', 'red')], [$titulo_tcc]);
        $this->form->addFields([new TLabel('Autores', 'red')], [$autores]);
        $this->form->addFields([new TLabel('Orientador', 'red')], [$orientador]);
        $this->form->addFields([new TLabel('Banca', 'red')], [$banca]);
        $this->form->addFields([new TLabel('Data da Defesa', 'red')], [$data_tcc]);

        // ações
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
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

            // Obtém o usuário logado que será o gerente do evento
            $user = SystemUser::newFromLogin(TSession::getValue('login'));
            $gerente_id = $user->id;

            $this->form->validate();
            $data = $this->form->getData();

            $data->gerente_evento = $gerente_id;
            $novo = empty($data->id_tcc);

            // Instancia o TCC
            $tcc = $novo ? new Tccs : new Tccs($data->id_tcc);
            $tcc->fromArray((array) $data);

            if ($novo) {
                // 1) Cria o evento
                $evento = new Eventos;
                $evento->titulo_evento      = $tcc->titulo_tcc;
                $evento->data_inicio_evento = $tcc->data_tcc;
                $evento->data_fim_evento    = $tcc->data_tcc;
                $evento->gerente_evento     = $gerente_id;
                $evento->status_evento      = 0;
                $evento->store();

                // 2) Associa o evento ao TCC
                $tcc->id_evento = $evento->id_evento;
            }

            // 3) Salva o TCC
            $tcc->store();

            // 4) Relaciona os autores e banca
            $tcc->setAutores($data->autores ?? []);
            $tcc->setBanca($data->banca ?? []);

            // 5) Inscreve os envolvidos se for novo
            if ($novo) {
                $inscritos = array_merge(
                    $data->autores ?? [],
                    [$data->id_orientador],
                    $data->banca ?? []
                );

                $tiposCriados = [];

                foreach ($inscritos as $id_usuario) {
                    $tipo = $this->getTipoParticipacao(
                        $id_usuario,
                        $data->autores ?? [],
                        $data->banca ?? [],
                        $data->id_orientador
                    );

                    // Insere inscrição
                    $inscricao = new Inscricoes;
                    $inscricao->id_evento         = $tcc->id_evento;
                    $inscricao->id_usuario        = $id_usuario;
                    $inscricao->status_inscricao  = 1;
                    $inscricao->data_inscricao    = date('Y-m-d H:i:s');
                    $inscricao->tipo_participacao = $tipo;
                    $inscricao->store();

                    // Cria o certificado-modelo para o tipo, se ainda não existir
                    if (!in_array($tipo, $tiposCriados)) {
                        $certificado = new Certificados;
                        $certificado->id_evento                 = $tcc->id_evento;
                        $certificado->tipo_certificado          = $tipo;
                        $certificado->titulo_certificado        = "Certificado de $tipo - {$evento->titulo_evento}";
                        $certificado->descricao_certificado     = null;
                        $certificado->carga_horaria_certificado = 0;
                        $certificado->bg_frente                 = null;
                        $certificado->data_emissao_certificado  = date('Y-m-d H:i:s');
                        $certificado->store();

                        $tiposCriados[] = $tipo;
                    }
                }

                new TMessage('info', "TCC e evento criados com sucesso! Inscrições e certificados-modelo criados.");
            } else {
                new TMessage('info', 'TCC atualizado com sucesso!');
            }

            $this->form->setData($tcc);

            TTransaction::close();

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    /**
     * Determina o tipo de participação do usuário com base nos dados do formulário
     *
     * @param int   $id_usuario
     * @param array $autores
     * @param array $banca
     * @param int   $id_orientador
     * @return string tipo_participacao
     */
    private function getTipoParticipacao(int $id_usuario, array $autores, array $banca, ?int $id_orientador): string
    {
        if (in_array($id_usuario, $autores)) {
            return 'autor';
        }

        if ($id_orientador && $id_usuario == $id_orientador) {
            return 'orientador';
        }

        if (in_array($id_usuario, $banca)) {
            return 'banca';
        }

        return 'aluno';
    }

    function onEdit($param)
    {
        try {
            if (isset($param['key'])) {
                TTransaction::open('test');

                $object = new Tccs($param['key']);

                $data = $object->toArray();
                $data['autores'] = array_values($object->getAutores());
                $data['banca']   = array_values($object->getBanca());

                $this->form->setData((object)$data);

                TTransaction::close();
            } else {
                $this->form->clear(true);
            }
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
}
