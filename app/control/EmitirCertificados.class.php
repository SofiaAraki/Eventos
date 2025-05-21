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

            // Busca dados de inscrição e evento
            $inscricao = new Inscricoes($param['id_inscricao']);
            $evento = new Eventos($inscricao->id_evento);

            // Busca modelo de certificado associado ao evento
            $certificado = Certificados::where('id_evento', '=', $evento->id_evento)->first();

            if (!$certificado) {
                throw new Exception("Modelo de certificado não encontrado.");
            }

            function formatar_data_extenso($data_emissao_certificado)
            {
                if (!$data_emissao_certificado) {
                    return '';
                }

                try {
                    $data_emissao_certificado = new DateTime($data_emissao_certificado);
                    $meses = [
                        1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
                        5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
                        9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'
                    ];

                    $dia = $data_emissao_certificado->format('d');
                    $mes = $meses[(int)$data_emissao_certificado->format('m')];
                    $ano = $data_emissao_certificado->format('Y');

                    return "{$dia} de {$mes} de {$ano}";
                } catch (Exception $e) {
                    return '';
                }
            }

            // Substituições dinâmicas no texto
            $texto = $certificado->descricao_certificado ?? '';
            $placeholders = [
                '{nome}' => htmlspecialchars($inscricao->usuario ?? ''),
                '{rg}' => htmlspecialchars($inscricao->rg ?? ''),
                '{titulo_evento}' => htmlspecialchars($evento->titulo_evento ?? ''),
                '{data_inicio_evento}' => formatar_data_extenso($evento->data_inicio_evento ?? ''),
                '{carga_horaria_evento}' => $certificado->carga_horaria_certificado ?? '',
                '{data_emissao_certificado}' => '<br><br><br><br>Ituverava, ' .formatar_data_extenso($certificado->data_emissao_certificado ?? '')

            ];

            foreach ($placeholders as $chave => $valor) {
                $texto = str_replace($chave, $valor, $texto);
            }

            // Início do HTML
            $html = "<html><head><meta charset='utf-8'></head><body>";
            $html .= "<div style='position: relative; width: 80%; height: 100%; font-family: Arial; margin: auto;'>";
            $html .= "<div style='position:relative; text-align: center; z-index:1;'>"; // imagem de fundo
            $html .= "<h1 style='font-size: 50px; margin: 100px 30px auto;'>" . htmlspecialchars('CERTIFICADO') . "</h1>";
            $html .= "<p style='font-size: 25px; line-height: 1.6; margin-top: 120px;'>$texto</p>";
            $html .= "</div></div></body></html>";

            // Gerar PDF
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            // Salvar e abrir janela com PDF
            $file_path = 'tmp/document.pdf';
            file_put_contents($file_path, $dompdf->output());

            $window = TWindow::create('Certificado', 0.6, 0.8);
            $object = new TElement('object');
            $object->data  = 'download.php?file=' . $file_path;
            $object->type  = 'application/pdf';
            $object->style = "width: 100%; height:calc(100% - 10px)";
            $window->add($object);
            $window->show();

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
}
