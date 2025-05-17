<?php

class Eventos extends TRecord
{
    const TABLENAME = 'eventos';
    const PRIMARYKEY= 'id';
    const IDPOLICY = 'serial'; // {max, serial}
}