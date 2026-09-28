<?php
class EventosAbertosList extends TPage
{
    public function __construct()
    {
        parent::__construct();

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));

        try
        {
            TTransaction::open('teste');

            $eventos = Evento::where('status_evento', '=', '1')->get();
            
            $html = new THtmlRenderer('app/resources/card_evento_aberto.html');
            
            $items = [];
            foreach ($eventos as $evento)
            {
                $data_inicio = !empty($evento->data_inicio_evento) 
                    ? (new DateTime($evento->data_inicio_evento))->format('d/m/Y H:i') 
                    : 'A definir';

                $valor = ($evento->valor_evento > 0) 
                    ? 'R$ ' . number_format($evento->valor_evento, 2, ',', '.') 
                    : 'Gratuito';

                $arte = $evento->get_arte_evento_url();
                $isFallback = (empty($evento->arte_evento) || !file_exists($evento->arte_evento));

                $items[] = [
                    'id'            => $evento->id_evento,
                    'titulo'        => $evento->titulo_evento ?? 'Evento sem título',
                    'local'         => $evento->local_evento ?? 'Local não informado',
                    'data_inicio'   => $data_inicio,
                    'status_evento' => $evento->status_evento == '1' ? 'Inscrições Abertas' : 'Inativo',
                    'valor'         => $valor,
                    'badge_valor'   => ($evento->valor_evento > 0) ? 'bg-primary-subtle text-primary border-primary-subtle' : 'bg-success-subtle text-success border-success-subtle',
                    'arte'          => $arte,
                    'img_class'     => $isFallback ? 'img-fallback' : 'img-cover',  
                ];
            }

            $html->enableSection('main');

            if (empty($items)) 
            {
                $html->enableSection('sem_eventos');
            } 
            else 
            {
                $html->enableSection('eventos', $items, true);
            }

            $vbox->add($html);

            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }

        parent::add($vbox);
    }
}