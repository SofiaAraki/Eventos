<?php
class EventoFormCoordenador extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_solicitacao_evento');
        $this->form->setFormTitle('Solicitação de Evento');
        $this->form->setClientValidation(true);

        $id                  = new THidden('id_evento');
        $titulo_evento       = new TEntry('titulo_evento');
        $local_evento        = new TEntry('local_evento');
        $data_inicio_evento  = new TDateTime('data_inicio_evento');
        $data_fim_evento     = new TDateTime('data_fim_evento');
        $descricao_evento    = new TText('descricao_evento');
        
        $monitor_evento      = new TDBMultiSearch('monitor_evento', 'teste', 'SystemUser', 'id', 'name');

        $arte_evento = new TFile('arte_evento');
        $arte_evento->setAllowedExtensions(['png', 'jpg', 'jpeg', 'webp']);
        $arte_evento->setService('SystemDocumentUploaderService');
        $arte_evento->enableFileHandling();

        $data_inicio_evento->setMask('dd/mm/yyyy hh:ii');
        $data_inicio_evento->setDatabaseMask('yyyy-mm-dd hh:ii');

        $data_fim_evento->setMask('dd/mm/yyyy hh:ii');
        $data_fim_evento->setDatabaseMask('yyyy-mm-dd hh:ii');

        $this->form->addFields([$id]);
        $this->form->addFields([new TLabel('Título', 'red')], [$titulo_evento]);
        $this->form->addFields([new TLabel('Local', 'red')], [$local_evento]);
        $this->form->addFields(
            [new TLabel('Data Início', 'red')], [$data_inicio_evento],
            [new TLabel('Data Fim', 'red')], [$data_fim_evento]
        );
        $this->form->addFields([new TLabel('Arte do Evento', 'red')], [$arte_evento]);
        $this->form->addFields([new TLabel('Descrição')], [$descricao_evento]);
        $this->form->addFields([new TLabel('Monitores')], [$monitor_evento]);

        $titulo_evento->addValidation('Título do Evento', new TRequiredValidator);
        $local_evento->addValidation('Local do Evento', new TRequiredValidator);
        $data_inicio_evento->addValidation('Data de Início', new TRequiredValidator);
        $data_fim_evento->addValidation('Data de Fim', new TRequiredValidator);

        $this->form->addAction('Salvar Evento', new TAction([$this, 'onSave']), 'far:check-circle green');
        $this->form->addActionLink('Voltar', new TAction(['SolicitacaoListCoordenador', 'onReload']), 'fa:arrow-left blue');

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', 'SolicitacaoListCoordenador'));
        $vbox->add($this->form);

        parent::add($vbox);
    }

    public function onSave()
    {
        try
        {
            $this->form->validate();
            $data = $this->form->getData();

            if (!empty($data->data_fim_evento) && !empty($data->data_inicio_evento)) {
                $inicio = DateTime::createFromFormat('d/m/Y H:i', $data->data_inicio_evento) ?: new DateTime($data->data_inicio_evento);
                $fim    = DateTime::createFromFormat('d/m/Y H:i', $data->data_fim_evento) ?: new DateTime($data->data_fim_evento);

                if ($fim < $inicio) {
                    throw new Exception('A data de fim não pode ser anterior à data de início.');
                }
            }

            TTransaction::open('teste');

            $is_update = !empty($data->id_evento);
            $user_id   = TSession::getValue('userid');

            $evento = new Evento($data->id_evento ?? null);
            $evento->fromArray((array) $data);

            if (!$is_update) {
                $evento->gerente_evento   = $user_id;
                $evento->status_aprovacao = 0;
            }

            $evento->atualizado_por   = $user_id;
            $evento->data_atualizacao = date('Y-m-d H:i:s');

            if (!empty($data->arte_evento)) {
                $arte_raw = urldecode($data->arte_evento);
                
                if (is_string($arte_raw) && strpos($arte_raw, '{') !== false) {
                    $json = json_decode($arte_raw, true);
                    $file_path = $json['newFile'] ?? $json['fileName'] ?? '';
                } else {
                    $file_path = $arte_raw;
                }

                if (!empty($file_path) && file_exists($file_path)) {
                    if (strpos($file_path, 'tmp/') === 0) {
                        $target_dir = 'app/images/eventos/';
                        if (!file_exists($target_dir)) {
                            mkdir($target_dir, 0777, true);
                        }
                        $target_file = $target_dir . basename($file_path);
                        rename($file_path, $target_file);
                        $evento->arte_evento = $target_file;
                    } else {
                        $evento->arte_evento = $file_path;
                    }
                }
            }

            $evento->store();

            $this->syncMonitores($evento, (array) ($data->monitor_evento ?? []));

            $data->id_evento   = $evento->id_evento;
            $data->arte_evento = $evento->arte_evento;
            
            $this->form->setData($data);

            TTransaction::close();

            new TMessage(
                'info', 
                $is_update ? 'Solicitação de evento atualizada!' : 'Solicitação de evento enviada com sucesso!',
                new TAction(['SolicitacaoListCoordenador', 'onReload'])
            );
        }
        catch (Exception $e)
        {
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
                
                $evento = new Evento($param['key']);
                $data = (object) $evento->toArray();

                $data->monitor_evento = $evento->get_monitor_evento();

                $this->form->setData($data);
                
                TTransaction::close();
            } else {
                $this->form->clear(true);
            }
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onClear()
    {
        $this->form->clear();
    }

    private function syncMonitores(Evento $evento, array $ids): void
    {
        $repo = new TRepository('Monitor');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('id_evento', '=', $evento->id_evento));
        $repo->delete($criteria);

        $ids = array_unique(array_filter($ids));

        foreach ($ids as $userId)
        {
            $m = new Monitor;
            $m->id_evento  = $evento->id_evento;
            $m->id_usuario = (int) $userId;
            $m->store();

            try {
                $group = new SystemGroup(6);
                $user = new SystemUser((int) $userId);
                if ($user->id) {
                    $group->addSystemUser($user);
                }
            } catch (Exception $e) {
                // Proteção contra falhas de inserção de grupo
            }
        }
    }
}