<?php
class Autores extends TRecord
{
    const TABLENAME = 'autores';
    const PRIMARYKEY= 'id_autor';
    const IDPOLICY = 'serial';

    public function __construct($id_autor = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id_autor, $callObjectLoad);
        parent::addAttribute('id_tcc');
        parent::addAttribute('autor');
    }

    
}