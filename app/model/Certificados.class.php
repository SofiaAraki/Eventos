<?php
class Certificados extends TRecord
{
    const TABLENAME = 'certificados';
    const PRIMARYKEY= 'id_certificado';
    const IDPOLICY = 'serial';

    public function __construct($id_certificado = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id_certificado, $callObjectLoad);
        parent::addAttribute('id_evento');
        parent::addAttribute('titulo_certificado');
        parent::addAttribute('data_emissao_certificado');
        parent::addAttribute('bg_frente');
        parent::addAttribute('carga_horaria_certificado');
        parent::addAttribute('tipo_certificado');
    }

    public function get_evento()
    {
        $id_evento = Eventos::find($this->id_evento);
        return $id_evento ? $id_evento->titulo_evento : '-';
    }
}
