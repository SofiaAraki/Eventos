<?php
class Registro extends TRecord
{
    const TABLENAME  = 'registro';
    const PRIMARYKEY = 'id_registro';
    const IDPOLICY   = 'serial';

    private $inscricao_obj;
    private $certificado_obj;

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('id_inscricao');
        parent::addAttribute('id_certificado');
        parent::addAttribute('descricao_certificado');
        parent::addAttribute('data_registro');
    }

    /**
     * Retorna o objeto Inscricao relacionado
     */
    public function get_inscricao()
    {
        if (empty($this->inscricao_obj))
        {
            $this->inscricao_obj = new Inscricao($this->id_inscricao);
        }
        return $this->inscricao_obj;
    }

    /**
     * Retorna o objeto Certificado relacionado
     */
    public function get_certificado()
    {
        if (empty($this->certificado_obj))
        {
            $this->certificado_obj = new Certificado($this->id_certificado);
        }
        return $this->certificado_obj;
    }

    public function get_nome()
    {
        $inscricao = $this->get_inscricao();
        return $inscricao->usuario->name ?? ($inscricao->usuario_name ?? '-');
    }

    public function get_evento_name()
    {
        $inscricao = $this->get_inscricao();
        return $inscricao->evento->titulo_evento ?? ($inscricao->evento_name ?? '-');
    }

    public function get_certificado_name()
    {
        return $this->get_certificado()->titulo_certificado ?? '-';
    }

    public function get_conteudo_final()
    {
        if (!empty($this->descricao_certificado))
        {
            return $this->descricao_certificado;
        }
        return $this->get_certificado()->descricao_certificado ?? '';
    }
}