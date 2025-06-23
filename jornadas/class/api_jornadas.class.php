<?php

use Luracast\Restler\RestException;

class JornadasApi
{
    // Función para obtener la jornada más reciente por token
    private function getJornadaByToken($token)
    {
        global $db;
        $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "jornadas WHERE token = '" . $db->escape($token) . "' ORDER BY rowid DESC LIMIT 1";
        $resql = $db->query($sql);
        if ($resql && $db->num_rows($resql) > 0) {
            return $db->fetch_object($resql);
        } else {
            return null;
        }
    }

    /**
     * Iniciar jornada
     * @url POST /jornadas/start
     */
    public function start($fecha_inicio, $token)
    {
        global $db;

        if (empty($fecha_inicio) || empty($token)) {
            throw new RestException(400, "Parámetros incompletos");
        }

        $timestamp = strtotime($fecha_inicio);
        if ($timestamp === false) {
            throw new RestException(400, "Formato de fecha inválido");
        }
        $fecha_inicio = date("Y-m-d H:i:s", $timestamp);

        // Verificar si ya existe una jornada activa o pausada
        $jornada = $this->getJornadaByToken($token);
        if ($jornada && ($jornada->estado == 'activa' || $jornada->estado == 'parada')) {
            $tiempo_trabajo_actual = (int)$jornada->tiempo_trabajo;
            $tiempo_descanso_actual = (int)$jornada->tiempo_descanso;

            $now = time();

            if ($jornada->estado == 'activa') {
                // Calcular tiempo trabajado desde fecha_inicio
                $tiempo_trabajo_actual += ($now - strtotime($jornada->fecha_inicio));
            } elseif ($jornada->estado == 'parada' && !empty($jornada->fecha_pausa)) {
                // Calcular tiempo de descanso desde fecha_pausa
                $tiempo_descanso_actual += ($now - strtotime($jornada->fecha_pausa));
            }

            return [
                'success' => true,
                'message' => 'Ya hay una jornada en curso',
                'estado' => $jornada->estado,
                'fecha_inicio' => $jornada->fecha_inicio,
                'tiempo_trabajo' => $tiempo_trabajo_actual,
                'tiempo_descanso' => $tiempo_descanso_actual,
                'rowid' => $jornada->rowid
            ];
        }

        // Insertar nueva jornada
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "jornadas 
            (token, fecha_inicio, estado, fecha_fin, tiempo_descanso, tiempo_trabajo, fecha_pausa) 
            VALUES (
                '" . $db->escape($token) . "', 
                '" . $db->escape($fecha_inicio) . "', 
                'activa', 
                NULL, 
                0, 
                0, 
                NULL
            )";

        if ($db->query($sql)) {
            return ['success' => true, 'message' => 'Jornada iniciada'];
        } else {
            throw new RestException(500, "Error al iniciar jornada: " . $db->lasterror());
        }
    }



    /**
     * Pausar jornada (cambia estado a 'parada')
     * @url POST /jornadas/pause
     */
    public function pause($token)
    {
        global $db;

        if (empty($token)) {
            throw new RestException(400, "Parámetros incompletos");
        }

        $jornada = $this->getJornadaByToken($token);
        if (!$jornada) {
            throw new RestException(404, "Jornada no encontrada");
        }

        if ($jornada->estado != 'activa') {
            throw new RestException(400, "La jornada no está activa");
        }

        $now = date("Y-m-d H:i:s");

        // Calcular tiempo trabajo acumulado desde fecha_inicio o última activación
        // NOTA: para mayor precisión deberías guardar última activación; aquí usamos fecha_inicio para simplificar
        $ultimoInicio = $jornada->fecha_inicio;
        if (!empty($jornada->fecha_pausa)) {
            // Si hay fecha_pausa, significa que algo está mal, pero lo ignoramos
            $ultimoInicio = $jornada->fecha_pausa;
        }

        $segundosTrabajados = (strtotime($now) - strtotime($ultimoInicio)) + (int)$jornada->tiempo_trabajo;

        $sqlUpdate = "UPDATE " . MAIN_DB_PREFIX . "jornadas SET estado = 'parada', tiempo_trabajo = " . (int)$segundosTrabajados . ", fecha_pausa = '" . $db->escape($now) . "' WHERE rowid = " . (int)$jornada->rowid;

        if ($db->query($sqlUpdate)) {
            return ['success' => true, 'message' => 'Jornada pausada correctamente'];
        } else {
            throw new RestException(500, "Error al pausar jornada: " . $db->lasterror());
        }
    }

