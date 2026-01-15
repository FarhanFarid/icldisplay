<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcedureIcl extends Model
{
    protected $connection = 'iclms';
    protected $table = 'procedure_icl';

    public function prerecovery()
    {
        return $this->hasOne(PICLPreRecoveryChecklist::class, 'picl_id', 'id');
    }

    public function postrecovery()
    {
        return $this->hasOne(PICLPostRecoveryChecklist::class, 'picl_id', 'id');
    }


}
