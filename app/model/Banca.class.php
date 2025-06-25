<?php
class Banca extends TRecord
{
    const TABLENAME = 'banca';
    const PRIMARYKEY= 'id_banca';
    const IDPOLICY = 'serial';

    public function __construct($id_banca = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id_banca, $callObjectLoad);
        parent::addAttribute('id_tcc');
        parent::addAttribute('banca');
    }

}