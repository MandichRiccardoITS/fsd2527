<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Modulo extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'moduli';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nome',
        'unita_formativa',
    ];

    /**
     * Get the lezioni for the modulo.
     */
    public function lezioni(): HasMany
    {
        return $this->hasMany(Lezione::class, 'id_modulo');
    }
}

