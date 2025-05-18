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
class MeusCertificadosView extends TStandardList
{
    protected $datagrid;

    public function __construct()
    {
        parent::__construct();

        parent::setDatabase('test');
        parent::setActiveRecord('Certificados');

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);

        $titulo_evento = new TDataGridColumn('titulo_evento', 'Evento', 'left', '100%');

        $this->datagrid->addColumn($titulo_evento);

        $action1 = new TDataGridAction([$this, 'onView'], ['titulo_evento'=>'{titulo_evento}']);
        $action1->setUseButton(TRUE);
        $this->datagrid->addAction($action1, 'Emitir Certificado', 'fa:certificate blue');

        $this->datagrid->createModel();

        $panel = new TPanelGroup('Meus Certificados');
        $panel->add($this->datagrid)->style = 'overflow-x:auto';
        $panel->addFooter('Baixe já seu Certificado!');

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

            $repository = new TRepository('Eventos');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('status_evento', '=', 1));

            $eventos = $repository->load($criteria);

            $this->datagrid->clear();

            if ($eventos) {
                foreach ($eventos as $evento) {
                    $this->datagrid->addItem($evento);
                }
            }

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onView($param)
    {
        $titulo_evento = $param['titulo_evento'];
        new TMessage('info', "Você foi inscrito em <b>$titulo_evento</b>");
    }

    public function show()
    {
        $this->onReload();
        parent::show();
    }
}
