<?php

class ViewRelatorioParticipantes extends TRecord
{
    const TABLENAME  = 'view_relatorio_participantes';
    const PRIMARYKEY = 'id_inscricao';
    const IDPOLICY   = 'max'; // ou 'serial'

    /**
     * Construtor da classe
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        
        // Atributos mapeados da View
        parent::addAttribute('id_evento');
        parent::addAttribute('id_usuario');
        parent::addAttribute('status_inscricao');
        parent::addAttribute('usuario_name');
        parent::addAttribute('usuario_email');
        parent::addAttribute('status_label');
        parent::addAttribute('esta_presente');
        parent::addAttribute('ja_saiu');
        parent::addAttribute('total_minutos');
        parent::addAttribute('ultima_entrada');
        parent::addAttribute('ultima_saida');
        parent::addAttribute('dia_evento');
        parent::addAttribute('responsavel_nome');
    }
}