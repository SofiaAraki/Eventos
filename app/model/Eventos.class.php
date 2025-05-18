<?php

class Eventos extends TRecord
{
    const TABLENAME = 'eventos';
    const PRIMARYKEY= 'id_evento';
    const IDPOLICY = 'serial'; // {max, serial}

    /**
     * Constructor method
     */
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
        parent::addAttribute('id_evento');
        
    }

    public function get_gerente_evento_name()
    {
        $user = SystemUser::find($this->gerente_evento);
        return $user ? $user->name : '-';
    }

}
