<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class Lezione extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lezioni';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'data',
        'ora_inizio',
        'ora_fine',
        'id_docente',
        'id_modulo',
        'aula',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'date',
        'ora_inizio' => 'datetime:H:i',
        'ora_fine' => 'datetime:H:i',
    ];

    /**
     * Get the docente that owns the lezione.
     */
    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'id_docente');
    }

    /**
     * Get the modulo that owns the lezione.
     */
    public function modulo(): BelongsTo
    {
        return $this->belongsTo(Modulo::class, 'id_modulo');
    }

    /**
     * Get the ore_lezione attribute (calculated).
     */
    protected function oreLezione(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->ora_inizio || !$this->ora_fine) {
                    return 0;
                }
                
                $inizio = Carbon::parse($this->ora_inizio);
                $fine = Carbon::parse($this->ora_fine);
                
                return round($fine->diffInMinutes($inizio) / 60, 2);
            }
        );
    }
}

