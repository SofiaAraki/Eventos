<?php
class SolicitacaoListCoordenador extends TPage
{
    protected $pageNavigation;
    protected $panel;

    use Adianti\Base\AdiantiStandardListTrait;

    public function __construct()
    {
        parent::__construct();

        $this->setDatabase('teste');
        $this->setActiveRecord('Evento');
        $this->setDefaultOrder('id_evento', 'desc');

        $this->panel = new TPanelGroup('Acompanhamento de Solicitações');
        $this->panel->addHeaderActionLink('Nova Solicitação', new TAction(['EventoFormCoordenador', 'onClear']), 'fa:plus-circle green');

        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
        $this->panel->addFooter($this->pageNavigation);

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->panel);

        parent::add($vbox);
    }

    public function onReload($param = NULL)
    {
        try {
            TTransaction::open('teste');

            $repository = new TRepository('Evento');
            $limit = 9;

            $loggedUser = TSession::getValue('userid');

            $repoCoord = new TRepository('EventoCoordenador');
            $criteriaCoord = new TCriteria;
            $criteriaCoord->add(new TFilter('id_usuario', '=', $loggedUser));
            $coordenacoes = $repoCoord->load($criteriaCoord);

            $idsEventosCoordenador = [];
            if ($coordenacoes) {
                foreach ($coordenacoes as $coord) {
                    $idsEventosCoordenador[] = $coord->id_evento;
                }
            }

            $criteriaUser = new TCriteria;
            $criteriaUser->add(new TFilter('gerente_evento', '=', $loggedUser));

            if (!empty($idsEventosCoordenador)) {
                $criteriaUser->add(new TFilter('id_evento', 'in', $idsEventosCoordenador), TExpression::OR_OPERATOR);
            }

            $criteria = new TCriteria;
            $param['order']     = $param['order'] ?? 'id_evento';
            $param['direction'] = $param['direction'] ?? 'desc';
            
            $criteria->setProperties($param);
            $criteria->setProperty('limit', $limit);
            
            $criteria->add($criteriaUser);

            $objects = $repository->load($criteria);

            $html = new THtmlRenderer('app/resources/card_evento_coordenador.html');

            if ($objects) {
                $html->enableSection('main');

                $items = [];
                foreach ($objects as $evento) {
                    $arte = method_exists($evento, 'get_arte_evento_url') ? $evento->get_arte_evento_url() : 'favicon.png';
                    $dataInicio = $evento->data_inicio_evento ? (new DateTime($evento->data_inicio_evento))->format('d/m/Y H:i') : '-';

                    switch ((int) $evento->status_aprovacao) {
                        case 1:
                            $status_texto = 'Aprovado';
                            $status_class = 'bg-success text-white';
                            break;
                        case 2:
                            $status_texto = 'Rejeitado';
                            $status_class = 'bg-danger text-white';
                            break;
                        default:
                            $status_texto = 'Aguardando Análise';
                            $status_class = 'bg-warning text-dark';
                            break;
                    }

                    $items[] = [
                        'id_evento'           => $evento->id_evento,
                        'titulo_evento'       => $evento->titulo_evento,
                        'data_inicio'         => $dataInicio,
                        'arte'                => $arte,
                        'img_class'           => 'img-fluid',
                        'status_texto'        => $status_texto,
                        'status_class'        => $status_class,
                        'action_edit'         => "engine.php?class=EventoFormCoordenador&method=onEdit&key={$evento->id_evento}",
                        'action_certificados' => "engine.php?class=CertificadoFormCoordenador&method=onReload&id_evento={$evento->id_evento}",
                        'action_relatorio'    => "engine.php?class=SolicitacaoListCoordenador&method=onRelatorioEventoCoordenador&id_evento={$evento->id_evento}",
                        'action_delete'       => "engine.php?class=SolicitacaoListCoordenador&method=onDelete&key={$evento->id_evento}",
                        
                        'parecer' => !empty($evento->observacao_aprovacao) ? [
                            [ 'observacao_aprovacao' => $evento->observacao_aprovacao ]
                        ] : []
                    ];
                }

                $html->enableSection('eventos', $items, true);
            } else {
                $html->enableSection('main');
                $html->enableSection('empty');
            }

            $this->panel->add($html);

            $criteriaCount = new TCriteria;
            $criteriaCount->add($criteriaUser);
            $count = $repository->count($criteriaCount);

            $this->pageNavigation->setCount($count);
            $this->pageNavigation->setProperties($param);
            $this->pageNavigation->setLimit($limit);

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public static function onRelatorioEventoCoordenador($param)
    {
        try {
            if (!isset($param['id_evento'])) {
                throw new Exception('Evento não informado');
            }

            TApplication::loadPage('RelatorioEventoCoordenador', 'onReload', ['id_evento' => $param['id_evento']]);
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
}