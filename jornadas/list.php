<?php

require '../../main.inc.php';  // <-- CORREGIDO
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// Permisos
if (!$user->rights->jornadas->read) {
    accessforbidden();
}

llxHeader('', 'Lista de Jornadas');
print load_fiche_titre('Jornadas registradas');

// Consulta
$sql = "SELECT rowid, usuario_id, fecha_inicio, fecha_fin, estado FROM " . MAIN_DB_PREFIX . "jornadas ORDER BY fecha_inicio DESC";
$resql = $db->query($sql);

if ($resql) {
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><th>ID</th><th>Usuario</th><th>Inicio</th><th>Fin</th></tr>';

    while ($obj = $db->fetch_object($resql)) {
        print '<tr>';
        print '<td>' . $obj->rowid . '</td>';
        print '<td>' . $obj->fk_user . '</td>';
        print '<td>' . $obj->fecha_inicio . '</td>';
        print '<td>' . $obj->fecha_fin . '</td>';
        print '</tr>';
    }

    print '</table>';
} else {
    print '<div class="error">Error al ejecutar la consulta SQL</div>';
}

llxFooter();
$db->close();
