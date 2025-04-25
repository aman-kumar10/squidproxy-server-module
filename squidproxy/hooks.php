<?php

use WHMCS\Database\Capsule;


if (!defined("WHMCS")) {
  die("This file cannot be accessed directly");
}


// admin area hook
// add_hook('AdminProductConfigFieldsSave', 1, function ($vars) {
//   try {
//     $pid = $vars['pid'];
//     $groupname = 'Squid Proxy-' . $pid;
//     $groupId = Capsule::table('tblproductconfiggroups')->where('name', $groupname)->where('description', 'Squid Proxy')->value('id');

//     if (Capsule::table('tblproducts')->where('id', $pid)->where('servertype', 'squidproxy')->count() == 1 && !empty(Capsule::table('tblproducts')->where('id', $pid)->where('servertype', 'squidproxy')->value('configoption3'))) {

//         Capsule::table('tblproductconfiglinks')->where('gid', $groupId)->where('pid', $pid)->delete();

//     } else {

//       if(Capsule::table('tblproductconfiglinks')->where('gid', $groupId)->where('pid', $pid)->count() == 0) {
//         Capsule::table('tblproductconfiglinks')->insert([
//           'gid'=> $groupId,
//           'pid'=> $pid
//           ]);
//       }

//     }
//   } catch (Exception $e) {
//     logActivity("Squid Proxy: Failed to update configurable options, " . $e->getMessage());
//   }
// });
