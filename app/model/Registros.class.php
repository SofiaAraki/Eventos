<?php
class Registros extends TRecord
{
    const TABLENAME = 'registros';
    const PRIMARYKEY= 'id_registro';
    const IDPOLICY = 'serial';

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('id_inscricao');
        parent::addAttribute('tipo_certificado');
        parent::addAttribute('descricao_certificado');
        parent::addAttribute('data_emissao');
    }

    public function get_nome()
    {
        if ($this->id_inscricao) {
            $inscricao = new Inscricoes($this->id_inscricao);
            return $inscricao->usuario;
        }
        return '-';
    }

    public function get_evento()
    {
        if ($this->id_inscricao) {
            $inscricao = new Inscricoes($this->id_inscricao);     // carrega a inscrição
            $evento = new Eventos($inscricao->id_evento);          // carrega o evento da inscrição
            return $evento->titulo_evento;
        }
        return '-';
    }

    public function get_certificado()
    {
        if ($this->id_inscricao) {
            $inscricao = new Inscricoes($this->id_inscricao); 

            // Busca o certificado que corresponde ao evento e tipo
            $certificado = Certificados::where('id_evento', '=', $inscricao->id_evento)
                                        ->where('tipo_certificado', '=', $this->tipo_certificado)
                                        ->first();

            return $certificado ? $certificado->titulo_certificado : '-';
        }

        return '-';
    }
}
