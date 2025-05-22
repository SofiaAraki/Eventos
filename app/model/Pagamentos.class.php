<?php
class Pagamentos extends TRecord
{
    const TABLENAME  = 'pagamentos';
    const PRIMARYKEY = 'id_pagamento';
    const IDPOLICY   = 'serial';

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('id_inscricao');
        parent::addAttribute('status_pagamento');
        parent::addAttribute('data_pagamento');
    }

    public function get_valor_evento()
    {
        $inscricao = Inscricoes::find($this->id_inscricao);
        if ($inscricao && $inscricao->id_evento)
        {
            $evento = Eventos::find($inscricao->id_evento);
            return $evento ? $evento->valor_evento : '-';
        }
        return '-';
    }

    public function get_evento()
    {
        $inscricao = Inscricoes::find($this->id_inscricao);
        if ($inscricao && $inscricao->id_evento)
        {
            $evento = Eventos::find($inscricao->id_evento);
            return $evento ? $evento->titulo_evento : '-';
        }
        return '-';
    }

    public function get_usuario()
    {
        $inscricao = Inscricoes::find($this->id_inscricao);
        if ($inscricao && $inscricao->id_usuario)
        {
            $usuario = SystemUser::find($inscricao->id_usuario);
            return $usuario ? $usuario->name : '-';
        }
        return '-';
    }

    
}
