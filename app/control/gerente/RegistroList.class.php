<?php
class RegistroList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;
    
    use Adianti\Base\AdiantiStandardListTrait;

    public function __construct()
    {
        parent::__construct();
        
        $this->setDatabase('teste');
        $this->setActiveRecord('ViewRegistro');
        
        // Mapeamento dos campos do form com os campos na ViewRegistro
        $this->addFilterField('id_evento', '=', 'id_evento');
        $this->addFilterField('id_usuario', '=', 'id_usuario');
        $this->setDefaultOrder('id_registro', 'desc');

        $this->form = new BootstrapFormBuilder('form_search_Registro');
        $this->form->setFormTitle('Registros de Certificados Emitidos');
        
        // Configuração dos campos Autocomplete / UniqueSearch
        $id_evento = new TDBUniqueSearch('id_evento', 'teste', 'Evento', 'id_evento', 'titulo_evento');
        $id_evento->setMask('{titulo_evento}');
        
        $id_usuario = new TDBUniqueSearch('id_usuario', 'teste', 'SystemUser', 'id', 'name');
        $id_usuario->setMask('{name}');
        
        $this->form->addFields([new TLabel('Evento:')], [$id_evento]);
        $this->form->addFields([new TLabel('Usuário:')], [$id_usuario]);
                
        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
        $this->form->addActionLink('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
        
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        
        $this->datagrid->addColumn(new TDataGridColumn('id_registro', 'ID', 'left', '5%'));
        $this->datagrid->addColumn(new TDataGridColumn('certificado_name', 'Certificado', 'left', '25%')); 
        $this->datagrid->addColumn(new TDataGridColumn('evento_name', 'Evento', 'left', '25%'));      
        $this->datagrid->addColumn(new TDataGridColumn('usuario_name', 'Participante', 'left', '25%'));       
        
        $data_registro = new TDataGridColumn('data_registro', 'Emissão', 'center', '15%');
        $data_registro->setTransformer(fn($v) => !empty($v) ? date('d/m/Y H:i', strtotime($v)) : '-');
        $this->datagrid->addColumn($data_registro);
        
        $this->datagrid->addAction(new TDataGridAction(['RegistroForm', 'onEdit'], ['key' => '{id_registro}']), 'Editar', 'far:edit blue');
        $this->datagrid->addAction(new TDataGridAction([$this, 'onDelete'], ['key' => '{id_registro}']), 'Excluir', 'far:trash-alt red');
        
        $this->datagrid->createModel();
        
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
        
        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->form);
        $vbox->add(TPanelGroup::pack('', $this->datagrid, $this->pageNavigation));
        
        parent::add($vbox);
    }

    public function onClear()
    {
        $this->form->clear();
        TSession::setValue(__CLASS__ . '_filter_id_evento', NULL);
        TSession::setValue(__CLASS__ . '_filter_id_usuario', NULL);
        TSession::setValue('form_search_Registro_data', NULL);
        $this->onReload();
    }
}