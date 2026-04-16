<?php
require 'config.php';
require 'model/User.php';
$m = new User();
$admin = $m->login('admin@admin.com', 'admin123');
if(!$admin['success']) {
    $m->register('Admin', 'Admin', 'admin@admin.com', 'admin123', '000', 'Tunis', 'admin');
    echo 'Created Admin';
} else {
    echo 'Admin exists';
}
