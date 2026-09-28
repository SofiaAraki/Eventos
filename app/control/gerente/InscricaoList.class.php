<?php
class InscricaoList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;

    use Adianti\Base\AdiantiStandardListTrait;

    public function __construct()
    {
        parent::__construct();

        $this->setDatabase('teste');
        $this->setActiveRecord('Inscricao');
        $this->addFilterField('id_usuario', '=', 'id_usuario');
        $this->addFilterField('id_evento', '=', 'id_evento');
        $this->addFilterField('status_inscricao', '=', 'status_inscricao');
        $this->setDefaultOrder('id_inscricao', 'desc');

        $this->form = new BootstrapFormBuilder('form_search_Inscricao');
        $this->form->setFormTitle('Gerenciamento de Inscrições');

        $id_evento = new TDBUniqueSearch('id_evento', 'teste', 'Evento', 'id_evento', 'titulo_evento');
        $id_evento->SetSize('80%');
        $id_usuario = new TDBUniqueSearch('id_usuario', 'teste', 'SystemUser', 'id', 'name');
        $id_usuario->SetSize('80%');
        $status_inscricao = new TCombo('status_inscricao');
        $status_inscricao->SetSize('80%');
        $status_inscricao->addItems([
            '1' => 'Confirmado',
            '0' => 'Pendente'
        ]);

        $this->form->addFields([new TLabel('Evento:', 'red')], [$id_evento]);
        $this->form->addFields([new TLabel('Usuário:', 'red')], [$id_usuario]);
        $this->form->addFields([new TLabel('Status:', 'red')], [$status_inscricao]);

        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
        $this->form->addActionLink('Novo', new TAction(['InscricaoForm', 'onClear']), 'fa:plus-circle green');
        $this->form->addActionLink('Limpar', new TAction([$this, 'clear']), 'fa:eraser red');
        
        $this->form->setData(TSession::getValue('InscricaoList_filter_data'));

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->width = '100%';

        $col_id_inscricao = new TDataGridColumn('id_inscricao', 'ID', 'left', '5%');
        $col_tipo_participacao = new TDataGridColumn('tipo_participacao', 'Tipo', 'left', '5%');
        $col_usuario = new TDataGridColumn('usuario_name', 'Nome', 'left', '30%');
        $col_evento = new TDataGridColumn('evento_name', 'Evento', 'left', '30%');
        $col_data_inscricao = new TDataGridColumn('data_inscricao', 'Data de Inscrição', 'center', '15%');
        $col_status_inscricao = new TDataGridColumn('status_inscricao', 'Status', 'center', '10%');

        $this->datagrid->addColumn($col_id_inscricao);
        $this->datagrid->addColumn($col_tipo_participacao);
        $this->datagrid->addColumn($col_usuario);
        $this->datagrid->addColumn($col_evento);
        $this->datagrid->addColumn($col_data_inscricao);
        $this->datagrid->addColumn($col_status_inscricao);

        $col_data_inscricao->setTransformer(fn($v) => $v ? (new DateTime($v))->format('d/m/Y H:i') : '-');

        $col_status_inscricao->setTransformer( function($value, $object, $row, $cell) {

            $cell->setProperty('onclick', 'event.stopPropagation();');

            $label = ((int) $value === 1) ? 'Confirmada' : 'Pendente';
            $color = ((int) $value === 1) ? '#28a745' : '#dc3545';

            $dropdown = new TDropDown($label, '');
            $dropdown->getButton()->style .= ';color:white;border-radius:5px;background:'.$color;
            
            $params = [
                'id_inscricao' => $object->id_inscricao,
                'novo_status' => ($object->status_inscricao == 1) ? 0 : 1,
                'offset' => $_REQUEST['offset'] ?? 0,
                'limit' => $_REQUEST['limit'] ?? 10,
                'page' => $_REQUEST['page'] ?? 1,
                'first_page' => $_REQUEST['first_page'] ?? 1,
                'register_state' => 'false'
            ];
                
            $dropdown->addAction( 
                ($object->status_inscricao == 1) ? 'Pendente' : 'Confirmada',
                new TAction([$this, 'onChangeStatus'], $params ),
            );
            return $dropdown;
        });

        $col_id_inscricao->setAction(new TAction([$this, 'onReload']), ['order' => 'id_inscricao']);
        $col_data_inscricao->setAction(new TAction([$this, 'onReload']), ['order' => 'data_inscricao']);

        $action1 = new TDataGridAction(['InscricaoForm', 'onEdit'], ['id_inscricao' => '{id_inscricao}']);
        $action2 = new TDataGridAction([$this, 'onDelete'], ['id_inscricao' => '{id_inscricao}']);

        $this->datagrid->addAction($action1, 'Edit', 'far:edit blue');
        $this->datagrid->addAction($action2, 'Delete', 'far:trash-alt red');

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

    public function clear()
    {
        $this->clearFilters();
        $this->onReload();
    }

    public function onChangeStatus($param)
    {
        try {
            TTransaction::open('teste');
            
            $id = $param['id_inscricao'];
            $novo_status = $param['novo_status'];

            $inscricao = new Inscricao($id);
            $inscricao->status_inscricao = $novo_status;
            $inscricao->store();

            TTransaction::close();
            $this->onReload($param);
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}