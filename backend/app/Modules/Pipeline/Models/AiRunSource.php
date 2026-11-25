<?php

declare(strict_types=1);

namespace App\Modules\Pipeline\Models;

use Illuminate\Database\Eloquent\Model;

/** A chunk retrieved for an AI run, with its ranks (PROJECT1_PLAN.md §5.13). */
class AiRunSource extends Model
{
    public $timestamps = false;

    protected $table = 'ai_run_sources';

    protected $guarded = ['id'];
}
