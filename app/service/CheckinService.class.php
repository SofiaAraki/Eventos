<?php
class CheckinService
{
    public static function process($id_inscricao)
    {
        if (!$id_inscricao) {
            throw new Exception("ID da inscrição não identificado.");
        }

        TTransaction::open("teste");

        try {
            $inscricao = new Inscricao($id_inscricao);

            if (!$inscricao || empty($inscricao->id_inscricao)) {
                throw new Exception("Inscrição não encontrada.");
            }

            $nome_participante = self::getParticipanteName($inscricao);
            $agora = date('Y-m-d H:i:s');

            $presencaAberta = Presenca::where('id_inscricao', '=', $inscricao->id_inscricao)
                ->where('data_saida', 'IS', null ) 
                ->first();

            // Se não houver presença aberta, registra uma nova entrada
            if (!$presencaAberta) {
                $presenca = new Presenca();
                $presenca->id_inscricao    = $inscricao->id_inscricao;
                $presenca->data_entrada    = $agora;
                $presenca->responsavel     = TSession::getValue("userid") ?? 1;
                $presenca->store();

                TTransaction::close();

                return [
                    "success"      => true,
                    "participante" => $nome_participante,
                    "tipo"         => "ENTRADA",
                    "entrada"      => $agora,
                    "saida"        => null,
                    "permanencia"  => null
                ];
            }

            // Se houver presença aberta, registra a saída e calcula a permanência
            $entrada = new DateTime($presencaAberta->data_entrada);
            $saida   = new DateTime($agora);

            // Verifica se a entrada e saída são do mesmo dia
            // Se não for o mesmo dia, fecha a presença sem computar permanência (zero minutos de permanência)
            if ($entrada->format('Y-m-d') !== $saida->format('Y-m-d'))
            {
                $presencaAberta->data_saida      = $agora;
                $presencaAberta->permanencia_min = 0;
                $presencaAberta->store();

                TTransaction::close();

                return [
                    "success"      => false,
                    "participante" => $nome_participante,
                    "tipo"         => "INCONSISTENCIA",
                    "mensagem"     => "Registro de entrada pertence a outro dia. Permanência não computada."
                ];
            }

            $intervalo = $entrada->diff($saida);

            $permanencia_trecho = ($intervalo->days * 24 * 60) + ($intervalo->h * 60) + $intervalo->i;

            $presencaAberta->data_saida      = $agora;
            $presencaAberta->permanencia_min = $permanencia_trecho;
            $presencaAberta->store();

            self::verificarLiberacaoCertificado($inscricao);

            TTransaction::close();

            return [
                "success"      => true,
                "participante" => $nome_participante,
                "tipo"         => "SAIDA",
                "entrada"      => $presencaAberta->data_entrada,
                "saida"        => $agora,
                "permanencia"  => $permanencia_trecho
            ];

        } catch (Exception $e) {
            TTransaction::rollback();
            throw $e;
        }
    }

    private static function verificarLiberacaoCertificado(Inscricao $inscricao)
    {
        $presencaTotal = Presenca::where('id_inscricao', '=', $inscricao->id_inscricao)
            ->sumBy('permanencia_min');

        $certificado = Certificado::where('id_evento', '=', $inscricao->id_evento)
            ->where('tipo_participacao', '=', $inscricao->tipo_participacao)
            ->first();

        if ($certificado && $certificado->presenca_minima_certificado !== null && $presencaTotal >= $certificado->presenca_minima_certificado)
        {
            $inscricao->status_inscricao = 1;
            $inscricao->store();
        }
    }

    private static function getParticipanteName(Inscricao $inscricao)
    {
        try {
            if (isset($inscricao->usuario->name)) {
                return $inscricao->usuario->name;
            }
            
            TTransaction::open('teste');
            $user = new SystemUser($inscricao->id_usuario);
            $name = $user->name;
            TTransaction::close();
            
            return $name;
        } catch (Exception $e) {
            return "Participante #" . $inscricao->id_inscricao;
        }
    }
}