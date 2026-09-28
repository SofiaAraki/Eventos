<?php
class TipoParticipacaoModalView extends TWindow
{
    protected $form;

    public function __construct($param = null)
    {
        parent::__construct();
        
        // Define 90% da tela no mobile, mas limita em 450px no PC
        parent::setSize(0.7, null);
        parent::setTitle('Tipo de Participação');

        // JS/CSS para manter o modal responsivo e centralizado
        TScript::create("
            $('.modal-dialog').has('#form_tipo_participacao').css({
                'max-width': '450px',
                'margin': '10px auto'
            });
        ");

        $this->form = new BootstrapFormBuilder('form_tipo_participacao');
        $this->form->setProperty('style', 'margin: 0; padding: 5px;');

        // Campo Oculto para ID do Evento
        $id_evento = new THidden('id_evento');
        $id_evento->setValue($param['id_evento'] ?? null);

        $tipo_participacao = new TCombo('tipo_participacao');
        $tipo_participacao->addItems([
            'aluno'       => 'Aluno',
            'monitor'     => 'Monitor',
            'organizador' => 'Organizador',
            'professor'   => 'Professor',
        ]);
        $tipo_participacao->addValidation('Tipo de Participação', new TRequiredValidator);
        $tipo_participacao->setDefaultOption('Selecione uma opção...');

        $this->form->addFields([$id_evento]);
        
        $row = $this->form->addFields(
            [new TLabel('Como deseja participar?', 'var(--bs-body-color)', 11, 'b')], 
            [$tipo_participacao]
        );
        $row->layout = ['col-12', 'col-12'];

        $btn = $this->form->addAction(
            'Confirmar Inscrição', 
            new TAction([$this, 'onConfirmar']), 
            'fa:check-circle'
        );
        $btn->class = 'btn btn-success w-100 py-2 fw-semibold';

        parent::add($this->form);
    }

    public static function onConfirmar($param)
    {
        try 
        {
            TTransaction::open('teste');

            $form = new BootstrapFormBuilder('form_tipo_participacao');
            $form->validate();

            $user_id           = (int) TSession::getValue('userid');
            $event_id          = (int) ($param['id_evento'] ?? 0);
            $tipo_participacao = $param['tipo_participacao'] ?? null;

            if (empty($event_id)) {
                throw new Exception('Evento inválido ou não informado.');
            }

            $inscrito = Inscricao::where('id_evento', '=', $event_id)
                                 ->where('id_usuario', '=', $user_id)
                                 ->first();

            if ($inscrito) {
                TTransaction::close();
                TWindow::closeWindow();
                new TMessage('warning', 'Você já está inscrito neste evento.');
                return;
            }

            // Grava a Nova Inscrição
            $inscricao = new Inscricao;
            $inscricao->id_evento         = $event_id;
            $inscricao->id_usuario        = $user_id;
            $inscricao->tipo_participacao = $tipo_participacao;
            $inscricao->data_inscricao    = date('Y-m-d H:i:s');
            $inscricao->status_inscricao  = 0;
            $inscricao->store();

            TTransaction::close();

            TWindow::closeWindow();
            TScript::create("Template.closeRightPanel();");
            
            new TMessage('info', 'Inscrição realizada com sucesso!');

        } 
        catch (Exception $e) 
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}