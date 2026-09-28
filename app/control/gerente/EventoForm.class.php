<?php
class EventoForm extends TPage
{
    protected $form;
    
    use Adianti\Base\AdiantiStandardFormTrait;

    public function __construct()
    {
        parent::__construct();

        $this->setDatabase('teste');
        $this->setActiveRecord('Evento');

        $this->form = new BootstrapFormBuilder('form_Evento');
        $this->form->setFormTitle('Novo Evento');
        $this->form->setClientValidation(true);

        $id = new TEntry('id_evento');
        $titulo_evento = new TEntry('titulo_evento');
        $local_evento = new TEntry('local_evento');
        $status_evento = new TCombo('status_evento');
        $data_inicio_evento = new TDateTime('data_inicio_evento');
        $data_fim_evento = new TDateTime('data_fim_evento');
        $descricao_evento = new TText('descricao_evento');
        
        $coordenador_evento = new TDBMultiSearch('coordenador_evento', 'teste', 'SystemUser', 'id', 'name');
        $monitor_evento = new TDBMultiSearch('monitor_evento', 'teste', 'SystemUser', 'id', 'name');
        
        $arte_evento = new TFile('arte_evento');
        $arte_evento->setAllowedExtensions(['png', 'jpg', 'jpeg', 'webp']);
        $arte_evento->setService('SystemDocumentUploaderService');
        $arte_evento->enableFileHandling();

        $data_inicio_evento->setMask('dd/mm/yyyy hh:ii');
        $data_inicio_evento->setDatabaseMask('yyyy-mm-dd hh:ii');

        $data_fim_evento->setMask('dd/mm/yyyy hh:ii');
        $data_fim_evento->setDatabaseMask('yyyy-mm-dd hh:ii');

        $id->setEditable(FALSE);
        $status_evento->addItems(['1' => 'Aberto', '0' => 'Fechado']);

        $this->form->addFields([new TLabel('ID', 'red')], [$id], [new TLabel('Status', 'red')], [$status_evento]);
        $this->form->addFields([new TLabel('Evento', 'red')], [$titulo_evento]);
        $this->form->addFields([new TLabel('Local', 'red')], [$local_evento]);
        $this->form->addFields([new TLabel('Data de Início', 'red')], [$data_inicio_evento], [new TLabel('Data de Fim', 'red')], [$data_fim_evento]);
        $this->form->addFields([new TLabel('Arte do Evento', 'red')], [$arte_evento]);
        $this->form->addFields([new TLabel('Descrição', 'red')], [$descricao_evento]);
        $this->form->addFields([new TLabel('Coordenadores')], [$coordenador_evento]);
        $this->form->addFields([new TLabel('Monitores')], [$monitor_evento]);

        $titulo_evento->addValidation('Título', new TRequiredValidator);

        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addActionLink('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
        $this->form->addActionLink('Voltar', new TAction(['EventoList', 'onReload']), 'fa:table blue');

        $vbox = new TVBox;
        $vbox->style = 'width:100%';
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

            if (!empty($data->data_fim_evento) && !empty($data->data_inicio_evento)) {
                $inicio = DateTime::createFromFormat('d/m/Y H:i', $data->data_inicio_evento) ?: new DateTime($data->data_inicio_evento);
                $fim    = DateTime::createFromFormat('d/m/Y H:i', $data->data_fim_evento) ?: new DateTime($data->data_fim_evento);

                if ($fim < $inicio) {
                    throw new Exception('A data de fim não pode ser anterior à data de início.');
                }
            }

            $is_update = !empty($data->id_evento);
            $user_id   = TSession::getValue('userid');
            
            $evento = new Evento($data->id_evento ?? null);
            $evento->fromArray((array) $data);

            if (!$is_update) {
                $evento->gerente_evento = $user_id;
            }
            
            $evento->atualizado_por = $user_id;
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

            $this->syncCoordenadores($evento, (array) ($data->coordenador_evento ?? []));
            $this->syncMonitores($evento, (array) ($data->monitor_evento ?? []));

            $data->id_evento = $evento->id_evento;
            $data->arte_evento = $evento->arte_evento;
            
            $this->form->setData($data);

            TTransaction::close();

            new TMessage('info', $is_update ? 'Atualizado com sucesso!' : 'Criado com sucesso!');

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
            $this->form->setData($this->form->getData());
        }
    }

    public function onEdit($param)
    {
        try
        {
            if (isset($param['key']))
            {
                TTransaction::open('teste');

                $evento = new Evento($param['key']);
                $data = (object) $evento->toArray();

                $data->coordenador_evento = $evento->get_coordenador_evento();
                $data->monitor_evento     = $evento->get_monitor_evento();

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

    private function syncCoordenadores(Evento $evento, array $ids): void
    {
        $repo = new TRepository('EventoCoordenador');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('id_evento', '=', $evento->id_evento));
        $repo->delete($criteria);

        $ids = array_unique(array_filter($ids));

        foreach ($ids as $userId)
        {
            $ec = new EventoCoordenador;
            $ec->id_evento = $evento->id_evento;
            $ec->id_usuario = (int) $userId;
            $ec->store();

            try {
                $group = new SystemGroup(7);
                $user = new SystemUser((int) $userId);
                if ($user->id) {
                    $group->addSystemUser($user);
                }
            } catch (Exception $e) {
                // Proteção contra falhas secundárias de grupo
            }
        }
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
            $m->id_evento = $evento->id_evento;
            $m->id_usuario = (int) $userId;
            $m->store();

            try {
                $group = new SystemGroup(6);
                $user = new SystemUser((int) $userId);
                if ($user->id) {
                    $group->addSystemUser($user);
                }
            } catch (Exception $e) {
                // Proteção contra falhas secundárias de grupo
            }
        }
    }
}