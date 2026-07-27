<?php
class Presenca extends TRecord
{
    const TABLENAME  = 'presenca';
    const PRIMARYKEY = 'id_presenca';
    const IDPOLICY   = 'serial';

    public function __construct($id = null, $callObjectLoad = true)
    {
        parent::__construct($id, $callObjectLoad);

        parent::addAttribute('id_inscricao');
        parent::addAttribute('data_entrada');
        parent::addAttribute('data_saida');
        parent::addAttribute('responsavel');
        parent::addAttribute('permanencia_min');
    }

    public function get_inscricao()
    {
        return new Inscricao($this->id_inscricao);
    }

    public function get_responsavel_nome()
    {
        $user = SystemUser::find($this->responsavel);
        return $user ? $user->name : '-';
    }

    public static function getUltimaPresenca($id_inscricao)
    {
        return self::where('id_inscricao', '=', $id_inscricao)
            ->orderBy('id_presenca', 'desc')
            ->first();
    }

    public static function getPermanenciaTotal($id_inscricao)
    {
        return self::where('id_inscricao', '=', $id_inscricao)
            ->sumBy('permanencia_min') ?? 0;
    }

    public static function countSaidasEvento($id_evento) // excluir
    {
        $ids = Inscricao::where('id_evento', '=', $id_evento)
            ->getIndexedArray('id_inscricao', 'id_inscricao');

        if (empty($ids)) {
            return 0;
        }

        return self::where('id_inscricao', 'in', array_values($ids))
            ->where('data_saida', 'is not', null)
            ->count();
    }

    public static function countPresentesAgora($id_evento)
    {
        $ids = Inscricao::where('id_evento', '=', $id_evento)->getIndexedArray('id_inscricao');
        if (empty($ids)) return 0;

        return self::where('id_inscricao', 'in', array_values($ids))
                   ->where('data_saida', 'is', null) // Entrada aberta
                   ->count();
    }

    public static function countPessoasQueSairam($id_evento)
    {
        $ids = Inscricao::where('id_evento', '=', $id_evento)->getIndexedArray('id_inscricao');
        if (empty($ids)) return 0;

        return self::where('id_inscricao', 'in', array_values($ids))
                   ->where('data_saida', 'is not', null)
                   ->select('id_inscricao')
                   ->count(); 
    }
}