<?php
class TiposParticipacao extends TRecord
{
    const TABLENAME  = 'tipos_participacao';
    const PRIMARYKEY = 'codigo';
    const IDPOLICY   = 'serial';

    public function __construct($id = null, $callObjectLoad = true)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('descricao');
    }
}