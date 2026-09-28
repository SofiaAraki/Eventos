<?php
class EmissaoManual extends TRecord
{
    const TABLENAME  = 'emissao_manual';
    const PRIMARYKEY = 'id_emissao';
    const IDPOLICY   = 'serial';

    private $evento;
    private $coordenador;

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('id_evento');
        parent::addAttribute('nome_pessoa');
        parent::addAttribute('bg_frente_certificado');
        parent::addAttribute('descricao_certificado');
        parent::addAttribute('data_emissao');
        parent::addAttribute('coordenador_id');
    }

    public function get_evento()
    {
        if (empty($this->evento)) {
            $this->evento = new Evento($this->id_evento);
        }
        return $this->evento;
    }

    public function get_coordenador()
    {
        if (empty($this->coordenador) && !empty($this->coordenador_id)) {
            $this->coordenador = new SystemUser($this->coordenador_id);
        }
        return $this->coordenador;
    }
}