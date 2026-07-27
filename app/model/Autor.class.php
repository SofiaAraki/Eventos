<?php
class Autor extends TRecord
{
    const TABLENAME  = 'autor';
    const PRIMARYKEY = 'id_autor';
    const IDPOLICY   = 'serial';

    public function __construct($id_autor = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id_autor, $callObjectLoad);
        parent::addAttribute('id_tcc');
        parent::addAttribute('id_autor_usuario');
    }

    public function get_usuario()
    {
        return new SystemUser($this->id_autor_usuario);
    }

    public function get_usuario_name()
    {
        $usuario = $this->get_usuario();
        return $usuario ? $usuario->name : '-';
    }
}