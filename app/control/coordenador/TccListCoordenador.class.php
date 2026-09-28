<?php

class TccListCoordenador extends TPage
{
    protected $pageNavigation;
    protected $panel;

    use Adianti\Base\AdiantiStandardListTrait;

    public function __construct()
    {
        parent::__construct();

        $this->setDatabase('teste');
        $this->setActiveRecord('Tcc');
        $this->setDefaultOrder('id_tcc', 'desc');

        $this->panel = new TPanelGroup('Acompanhamento de Solicitações de TCC');
        $this->panel->addHeaderActionLink('Nova Solicitação', new TAction(['TccFormCoordenador', 'onClear']), 'fa:plus-circle green');

        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
        $this->panel->addFooter($this->pageNavigation);

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->panel);

        parent::add($vbox);
    }

    /**
     * Carrega e exibe os cards listando apenas os TCCs vinculados ao usuário logado
     */
    public function onReload($param = NULL)
    {
        try
        {
            TTransaction::open('teste');

            $loggedUser = TSession::getValue('userid');

            // 1. Busca os IDs dos eventos do usuário logado
            $repoEventos = new TRepository('Evento');
            $criteriaEvento = new TCriteria;
            $criteriaEvento->add(new TFilter('gerente_evento', '=', $loggedUser));
            $eventos = $repoEventos->load($criteriaEvento);

            $idsEventos = [];
            if ($eventos) {
                foreach ($eventos as $ev) {
                    $idsEventos[] = $ev->id_evento;
                }
            }

            $html = new THtmlRenderer('app/resources/card_tcc_coordenador.html');

            if (!empty($idsEventos))
            {
                $repository = new TRepository('Tcc');
                $limit = 9;

                $criteria = new TCriteria;
                $param['order']     = $param['order'] ?? 'id_tcc';
                $param['direction'] = $param['direction'] ?? 'desc';

                $criteria->setProperties($param);
                $criteria->setProperty('limit', $limit);

                // Filtra para trazer apenas os TCCs vinculados
                $criteria->add(new TFilter('id_evento', 'IN', $idsEventos));

                $objects = $repository->load($criteria);

                if ($objects) {
                    $html->enableSection('main');

                    $items = [];
                    foreach ($objects as $tcc) {
                        $dataDefesa = !empty($tcc->data_tcc) 
                            ? (new DateTime($tcc->data_tcc))->format('d/m/Y H:i') 
                            : '-';

                        // Mapeia classes de status idênticas às das outras abas
                        switch ((int) ($tcc->evento->status_aprovacao ?? 0)) {
                            case 1:
                                $status_texto = 'Aprovado';
                                $status_class = 'bg-success-subtle text-success border-success-subtle';
                                break;
                            case 2:
                                $status_texto = 'Rejeitado';
                                $status_class = 'bg-danger-subtle text-danger border-danger-subtle';
                                break;
                            default:
                                $status_texto = 'Aguardando Análise';
                                $status_class = 'bg-warning-subtle text-warning-emphasis border-warning-subtle';
                                break;
                        }

                        $items[] = [
                            'id_tcc'          => $tcc->id_tcc,
                            'titulo_tcc'      => $tcc->titulo_tcc ?? 'Sem título',
                            'orientador_name' => $tcc->orientador_name ?? 'Não informado',
                            'data_tcc'        => $dataDefesa,
                            'status_texto'    => $status_texto,
                            'status_class'    => $status_class,
                            'action_edit'     => "index.php?class=TccFormCoordenador&method=onEdit&key={$tcc->id_tcc}",
                        ];
                    }

                    $html->enableSection('tccs', $items, true);
                } else {
                    $html->enableSection('main');
                    $html->enableSection('empty');
                }

                // Configura paginação
                $criteria->resetProperties();
                $count = $repository->count($criteria);

                $this->pageNavigation->setCount($count);
                $this->pageNavigation->setProperties($param);
                $this->pageNavigation->setLimit($limit);
            }
            else
            {
                $html->enableSection('main');
                $html->enableSection('empty');
                $this->pageNavigation->setCount(0);
            }

            $this->panel->add($html);

            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}