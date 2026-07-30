<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value', 'value_type', 'group', 'description'])]
class SystemParameter extends Model
{
    use HasUlids;

    protected $table = 'cn_system_parameters';
}
