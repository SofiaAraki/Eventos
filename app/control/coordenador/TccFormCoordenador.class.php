<?php
class TccFormCoordenador extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_solicitacao_tcc');
        $this->form->setFormTitle('Solicitação de TCC');
        $this->form->setClientValidation(true);

        $id_tcc      = new THidden('id_tcc');
        $titulo_tcc  = new TEntry('titulo_tcc');
        $autores     = new TDBMultiSearch('autores', 'teste', 'SystemUser', 'id', 'name');
        $orientador  = new TDBUniqueSearch('id_orientador', 'teste', 'SystemUser', 'id', 'name');
        $banca       = new TDBMultiSearch('banca', 'teste', 'SystemUser', 'id', 'name');
        $data_tcc    = new TDateTime('data_tcc');

        $data_tcc->setMask('dd/mm/yyyy hh:ii');
        $data_tcc->setDatabaseMask('yyyy-mm-dd hh:ii');

        $this->form->addFields([$id_tcc]);
        $this->form->addFields([new TLabel('Tema do TCC', 'red')], [$titulo_tcc]);
        $this->form->addFields([new TLabel('Autor(es)', 'red')], [$autores]);
        $this->form->addFields([new TLabel('Orientador', 'red')], [$orientador]);
        $this->form->addFields([new TLabel('Banca Examinadora', 'red')], [$banca]);
        $this->form->addFields([new TLabel('Data e Hora da Defesa', 'red')], [$data_tcc]);

        $titulo_tcc->addValidation('Tema do TCC', new TRequiredValidator);
        $autores->addValidation('Autor(es)', new TRequiredValidator);
        $orientador->addValidation('Orientador', new TRequiredValidator);
        $banca->addValidation('Banca', new TRequiredValidator);
        $data_tcc->addValidation('Data e Hora da Defesa', new TRequiredValidator);

        $this->form->addAction('Enviar Solicitação', new TAction([$this, 'onSave']), 'far:check-circle green');
        $this->form->addActionLink('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
        $this->form->addActionLink('Voltar', new TAction(['TccListCoordenador', 'onReload']), 'fa:table blue');

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', 'TccListCoordenador'));
        $vbox->add($this->form);

        parent::add($vbox);
    }

    public function onSave($param)
    {
        try {
            $this->form->validate();
            $data = $this->form->getData();

            $autores = array_unique((array) ($data->autores ?? []));
            $banca   = array_unique((array) ($data->banca ?? []));

            if (array_intersect($autores, $banca)) {
                throw new Exception('Um usuário não pode ser autor e membro da banca simultaneamente.');
            }

            if ($data->id_orientador && in_array($data->id_orientador, $banca)) {
                throw new Exception('O orientador não pode fazer parte da banca examinadora.');
            }

            if ($data->id_orientador && in_array($data->id_orientador, $autores)) {
                throw new Exception('O orientador não pode ser cadastrado como autor.');
            }

            $id_tcc = TccService::salvar($data, true);

            $mensagem = empty($data->id_tcc) 
                ? 'Solicitação de TCC enviada com sucesso!' 
                : 'Solicitação de TCC atualizada com sucesso!';

            new TMessage('info', $mensagem, new TAction(['TccListCoordenador', 'onReload']));

        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            $this->form->setData($this->form->getData());
        }
    }

    public function onEdit($param)
    {
        try {
            if (isset($param['key'])) {
                TTransaction::open('teste');

                $tcc = new Tcc($param['key']);

                $data = new stdClass;
                $data->id_tcc        = $tcc->id_tcc;
                $data->titulo_tcc    = $tcc->titulo_tcc;
                $data->id_orientador = $tcc->id_orientador;
                $data->data_tcc      = $tcc->data_tcc;

                $data->autores = [];
                foreach ($tcc->get_autores() as $autor) {
                    $data->autores[] = $autor->id_autor_usuario;
                }

                $data->banca = [];
                foreach ($tcc->get_banca() as $membro) {
                    $data->banca[] = $membro->id_banca_usuario;
                }

                $this->form->setData($data);

                TTransaction::close();
            }
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onClear()
    {
        $this->form->clear(true);
    }
}