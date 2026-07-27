<?php
class TccForm extends TPage
{
    protected $form;

    use Adianti\Base\AdiantiStandardFormTrait;

    public function __construct()
    {
        parent::__construct();

        $this->setDatabase('teste');
        $this->setActiveRecord('Tcc');

        $this->form = new BootstrapFormBuilder('form_Tcc');
        $this->form->setFormTitle('Gerenciar TCC');

        $id_tcc      = new THidden('id_tcc');
        $titulo_tcc  = new TEntry('titulo_tcc');
        $autores     = new TDBMultiSearch('autores', 'teste', 'SystemUser', 'id', 'name');
        $orientador  = new TDBUniqueSearch('id_orientador', 'teste', 'SystemUser', 'id', 'name');
        $banca       = new TDBMultiSearch('banca', 'teste', 'SystemUser', 'id', 'name');
        $data_tcc    = new TDateTime('data_tcc');

        // Formatação brasileira (BR) para exibição e salvamento no banco de dados
        $data_tcc->setMask('dd/mm/yyyy hh:ii');
        $data_tcc->setDatabaseMask('yyyy-mm-dd hh:ii');

        $this->form->addFields([$id_tcc]);
        $this->form->addFields([new TLabel('Tema', 'red')], [$titulo_tcc]);
        $this->form->addFields([new TLabel('Autor', 'red')], [$autores]);
        $this->form->addFields([new TLabel('Orientador', 'red')], [$orientador]);
        $this->form->addFields([new TLabel('Banca', 'red')], [$banca]);
        $this->form->addFields([new TLabel('Data da Defesa', 'red')], [$data_tcc]);

        $titulo_tcc->addValidation('Tema', new TRequiredValidator);
        $autores->addValidation('Autor', new TRequiredValidator);
        $orientador->addValidation('Orientador', new TRequiredValidator);
        $banca->addValidation('Banca', new TRequiredValidator);
        $data_tcc->addValidation('Data da Defesa', new TRequiredValidator);

        $this->form->addAction('Salvar',  new TAction([$this, 'onSave']),   'fa:save green');
        $this->form->addActionLink('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
        $this->form->addActionLink('Voltar', new TAction(['TccList', 'onReload']), 'fa:table blue');

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
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
                throw new Exception('Um usuário não pode ser autor e membro da banca.');
            }

            if ($data->id_orientador && in_array($data->id_orientador, $banca)) {
                throw new Exception('O orientador não pode participar da banca.');
            }

            if ($data->id_orientador && in_array($data->id_orientador, $autores)) {
                throw new Exception('O orientador não pode ser autor.');
            }

            $tcc = TccService::salvar($data);

            $mensagem = empty($data->id_tcc) ? 'TCC criado com sucesso!' : 'TCC atualizado com sucesso!';
            new TMessage('info', $mensagem);
            $this->form->setData($tcc);

        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }

    public function onEdit($param)
    {
        try
        {
            if (isset($param['key']))
            {
                TTransaction::open('teste');

                $tcc = new Tcc($param['key']);

                $data = new stdClass;
                $data->id_tcc         = $tcc->id_tcc;
                $data->titulo_tcc     = $tcc->titulo_tcc;
                $data->id_orientador  = $tcc->id_orientador;
                $data->data_tcc       = $tcc->data_tcc;
                $data->autores = [];

                foreach ($tcc->get_autores() as $autor)
                {
                    $data->autores[] = $autor->id_autor_usuario;
                }

                $data->banca = [];

                foreach ($tcc->get_banca() as $membro)
                {
                    $data->banca[] = $membro->id_banca_usuario;
                }

                $this->form->setData($data);

                TTransaction::close();
            }
            else
            {
                $this->form->clear(true);
            }
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}