<?php
class Certificado extends TRecord
{
    const TABLENAME = 'certificado';
    const PRIMARYKEY= 'id_certificado';
    const IDPOLICY = 'serial';

    public function __construct($id_certificado = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id_certificado, $callObjectLoad);
        parent::addAttribute('id_evento');
        parent::addAttribute('titulo_certificado');
        parent::addAttribute('descricao_certificado');
        parent::addAttribute('data_emissao_certificado');
        parent::addAttribute('bg_frente_certificado');
        parent::addAttribute('carga_horaria_certificado');
        parent::addAttribute('tipo_participacao');
        parent::addAttribute('presenca_minima_certificado');
    }

    public function get_evento_name()
    {
        $evento = Evento::find($this->id_evento);
        return $evento ? $evento->titulo_evento : '-';
    }
}
