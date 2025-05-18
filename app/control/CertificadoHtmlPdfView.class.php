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
class CertificadoHtmlPdfView extends TPage
{
    public function __construct($param)
    {
        parent::__construct();

        try {
            // Dados do certificado vindo de parâmetros (dinâmicos)
            $certificado = new stdClass;
            $certificado->nome = $param['nome'] ?? 'Nome do Participante';
            $certificado->rg = $param['rg'] ?? '0000000';
            $certificado->titulo_evento = $param['titulo_evento'] ?? 'Evento de Exemplo';
            $certificado->inicio = $param['inicio'] ?? '01/01/2024';
            $certificado->termino = $param['termino'] ?? '02/01/2024';
            $certificado->carga_horaria = $param['carga_horaria'] ?? '10';

            // Carrega o template e processa os dados
            $html = new AdiantiHTMLDocumentParser('app/resources/certificado.html', 'A4', 'landscape');
            $html->setMaster($certificado);
            $html->process();

            // Gera o conteúdo HTML final
            $contents = $html->getContents();

            // Gera PDF com Dompdf
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($contents);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            // Salva o PDF na pasta temporária
            $output_file = 'tmp/certificado.pdf';
            file_put_contents($output_file, $dompdf->output());

            // Abre o arquivo PDF
            parent::openFile($output_file);
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
}
