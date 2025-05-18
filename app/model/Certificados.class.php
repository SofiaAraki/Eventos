<?php

// class Certificados extends TRecord
// {
//     const TABLENAME = 'certificados';
//     const PRIMARYKEY= 'id_certificado';
//     const IDPOLICY = 'serial'; // {max, serial}

//     public function __construct($id_certificado = NULL, $callObjectLoad = TRUE)
//     {
//         parent::__construct($id_certificado, $callObjectLoad);
//         parent::addAttribute('data_emissao_certificado');
//         parent::addAttribute('titulo_certificado');
//         parent::addAttribute('id_usuario');
//         parent::addAttribute('id_evento');
        
//     }

//         // parent::addAttribute('descricao');
//         // parent::addAttribute('system_user_id');
//         // parent::addAttribute('bg_frente');
//         // parent::addAttribute('bg_verso');
//         // parent::addAttribute('mostra_verso');
//         // parent::addAttribute('margin_left');
//         // parent::addAttribute('margin_right');
//         // parent::addAttribute('orientacao_pagina');
    

//     public function get_evento()
//     {
//         $id_evento = Eventos::find($this->id_evento);
//         return $id_evento ? $id_evento->titulo_evento : '-';
//     }
    
//     public function get_usuario()
//     {
//         $user = SystemUser::find($this->id_usuario);
//         return $user ? $user->name : '-';
//     }

// }

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
        parent::addAttribute('descricao_certificado');
        parent::addAttribute('data_emissao_certificado');
        parent::addAttribute('bg_frente');
        parent::addAttribute('bg_verso');
        parent::addAttribute('orientacao_pagina');
        parent::addAttribute('mostra_verso');
        parent::addAttribute('margem_esquerda');
        parent::addAttribute('margem_direita');
        parent::addAttribute('carga_horaria_certificado');
    }

    public function get_evento()
    {
        $id_evento = Eventos::find($this->id_evento);
        return $id_evento ? $id_evento->titulo_evento : '-';
    }
}
