<?php
/**
 * StandardDataGridView Listing
 *
 * @version    1.0
 * @package    samples
 * @subpackage tutor
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    https://adiantiframework.com.br/license-tutor
 */
class CertificadosView extends TPage
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;

    use Adianti\Base\AdiantiStandardListTrait;

    public function __construct()
    {
        parent::__construct();

        $this->setDatabase('test');                // Banco de dados
        $this->setActiveRecord('Certificados');    // Active Record
        $this->addFilterField('titulo_certificado', 'like', 'titulo_certificado'); // Filtro
        $this->setDefaultOrder('id_certificado', 'asc');

        $this->buildForm();
        $this->buildDataGrid();
        $this->buildPage();
    }

    /**
     * Constrói o formulário de busca
     */
    private function buildForm()
    {
        $this->form = new BootstrapFormBuilder('form_search_Certificado');
        $this->form->setFormTitle('Gerenciamento de Certificados');

        $titulo_certificado = new TEntry('titulo_certificado');

        $this->form->addFields(
            [new TLabel('Modelo', 'red')],
            [$titulo_certificado]
        );

        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
        $this->form->addActionLink('Novo', new TAction(['CertificadosFormView', 'onClear']), 'fa:plus-circle green');
        $this->form->addActionLink('Limpar', new TAction([$this, 'clear']), 'fa:eraser red');

        $this->form->setData(TSession::getValue('CertificadosView_filter_data'));
    }

    /**
     * Constrói a grade de listagem
     */
    private function buildDataGrid()
    {
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = "100%";

        // Colunas
        $col_id         = new TDataGridColumn('id_certificado', 'ID', 'left', '5%');
        $col_modelo     = new TDataGridColumn('titulo_certificado', 'Modelo', 'center', '30%');
        $col_data       = new TDataGridColumn('data_emissao_certificado', 'Data de Emissão', 'center', '15%');
        $col_evento     = new TDataGridColumn('evento', 'Evento', 'center', '25%');
        $col_carga      = new TDataGridColumn('carga_horaria_certificado', 'Carga Horária', 'center', '10%');
        $col_tipo       = new TDataGridColumn('tipo_certificado', 'Tipo', 'center', '10%');

        // Adição das colunas
        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_modelo);
        $this->datagrid->addColumn($col_data);
        $this->datagrid->addColumn($col_evento);
        $this->datagrid->addColumn($col_carga);
        $this->datagrid->addColumn($col_tipo);

        // Transformer para data
        $col_data->setTransformer(
            function ($value) {
                return $value ? (new DateTime($value))->format('d/m/Y H:i') : '-';
            }
        );

        // Ordenar por data
        $col_data->setAction(new TAction([$this, 'onReload']), ['order' => 'data_emissao_certificado']);

        // Ações da grade
        $this->datagrid->addAction(new TDataGridAction(['CertificadosFormView', 'onEdit'], ['key' => '{id_certificado}']), 'Edit', 'far:edit blue');
        $this->datagrid->addAction(new TDataGridAction([$this, 'onDelete'], ['key' => '{id_certificado}']), 'Delete', 'far:trash-alt red');

        $this->datagrid->createModel();

        // Paginação
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
    }

    /**
     * Monta a estrutura da página
     */
    private function buildPage()
    {
        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->form);
        $vbox->add(TPanelGroup::pack('', $this->datagrid, $this->pageNavigation));
        parent::add($vbox);
    }

    /**
     * Limpa os filtros e recarrega a listagem
     */
    public function clear()
    {
        $this->clearFilters();
        $this->onReload();
    }

    /**
     * Editar um certificado
     */
    public function onEdit($param)
    {
        try {
            if (isset($param['key'])) {
                TTransaction::open('test');
                $certificado = new Certificados($param['key']);
                $this->form->setData($certificado); 
                TTransaction::close();
            } else {
                $this->form->clear(true);
            }
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}
