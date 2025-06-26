<?php
/**
 * Template View pattern implementation
 *
 * @version    1.0
 * @package    samples
 * @subpackage tutor
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    https://adiantiframework.com.br/license-tutor
 */
class EmitirCertificados extends TPage
{
    public function __construct($param)
    {
        parent::__construct();

        try {
            TTransaction::open('test');

            $inscricao = new Inscricoes($param['id_inscricao']);
            $evento    = new Eventos($inscricao->id_evento);

            // Obtém o certificado-modelo correto
            $certificadoModelo = Certificados::where('id_evento', '=', $evento->id_evento)
                ->where('tipo_certificado', '=', $inscricao->tipo_participacao)
                ->first();

            if (!$certificadoModelo) {
                throw new Exception('Não há certificado-modelo para o tipo de participação.');
            }

            // Gera o texto conforme tipo
            $texto = $this->gerarTextoCertificado(
                $inscricao->tipo_participacao,
                $certificadoModelo,
                $evento,
                $inscricao
            );

            // Procura o registro existente
            $registro = Registros::where('id_inscricao', '=', $inscricao->id_inscricao)
                ->where('tipo_certificado', '=', $inscricao->tipo_participacao)
                ->first() ?: new Registros;

            // Registra e gera o PDF
            $registro->id_inscricao          = $inscricao->id_inscricao;
            $registro->tipo_certificado      = $inscricao->tipo_participacao;
            $registro->descricao_certificado = $texto;
            $registro->data_emissao          = date('Y-m-d H:i:s');
            $registro->store();

            $this->gerarPdfCertificado($texto, $registro->id_registro, $certificadoModelo);

            TTransaction::close();

        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    /**
     * Monta o texto do certificado
     */
    private function gerarTextoCertificado(string $tipo, Certificados $certificado, Eventos $evento, Inscricoes $inscricao): string
    {
        $user = new SystemUser($inscricao->id_usuario);

        switch ($tipo) {
            case 'autor':
                $tcc     = $this->getTccByEvento($evento->id_evento);
                $autores = $this->getUsuariosNomes($tcc->getAutores());
                $orientador = (new SystemUser($tcc->id_orientador))->name;
                $banca   = $this->getUsuariosNomes($tcc->getBanca());
                return "A instituição, pelo presente, certifica que <strong>{$user->name}</strong> apresentou seu Trabalho de Graduação intitulado <strong>\"{$tcc->titulo_tcc}\"</strong> defendido em <strong>" . $this->formatarDataExtenso($evento->data_inicio_evento) . "</strong> orientado por <strong>{$orientador}</strong>, avaliado pelos membros da banca <strong>" . implode(', ', $banca) . "</strong><br><br><br>Ituverava, " .$this->formatarDataExtenso($certificado->data_emissao_certificado) . ".";
            case 'orientador':
                $tcc     = $this->getTccByEvento($evento->id_evento);
                $autores = $this->getUsuariosNomes($tcc->getAutores());
                $banca   = $this->getUsuariosNomes($tcc->getBanca());
                return "A instituição, pelo presente, certifica que <strong>{$user->name}</strong> orientou o acadêmico <strong>" . implode(', ', $autores) . "</strong> em seu Trabalho de Graduação intitulado <strong>\"{$tcc->titulo_tcc}\"</strong> defendido em <strong>" . $this->formatarDataExtenso($evento->data_inicio_evento) . "</strong>, tendo como membros examinadores <strong>" . implode(', ', $banca) . "</strong><br><br><br>Ituverava, " .$this->formatarDataExtenso($certificado->data_emissao_certificado) . ".";
            case 'banca':
                $tcc     = $this->getTccByEvento($evento->id_evento);
                $autores = $this->getUsuariosNomes($tcc->getAutores());
                $banca   = $this->getUsuariosNomes($tcc->getBanca());
                return "A instituição, pelo presente, certifica que <strong>{$user->name}</strong> foi membro efetivo da Banca Examinadora do acadêmico <strong>" . implode(', ', $autores) . "</strong> em seu Trabalho de Graduação intitulado <strong>\"{$tcc->titulo_tcc}\"</strong> defendido em <strong>" . $this->formatarDataExtenso($evento->data_inicio_evento) . "</strong>, tendo como membros examinadores <strong>" . implode(', ', $banca) . "</strong><br><br><br>Ituverava, " .$this->formatarDataExtenso($certificado->data_emissao_certificado) . ".";
            case 'palestrate':
                return "A instituição, pelo presente, certifica que {$user->name}, Ministrou a palestra  \"{$evento->titulo_evento}\", {$this->formatarPeriodoEvento($evento->data_inicio_evento, $evento->data_fim_evento)}, com carga horária equivalente a {$certificado->carga_horaria_certificado} horas.<br><br><br><br>Ituverava, {$this->formatarDataExtenso($certificado->data_emissao_certificado)}.";
            default:
                // certificado "aluno" genérico
                return "A instituição, pelo presente, certifica que <strong>{$user->name}</strong> portador(a) do <strong>RG: {$user->rg}</strong>, participou do evento <strong>\"{$evento->titulo_evento}\"</strong>, {$this->formatarPeriodoEvento($evento->data_inicio_evento, $evento->data_fim_evento)}, com carga horária equivalente a {$certificado->carga_horaria_certificado} horas.<br><br><br>Ituverava, {$this->formatarDataExtenso($certificado->data_emissao_certificado)}.";
        }
    }

    /**
     * Busca o TCC vinculado ao evento
     */
    private function getTccByEvento(int $id_evento): ?Tccs
    {
        return Tccs::where('id_evento', '=', $id_evento)->first();
    }

    /**
     * Helper que retorna nomes dos usuários
     */
    private function getUsuariosNomes(array $ids): array
    {
        return array_map(fn($id) => (new SystemUser($id))->name, $ids);
    }

    private function formatarDataExtenso($date)
    {
        if (!$date) return '';
        $date = new DateTime($date);
        $meses = [1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',5=>'maio',6=>'junho',7=>'julho',8=>'agosto',9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'];
        return $date->format('d') . " de " . $meses[(int)$date->format('m')] . " de " . $date->format('Y');
    }

    private function formatarPeriodoEvento($inicio, $fim)
    {
        $inicio_ext = $this->formatarDataExtenso($inicio);
        $fim_ext = $this->formatarDataExtenso($fim);
        return ($inicio_ext === $fim_ext) ? "realizado em {$inicio_ext}" : "realizado de {$inicio_ext} até {$fim_ext}";
    }

    private function gerarPdfCertificado(string $texto, int $registro_id, Certificados $certificado)
    {   
        $bg_path = 'app/images/certificados/' . ($certificado->bg_frente ?? 'fundacao.png');
        $bg_full_path = getcwd() . '/' . $bg_path;

        $html = "<html><head><meta charset='utf-8'></head><body>";
        $html .= "<div style='position: relative; width: 100%; height: 100%; font-family: Arial;'>";

        // Verifica se o fundo existe e adiciona como imagem de fundo
        if (file_exists($bg_full_path)) {
            $bg_data = base64_encode(file_get_contents($bg_full_path));
            $bg_src = 'data:image/png;base64,' . $bg_data;
            $html .= "<img src='$bg_src' style='position: absolute; width: 100%; height: 100%; z-index: 0;'>"; 
        }

        // Conteúdo do certificado
        $html .= "<div style='position: relative; text-align: center; padding: 100px 100px; z-index: 1;'>";
        $html .= "<h1 style='font-size: 50px; margin: 50px 30px auto;'>CERTIFICADO</h1><br><br>";
        $html .= "<p style='font-size: 25px; line-height: 1.6;'>$texto</p>";
        $html .= "</div></div></body></html>";

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $file_path = 'tmp/document.pdf';
        file_put_contents($file_path, $dompdf->output());

        $window = TWindow::create('Certificado', 0.6, 0.8);
        $object = new TElement('object');
        $object->data = "download.php?file=$file_path";
        $object->type = 'application/pdf';
        $object->style = "width: 100%; height: calc(100% - 10px)";
        $window->add($object);
        $window->show();
    }
}
