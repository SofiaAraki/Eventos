<?php
class Inscricao extends TRecord
{
    const TABLENAME  = 'inscricao';
    const PRIMARYKEY = 'id_inscricao';
    const IDPOLICY   = 'serial';

    public function __construct($id = null, $callObjectLoad = true)
    {
        parent::__construct($id, $callObjectLoad);

        parent::addAttribute('id_usuario');
        parent::addAttribute('id_evento');
        parent::addAttribute('tipo_participacao');
        parent::addAttribute('data_inscricao');
        parent::addAttribute('status_inscricao');
        parent::addAttribute('cod_validador');
    }

    public function get_evento_name()
    {
        return Evento::find($this->id_evento)->titulo_evento ?? '-';
    }
    
    public function get_usuario_name()
    {
        $user = SystemUser::find($this->id_usuario);
        return $user ? $user->name : '-';
    }

    public function get_certificado_name()
    {
        return Certificado::where('id_evento', '=', $this->id_evento)
                        ->where('tipo_participacao', '=', $this->tipo_participacao)
                        ->first()->titulo_certificado ?? '-';
    }

    public function get_status_label()
    {
        return $this->status_inscricao == 1
            ? 'Confirmado'
            : 'Pendente';
    }

    public function get_permanencia_total()
    {
        return Presenca::getPermanenciaTotal($this->id_inscricao) . ' min';
    }

    public function get_ultima_presenca()
    {
        return Presenca::getUltimaPresenca($this->id_inscricao);
    }

    public function get_ultima_entrada()
    {
        $ultima = $this->get_ultima_presenca();

        if (!$ultima || empty($ultima->data_entrada)) {
            return '-';
        }

        return date('d/m/Y H:i', strtotime($ultima->data_entrada));
    }

    public function get_ultima_saida()
    {
        $ultima = $this->get_ultima_presenca();

        if (!$ultima) {
            return '-';
        }

        if (empty($ultima->data_saida)) {
            return 'NO EVENTO';
        }

        return date('d/m/Y H:i', strtotime($ultima->data_saida));
    }

    public function get_responsavel_nome()
    {
        $ultima = $this->get_ultima_presenca();

        if (!$ultima) {
            return '-';
        }

        return $ultima->responsavel_nome;
    }
}
