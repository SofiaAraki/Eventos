<?php
class TccService
{
    public static function salvar($data, $eSolicitacaoCoordenador = false)
    {
        TTransaction::open('teste');
        try {
            $novo = empty($data->id_tcc);
            $tcc = $novo ? new Tcc : new Tcc($data->id_tcc);
            $tcc->fromArray((array) $data);

            if ($novo) {
                $evento = new Evento();
                $evento->titulo_evento = $tcc->titulo_tcc;
                $evento->data_inicio_evento = $tcc->data_tcc;
                $evento->data_fim_evento = $tcc->data_tcc;
                $evento->gerente_evento = TSession::getValue('userid');
                
                $evento->status_evento    = 0;
                $evento->status_aprovacao = $eSolicitacaoCoordenador ? 0 : 1;
                $evento->store();

                $tcc->id_evento = $evento->id_evento;
            }

            $tcc->store();
            $tcc->setAutores($data->autores ?? []);
            $tcc->setBanca($data->banca ?? []);

            if (!$eSolicitacaoCoordenador) {
                self::gerarInscricoesECertificadosAprovados($tcc->id_evento, $data);
            }

            $id = $tcc->id_tcc;

            TTransaction::close();
            
            return $id;
        } catch (Exception $e) {
            TTransaction::rollback();
            throw $e;
        }
    }

    public static function gerarInscricoesECertificadosAprovados($id_evento, $data = null)
    {
        $evento = new Evento($id_evento);

        if (!$data) {
            $tcc = Tcc::where('id_evento', '=', $id_evento)->first();
            if (!$tcc) return;

            $data = new stdClass();
            $data->id_orientador = $tcc->id_orientador;
            
            $data->autores = [];
            foreach ($tcc->get_autores() as $autor) {
                $data->autores[] = $autor->id_autor_usuario;
            }

            $data->banca = [];
            foreach ($tcc->get_banca() as $membro) {
                $data->banca[] = $membro->id_banca_usuario;
            }
        }

        $usuarios = array_unique(array_merge(
            (array)($data->autores ?? []),
            [$data->id_orientador],
            (array)($data->banca ?? [])
        ));

        foreach ($usuarios as $id_usuario) {
            if (!$id_usuario) continue;

            $tipo = self::getTipoParticipacao($id_usuario, $data);
            
            $inscricao = Inscricao::where('id_evento', '=', $id_evento)
                                  ->where('id_usuario', '=', $id_usuario)
                                  ->first() ?? new Inscricao();
            
            $inscricao->id_evento = $id_evento;
            $inscricao->id_usuario = $id_usuario;
            $inscricao->tipo_participacao = $tipo;
            $inscricao->status_inscricao = 1;
            $inscricao->data_inscricao = $inscricao->data_inscricao ?? date('Y-m-d H:i:s');
            $inscricao->store();

            $cert = Certificado::where('id_evento', '=', $id_evento)
                               ->where('tipo_participacao', '=', $tipo)
                               ->first();
            if (!$cert) {
                $cert = new Certificado();
                $cert->id_evento = $id_evento;
                $cert->tipo_participacao = $tipo;
                $cert->titulo_certificado = "Certificado de {$tipo}";
                $cert->data_emissao_certificado = $evento->data_inicio_evento ?? date('Y-m-d H:i:s');
                $cert->store();
            }
        }
    }

    private static function getTipoParticipacao($id_usuario, $data)
    {
        if (in_array($id_usuario, (array)($data->autores ?? []))) return 'autor';
        if ($id_usuario == $data->id_orientador) return 'orientador';
        if (in_array($id_usuario, (array)($data->banca ?? []))) return 'banca';
        return 'aluno';
    }
}