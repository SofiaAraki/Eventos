<?php
/**
 * DatagridBootstrapView
 *
 * @version    1.0
 * @package    samples
 * @subpackage tutor
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    https://adiantiframework.com.br/license-tutor
 */
class MinhasInscricoesView extends TStandardList
{
    protected $datagrid;

    public function __construct()
    {
        parent::__construct();

        parent::setDatabase('test');
        parent::setActiveRecord('Inscricoes');

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);

        $titulo_evento = new TDataGridColumn('evento', 'Evento', 'center', '40%');
        $data_inscricao   = new TDataGridColumn('data_inscricao', 'Data de Inscrição', 'center', '30%');
        $status_inscricao = new TDataGridColumn('status_inscricao', 'Presença', 'center', '20%');

        $this->datagrid->addColumn($titulo_evento);
        $this->datagrid->addColumn($data_inscricao);
        $this->datagrid->addColumn($status_inscricao);

        $status_inscricao->setTransformer(function ($value, $object, $row) {
            switch ($value) {
                case 0: return '<span class="label label-danger">Pendente</span>';
                case 1: return '<span class="label label-success">Confirmada</span>';
                default: return $value;
            }
        });

        // $action1 = new TDataGridAction([$this, 'EmitirCertificado']);
        // $action1->setUseButton(TRUE);
        // $this->datagrid->addAction($action1, 'Emitir Certificado', 'fa:certificate blue');
        $action1 = new TDataGridAction([$this, 'EmitirCertificado'], ['id_inscricao' => '{id_inscricao}']);
        $action1->setUseButton(TRUE);
        $action1->setField('id_inscricao'); // <<< ESSENCIAL para o Adianti saber qual registro acionar
        $this->datagrid->addAction($action1, 'Emitir Certificado', 'fa:certificate blue');
        
        $this->datagrid->createModel();

        $panel = new TPanelGroup('Minhas Inscrições');
        $panel->add($this->datagrid)->style = 'overflow-x:auto';
        $panel->addFooter('O Certificado é liberado após a confirmação da presença!');

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($panel);

        parent::add($vbox);
    }

    public function onReload($param = null)
    {
        try {
            TTransaction::open('test');

            $repository = new TRepository('Inscricoes');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('id_usuario', '=', TSession::getValue('userid')));

            $inscricoes = $repository->load($criteria);

            $this->datagrid->clear();

            if ($inscricoes) {
                foreach ($inscricoes as $inscricao) {
                    $this->datagrid->addItem($inscricao);
                }
            }

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function EmitirCertificado($param)
    {
        try {
            TTransaction::open('test');

            $inscricao = new Inscricoes($param['id_inscricao']);

            if (!$inscricao) {
                throw new Exception('Inscrição não encontrada!');
            }

            if ($inscricao->status_inscricao != 1) {
                throw new Exception("Certificado só disponível para inscrições confirmadas.");
            }

            // Usa o objeto TRecord diretamente
            $html = new AdiantiHTMLDocumentParser('app/resources/certificado.html', 'A4', 'landscape');
            $html->setMaster($inscricao); // <<< Corrigido aqui
            $html->process();

            $contents = $html->getContents();

            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($contents);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            $output_file = 'tmp/certificado.pdf';
            file_put_contents($output_file, $dompdf->output());

            parent::openFile($output_file);

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function show()
    {
        $this->onReload();
        parent::show();
    }
}
