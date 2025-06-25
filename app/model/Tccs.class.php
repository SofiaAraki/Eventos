<?php
class Tccs extends TRecord
{
    const TABLENAME  = 'tccs';
    const PRIMARYKEY = 'id_tcc';
    const IDPOLICY   = 'serial';

    public function __construct($id_tcc = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id_tcc, $callObjectLoad);
        parent::addAttribute('titulo_tcc');
        parent::addAttribute('data_tcc');
        parent::addAttribute('id_orientador');
        parent::addAttribute('id_evento');
    }

    public function get_orientador()
    {
        $orientador = SystemUser::find($this->id_orientador);
        return $orientador ? $orientador->name : '-';
    }

    public function getAutores()
    {
        return Autores::where('id_tcc', '=', $this->id_tcc)
            ->getIndexedArray('autor', 'autor');
    }

    public function setAutores($ids)
    {
        Autores::where('id_tcc', '=', $this->id_tcc)->delete();

        if ($ids) {
            foreach ($ids as $autor_id) {
                $membro = new Autores;
                $membro->id_tcc = $this->id_tcc;
                $membro->autor = $autor_id;
                $membro->store();
            }
        }
    }

    public function getBanca()
    {
        return Banca::where('id_tcc', '=', $this->id_tcc)
            ->getIndexedArray('banca', 'banca');
    }

    public function setBanca($ids)
    {
        Banca::where('id_tcc', '=', $this->id_tcc)->delete();

        if ($ids) {
            foreach ($ids as $banca_id) {
                $membro = new Banca;
                $membro->id_tcc = $this->id_tcc;
                $membro->banca = $banca_id;
                $membro->store();
            }
        }
    }
}
