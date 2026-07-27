<?php
class Tcc extends TRecord
{
    const TABLENAME  = 'tcc';
    const PRIMARYKEY = 'id_tcc';
    const IDPOLICY   = 'serial';

    private $autores_obj;
    private $banca_obj;
    private $orientador_obj;
    private $evento_obj;

    public function __construct($id_tcc = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id_tcc, $callObjectLoad);
        parent::addAttribute('titulo_tcc');
        parent::addAttribute('data_tcc');
        parent::addAttribute('id_orientador');
        parent::addAttribute('id_evento');
    }

    public function get_evento()
    {
        if (empty($this->evento_obj))
        {
            $this->evento_obj = new Evento($this->id_evento);
        }
        return $this->evento_obj;
    }

    public function get_orientador()
    {
        if (!empty($this->id_orientador))
        {
            return new SystemUser($this->id_orientador);
        }
        return null;
    }

    public function get_orientador_name()
    {
        $orientador = $this->get_orientador();
        return $orientador ? $orientador->name : '-';
    }

    public function get_autores()
    {
        if (empty($this->autores_obj))
        {
            $this->autores_obj = Autor::where(
                'id_tcc',
                '=',
                $this->id_tcc
            )->load();
        }
        return $this->autores_obj;
    }

    public function get_banca()
    {
        if (empty($this->banca_obj))
        {
            $this->banca_obj = Banca::where(
                'id_tcc',
                '=',
                $this->id_tcc
            )->load();
        }
        return $this->banca_obj;
    }

    public function get_autores_names()
    {
        $nomes = [];

        if ($autores = $this->get_autores())
        {
            foreach ($autores as $autor)
            {
                $nomes[] = $autor->usuario_name;
            }
        }
        return implode(', ', $nomes);
    }

    public function get_banca_names()
    {
        $nomes = [];
        if ($banca = $this->get_banca())
        {
            foreach ($banca as $membro)
            {
                $nomes[] = $membro->usuario_name;
            }
        }
        return implode(', ', $nomes);
    }

    public function setAutores($ids)
    {
        Autor::where('id_tcc', '=', $this->id_tcc)->delete();

        $ids = array_unique((array) $ids);

        foreach ($ids as $autor_id)
        {
            if (!empty($autor_id))
            {
                $autor = new Autor;
                $autor->id_tcc = $this->id_tcc;
                $autor->id_autor_usuario = $autor_id;
                $autor->store();
            }
        }
    }

    public function setBanca($ids)
    {
        Banca::where('id_tcc', '=', $this->id_tcc)->delete();

        $ids = array_unique((array) $ids);

        foreach ($ids as $banca_id)
        {
            if (!empty($banca_id))
            {
                $membro = new Banca;
                $membro->id_tcc = $this->id_tcc;
                $membro->id_banca_usuario = $banca_id;
                $membro->store();
            }
        }
    }
}