<?php
class TccService
{
    public static function salvar($data)
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
                $evento->status_evento = 0;
                $evento->store();

                $tcc->id_evento = $evento->id_evento;
            }

            $tcc->store();
            $tcc->setAutores($data->autores ?? []);
            $tcc->setBanca($data->banca ?? []);

            self::registrarInscricoesECertificados($tcc, $data);

            $id = $tcc->id_tcc;

            TTransaction::close();
            
            return $id;
        } catch (Exception $e) {
            TTransaction::rollback();
            throw $e;
        }
    }

    private static function registrarInscricoesECertificados($tcc, $data)
    {
        $usuarios = array_unique(array_merge(
            (array)($data->autores ?? []),
            [$data->id_orientador],
            (array)($data->banca ?? [])
        ));

        foreach ($usuarios as $id_usuario) {
            $tipo = self::getTipoParticipacao($id_usuario, $data);
            
            $inscricao = Inscricao::where('id_evento', '=', $tcc->id_evento)
                                  ->where('id_usuario', '=', $id_usuario)
                                  ->first() ?? new Inscricao();
            
            $inscricao->id_evento = $tcc->id_evento;
            $inscricao->id_usuario = $id_usuario;
            $inscricao->tipo_participacao = $tipo;
            $inscricao->status_inscricao = 1;
            $inscricao->data_inscricao = $inscricao->data_inscricao ?? date('Y-m-d H:i:s');
            $inscricao->store();

            $cert = Certificado::where('id_evento', '=', $tcc->id_evento)
                               ->where('tipo_participacao', '=', $tipo)
                               ->first();
            if (!$cert) {
                $cert = new Certificado();
                $cert->id_evento = $tcc->id_evento;
                $cert->tipo_participacao = $tipo;
                $cert->titulo_certificado = "Certificado de {$tipo}";
                $cert->data_emissao_certificado = $tcc->data_tcc;
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
