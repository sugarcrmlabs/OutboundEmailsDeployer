<?php
// pre execute actions

global $sugar_config;

if (strtolower($sugar_config['dbconfig']['db_type']) == 'mysql') {
    echo 'Detected MySQL<br/>';
} else {
    die ('This installable module has been only certified with MySQL<br/>');
}
