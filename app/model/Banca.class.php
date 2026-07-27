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
        parent::addAttribute('id_banca_usuario');
    }

    public function get_usuario()
    {
        return new SystemUser($this->id_banca_usuario);
    }

    public function get_usuario_name()
    {
        $usuario = $this->get_usuario();
        return $usuario ? $usuario->name : '-';
    }
}