<?php

include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

class modJornadas extends DolibarrModules
{
    public function __construct($db)
    {
        $this->db = $db;
        $this->numero = 104000;
        $this->rights_class = 'jornadas';
        $this->family = "hr";
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "Modulo para registrar jornadas de usuarios";
        $this->version = '1.0';
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        $this->picto = 'clock';

        $this->dirs = array("/jornadas/temp");
        $this->sql_dir = array('/jornadas/sql/');
        $this->config_page_url = array();
        $this->langfiles = array("jornadas@jornadas");
        $this->phpmin = array(7, 0);
        $this->need_dolibarr_version = array(14, 0);

        // Activación del setup SQL
        $this->module_parts = array(
            'setup' => array('sql' => array('/jornadas/sql/jornadas.sql')),
            'api' => 1
        );

        // 📌 Permisos del módulo
        $this->rights = array();
        $this->rights[0][0] = 104001;
        $this->rights[0][1] = 'Leer jornadas';
        $this->rights[0][2] = 'r';
        $this->rights[0][3] = 1;
        $this->rights[0][4] = 'read';

        $this->rights[1][0] = 104002;
        $this->rights[1][1] = 'Crear/Modificar jornadas';
        $this->rights[1][2] = 'w';
        $this->rights[1][3] = 0;
        $this->rights[1][4] = 'write';

        $this->rights[2][0] = 104003;
        $this->rights[2][1] = 'Eliminar jornadas';
        $this->rights[2][2] = 'd';
        $this->rights[2][3] = 0;
        $this->rights[2][4] = 'delete';

        // 📌 Menú
        $this->menu = array(
            array(
                'fk_menu' => 0,
                'type' => 'top',
                'titre' => 'Jornadas',
                'mainmenu' => 'jornadas',
                'leftmenu' => 'jornadas',
                'url' => '/jornadas/list.php',
                'langs' => 'jornadas@jornadas',
                'position' => 100,
                'enabled' => '1',
                'perms' => '$user->rights->jornadas->read',
                'target' => '',
                'user' => 2
            )
        );
    }
}
