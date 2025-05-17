<?php

class Certificados extends TRecord
{
    const TABLENAME = 'certificados';
    const PRIMARYKEY= 'id';
    const IDPOLICY = 'serial'; // {max, serial}
}