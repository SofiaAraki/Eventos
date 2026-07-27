<?php
class CertificadoService
{
    public static function getTexto(Inscricao $inscricao, Certificado $modelo, Evento $evento)
    {
        TTransaction::open('teste');
        $user = new SystemUser($inscricao->id_usuario);
        TTransaction::close();

        $tipo = $inscricao->tipo_participacao;
        $dataEvento = self::formatarPeriodoEvento($evento->data_inicio_evento, $evento->data_fim_evento);
        $dataEmissao = self::formatarDataExtenso($modelo->data_emissao_certificado);
        $dataTcc = self::formatarDataExtenso($evento->data_inicio_evento);

        // Garante a existência do código de validação
        if (empty($inscricao->cod_validador)) {
            $inscricao->cod_validador = QrCodeService::generateCertificadoHash($inscricao);
            $inscricao->store();
        }

        $textoBase = "";

        switch ($tipo) {
            case 'autor':
                $tcc = self::getTccByEvento($evento->id_evento);
                $orientador = $tcc->orientador_name;
                $banca = $tcc->banca_names;
                $textoBase = "A FAFRAM - Faculdade Dr. Francisco Maeda, pelo presente, certifica que <strong>{$user->name}</strong> apresentou seu Trabalho de Graduação intitulado <strong>\"{$tcc->titulo_tcc}\"</strong>, Defendido em {$dataTcc}, orientado por <strong>{$orientador}</strong>, avaliado pelos membros da banca <strong>{$banca}</strong>.<br><br>Ituverava, {$dataEmissao}.";
                break;
            case 'orientador':
                $tcc = self::getTccByEvento($evento->id_evento);
                $autores = $tcc->autores_names;
                $banca = $tcc->banca_names;
                $textoBase = "A FAFRAM - Faculdade Dr. Francisco Maeda, pelo presente, certifica que <strong>{$user->name}</strong> orientou o Trabalho de Graduação intitulado <strong>\"{$tcc->titulo_tcc}\"</strong> apresentado por <strong>{$autores}</strong>, Defendido em {$dataTcc}, tendo como membros examinadores <strong>{$banca}</strong>.<br><br>Ituverava, {$dataEmissao}.";
                break;
            case 'banca':
                $tcc = self::getTccByEvento($evento->id_evento);
                $autores = $tcc->autores_names;
                $orientador = $tcc->orientador_name;
                $textoBase = "A FAFRAM - Faculdade Dr. Francisco Maeda, pelo presente, certifica que <strong>{$user->name}</strong> participou como membro da banca examinadora do Trabalho de Graduação intitulado <strong>\"{$tcc->titulo_tcc}\"</strong> apresentado por <strong>{$autores}</strong>, orientado por <strong>{$orientador}</strong>, Defendido em {$dataTcc}.<br><br>Ituverava, {$dataEmissao}.";
                break;
            case 'palestrante':
                $textoBase = "A FAFRAM - Faculdade Dr. Francisco Maeda, pelo presente, certifica que <strong>{$user->name}</strong> Ministrou a palestra \"{$evento->titulo_evento}\", {$dataEvento}, com carga horária de {$modelo->carga_horaria_certificado} horas.<br><br>Ituverava, {$dataEmissao}.";
                break;
            case 'organizador':
                $textoBase = "A FAFRAM - Faculdade Dr. Francisco Maeda, pelo presente, certifica que <strong>{$user->name}</strong> participou da organização do evento <strong>\"{$evento->titulo_evento}\"</strong>, {$dataEvento}, com carga horária de {$modelo->carga_horaria_certificado} horas.<br><br>Ituverava, {$dataEmissao}.";
                break;
            default:
                $textoBase = "A FAFRAM - Faculdade Dr. Francisco Maeda, pelo presente, certifica que <strong>{$user->name}</strong> portador(a) do <strong>RG: {$user->rg}</strong>, participou do evento <strong>\"{$evento->titulo_evento}\"</strong>, {$dataEvento}, com carga horária de {$modelo->carga_horaria_certificado} horas.<br><br>Ituverava, {$dataEmissao}.";
                break;
        }

        return $textoBase;
    }

    private static function getTccByEvento($id_evento)
    {
        $tcc = Tcc::where('id_evento', '=', $id_evento)->first();
        if (!$tcc) throw new Exception("TCC não encontrado para este evento.");
        return $tcc;
    }

    private static function formatarDataExtenso($date)
    {
        if (!$date) return '';
        $date = new DateTime($date);
        $meses = [1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',5=>'maio',6=>'junho',7=>'julho',8=>'agosto',9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'];
        return $date->format('d') . " de " . $meses[(int)$date->format('m')] . " de " . $date->format('Y');
    }

    private static function formatarPeriodoEvento($inicio, $fim)
    {
        $inicio_ext = self::formatarDataExtenso($inicio);
        $fim_ext = self::formatarDataExtenso($fim);
        return ($inicio_ext === $fim_ext) ? "realizado em {$inicio_ext}" : "realizado de {$inicio_ext} até {$fim_ext}";
    }
}