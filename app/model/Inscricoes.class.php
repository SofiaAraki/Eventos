<?php

class Inscricoes extends TRecord
{
    const TABLENAME  = 'inscricoes';
    const PRIMARYKEY = 'id_inscricao';
    const IDPOLICY   = 'serial';

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('data_inscricao');
        parent::addAttribute('id_usuario');
        parent::addAttribute('id_evento');
        parent::addAttribute('status_inscricao');
    }

    public function get_status_inscricao_nome()
    {
        return $this->status_inscricao == 1 ? 'Confirmada' : 'Pendente';
    }

    public function get_evento()
    {
        $id_evento = Eventos::find($this->id_evento);
        return $id_evento ? $id_evento->titulo_evento : '-';
    }
    
    public function get_usuario()
    {
        $user = SystemUser::find($this->id_usuario);
        return $user ? $user->name : '-';
    }
}
    // public function get_gerente_evento_name()
    // {
    //     $user = SystemUser::find($this->gerente_evento);
    //     return $user ? $user->name : '-';
    // }


