<?php

use WHMCS\Database\Capsule;


if(!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

// add_hook('AfterModuleProvisionAddOnFeature', 1, function($vars) {
//     echo "<pre>";
//     print_r($vars);
//     die();
// });

// admin area hook
add_hook('AdminAreaPage', 2, function($vars) {
    
    // 
});

// client area hook
add_hook('ClientAreaHeadOutput', 2, function($vars) {

    // 
});