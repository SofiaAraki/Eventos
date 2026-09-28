<?php
class EventoCoordenador extends TRecord
{
    const TABLENAME  = 'evento_coordenador';
    const PRIMARYKEY = 'id_evento_coordenador';
    const IDPOLICY   = 'serial';

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('id_usuario');
        parent::addAttribute('id_evento');
    }
}