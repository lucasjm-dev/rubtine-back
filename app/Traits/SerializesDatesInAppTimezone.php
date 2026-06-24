<?php

namespace App\Traits;

use DateTimeInterface;

/**
 * Serializa los campos de fecha en la hora local de la app (config app.timezone)
 * en lugar de convertirlos a UTC ISO-8601.
 *
 * Por defecto Eloquent serializa los campos casteados como datetime a UTC con
 * sufijo "Z" (ej: 15:00 BA -> 2026-06-23T18:00:00.000000Z), lo que provoca un
 * desfase de horas en el front. Con este trait el JSON devuelve la misma hora
 * de pared que se almacena (ej: "2026-06-23 15:00:00").
 */
trait SerializesDatesInAppTimezone
{
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
