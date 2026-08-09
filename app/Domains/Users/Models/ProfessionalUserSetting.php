<?php

namespace App\Domains\Users\Models;

use App\Traits\SerializesDatesInAppTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Configuración general del profesional (una fila por professional_user).
 *
 * Guarda los defaults que el frontend prefillea al crear eventos/schedules
 * (política de cancelación, duración de sesión, etc.). Se actualiza cuando
 * el profesional la edita explícitamente o cuando elige otra política al
 * guardar un evento ("lo último elegido" queda materializado acá).
 */
class ProfessionalUserSetting extends Model
{
    use SerializesDatesInAppTimezone;

    protected $table = 'professional_user_settings';

    protected $fillable = [
        'professional_user_id',
        'cancellation_notice_hours',
        'cancellation_fee_type',
        'cancellation_fee_value',
        'session_duration_minutes',
    ];

    protected $hidden = ['professional_user_id'];

    protected $casts = [
        'cancellation_notice_hours' => 'integer',
        'cancellation_fee_value' => 'float',
        'session_duration_minutes' => 'integer',
    ];

    public function professionalUser(): BelongsTo
    {
        return $this->belongsTo(ProfessionalUser::class);
    }
}
