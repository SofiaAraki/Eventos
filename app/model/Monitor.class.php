<?php
class Monitor extends TRecord
{
    const TABLENAME = 'monitor';
    const PRIMARYKEY= 'id_monitor';
    const IDPOLICY = 'serial'; 

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('id_evento');
        parent::addAttribute('id_usuario');
    }
}