    /**
     * Activar jornada (cambia estado a 'activa')
     * @url POST /jornadas/activate
     */
    public function activate($token)
    {
        global $db;

        if (empty($token)) {
            throw new RestException(400, "Parámetros incompletos");
        }

        $jornada = $this->getJornadaByToken($token);
        if (!$jornada) {
            throw new RestException(404, "Jornada no encontrada");
        }

        if ($jornada->estado != 'parada') {
            throw new RestException(400, "La jornada no está en estado 'parada'");
        }

        $now = date("Y-m-d H:i:s");

        $tiempoDescanso = (int)$jornada->tiempo_descanso;
        if (!empty($jornada->fecha_pausa)) {
            $tiempoDescanso += (strtotime($now) - strtotime($jornada->fecha_pausa));
        }

        $sqlUpdate = "UPDATE " . MAIN_DB_PREFIX . "jornadas SET estado = 'activa', tiempo_descanso = " . (int)$tiempoDescanso . ", fecha_pausa = NULL, fecha_inicio = '" . $db->escape($now) . "' WHERE rowid = " . (int)$jornada->rowid;

        if ($db->query($sqlUpdate)) {
            return ['success' => true, 'message' => 'Jornada activada correctamente'];
        } else {
            throw new RestException(500, "Error al activar jornada: " . $db->lasterror());
        }
    }

    /**
     * Finalizar jornada
     * @url POST /jornadas/end
     */
    public function end($fecha_fin, $token)
    {
        global $db;

        if (empty($fecha_fin) || empty($token)) {
            throw new RestException(400, "Parámetros incompletos");
        }

        $timestamp = strtotime($fecha_fin);
        if ($timestamp === false) {
            throw new RestException(400, "Formato de fecha inválido");
        }
        $fecha_fin = date("Y-m-d H:i:s", $timestamp);

        $jornada = $this->getJornadaByToken($token);
        if (!$jornada) {
            throw new RestException(404, "Jornada no encontrada");
        }

        if ($jornada->estado != 'activa' && $jornada->estado != 'parada') {
            throw new RestException(400, "La jornada no está activa ni pausada");
        }

        $tiempoTrabajoFinal = (int)$jornada->tiempo_trabajo;
        $tiempoDescansoFinal = (int)$jornada->tiempo_descanso;

        if ($jornada->estado == 'activa') {
            // Desde fecha_inicio hasta fecha_fin sumamos a tiempo_trabajo
            $tiempoTrabajoFinal += (strtotime($fecha_fin) - strtotime($jornada->fecha_inicio));
        } elseif ($jornada->estado == 'parada' && !empty($jornada->fecha_pausa)) {
            // Desde fecha_pausa hasta fecha_fin sumamos a tiempo_descanso
            $tiempoDescansoFinal += (strtotime($fecha_fin) - strtotime($jornada->fecha_pausa));
        }

        $sqlUpdate = "UPDATE " . MAIN_DB_PREFIX . "jornadas SET fecha_fin = '" . $db->escape($fecha_fin) . "', estado = 'finalizada', tiempo_trabajo = " . (int)$tiempoTrabajoFinal . ", tiempo_descanso = " . (int)$tiempoDescansoFinal . ", fecha_pausa = NULL WHERE rowid = " . (int)$jornada->rowid;

        if (!$db->query($sqlUpdate)) {
            throw new RestException(500, "Error al finalizar jornada: " . $db->lasterror());
        }

        return ['success' => true, 'message' => 'Jornada finalizada correctamente'];
    }

   
    /**
     * Obtener jornada activa o en pausa para el token
     * @url GET /jornadas/active
     */
    public function getActive()
    {
        global $db;

        $headers = apache_request_headers();
        $token = isset($headers['DOLAPIKEY']) ? $headers['DOLAPIKEY'] : '';

        if (empty($token)) {
            throw new RestException(400, "Token requerido");
        }

        $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "jornadas
            WHERE token = '" . $db->escape($token) . "'
            AND (estado = 'activa' OR estado = 'parada')
            ORDER BY rowid DESC
            LIMIT 1";

        $resql = $db->query($sql);
        if ($resql && $db->num_rows($resql) > 0) {
            $row = $db->fetch_object($resql);

            $tiempo_trabajo_actual = (int)$row->tiempo_trabajo;
            $tiempo_descanso_actual = (int)$row->tiempo_descanso;
            $now = time();

            if ($row->estado == 'activa') {
                $tiempo_trabajo_actual += ($now - strtotime($row->fecha_inicio));
            } elseif ($row->estado == 'parada' && !empty($row->fecha_pausa)) {
                $tiempo_descanso_actual += ($now - strtotime($row->fecha_pausa));
            }

            return [
                'fecha_inicio' => $row->fecha_inicio,
                'estado' => $row->estado,
                'tiempo_trabajo' => $tiempo_trabajo_actual,
                'tiempo_descanso' => $tiempo_descanso_actual,
                'rowid' => $row->rowid
            ];
        } else {
            return ['estado' => 'sin_jornada_activa'];
        }
    }
}
