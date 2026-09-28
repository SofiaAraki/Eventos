<?php
class Evento extends TRecord
{
    const TABLENAME = 'evento';
    const PRIMARYKEY= 'id_evento';
    const IDPOLICY = 'serial';

    public function __construct($id_evento = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id_evento, $callObjectLoad);
        parent::addAttribute('titulo_evento');
        parent::addAttribute('data_inicio_evento');
        parent::addAttribute('data_fim_evento');
        parent::addAttribute('local_evento');
        parent::addAttribute('descricao_evento');
        parent::addAttribute('status_evento');
        parent::addAttribute('gerente_evento');
        parent::addAttribute('atualizado_por');
        parent::addAttribute('data_atualizacao');
        parent::addAttribute('valor_evento');
        parent::addAttribute('status_aprovacao');
        parent::addAttribute('observacao_aprovacao');
        parent::addAttribute('arte_evento');
    }

    public function get_gerente_evento_name()
    {
        $user = SystemUser::find($this->gerente_evento);
        return $user ? $user->name : '-';
    }

    public function get_arte_evento_url()
    {
        $arte = $this->arte_evento;

        if (!empty($arte) && is_string($arte) && (strpos($arte, '{') !== false)) {
            $json = json_decode(urldecode($arte), true);
            $arte = $json['fileName'] ?? $json['newFile'] ?? '';
        }

        if (!empty($arte) && file_exists($arte)) {
            return $arte;
        }

        return 'favicon.png'; // Caminho do seu fallback
    }

    public function get_monitor_evento()
    {
        $monitores = Monitor::where('id_evento', '=', $this->id_evento)->load();
        $ids = [];
        if ($monitores) {
            foreach ($monitores as $monitor) {
                $ids[] = $monitor->id_usuario;
            }
        }
        return $ids;
    }

    public function get_coordenador_evento()
    {
        $coordenadores = EventoCoordenador::where('id_evento', '=', $this->id_evento)->load();
        $ids = [];
        if ($coordenadores) {
            foreach ($coordenadores as $coord) {
                $ids[] = $coord->id_usuario;
            }
        }
        return $ids;
    }
}
