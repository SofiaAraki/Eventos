<?php

class ViewRelatorioParticipantes extends TRecord
{
    const TABLENAME  = 'view_relatorio_participantes';
    const PRIMARYKEY = 'id_inscricao';
    const IDPOLICY   = 'max';

    public function __construct($id = NULL,$callObjectLoad = TRUE)
    {
        parent::__construct($id,$callObjectLoad);
        
        parent::addAttribute('id_evento');
        parent::addAttribute('id_usuario');
        parent::addAttribute('status_inscricao');
        parent::addAttribute('tipo_participacao_codigo');
        parent::addAttribute('tipo_participacao_descricao');
        parent::addAttribute('usuario_name');
        parent::addAttribute('usuario_curso');
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

    public function get_permanencia_total()
    {
        $minutos = (int)$this->total_minutos;
        if ($minutos <= 0) {
            return '0 min';
        }
        
        $horas = floor($minutos / 60);
        $minRestantes =$minutos % 60;

        if ($horas > 0) {
            return "{$horas}h {$minRestantes}min";
        }

        return "{$minutos} min";
    }
}