<?php

use WHMCS\Database\Capsule;
use WHMCS\Module\Server\Squidproxy\Helper;

if (!defined("WHMCS")) {
    die('You can not access this file directly.');
}

function squidproxy_MetaData(){
    return [
        'DisplayName' => 'Squid Proxy',
        'DefaultSSLPort' => '2048', // Default SSL Connection Port
    ];
}

function squidproxy_ConfigOptions($params){
    $helper = new Helper($params);
    // create configurable options
    $helper->configurableOptions();

    // custom fields
    $pid = $_REQUEST['id'];
    $helper->customfieldsProduct($pid);

    return [
        'Username' => [
            'Type' => 'text', 
            'Size' => '50', 
            'Description' => 'Enter user name',
        ],
        'Password' => [
            'Type' => 'password',
            'Size' => '50',
            'Description' => 'Enter user password',
        ]
    ];
}


function squidproxy_TestConnection(array $params){
    try {
        $errorMsg  = '';
        $success = '';
        $servername = $params['serverusername'];
        $serverpass = $params['serverpassword'];

        $helper = new Helper($params);
        $curlRes = $helper->testConnectionCurl($servername, $serverpass);

        if ($curlRes['httpcode'] == 200 && $curlRes['result']->message == 'Success') {
            $success = true;
        } else {
            $errorMsg = $curlRes['result']->getMessage;
        }

        return array('success' => $success, 'error' => $errorMsg);
    } catch (Exception $e) {

        return ['success' => false, 'error' => "Error: " . $e->getMessage()];
    }
}


// Create Account
function squidproxy_CreateAccount($params){
    try {
        $helper = new Helper($params);

        $userId = $params['userid'];
        $productId = $params['pid'];
        $serviceId = $params['serviceid'];
        $proxy_no = $params['configoptions']['proxy_no'];

        if (!empty($helper->getCustomFieldVal($productId, 'proxy_password|%', 'password'))) {
            $password = $helper->getCustomFieldVal($productId, 'proxy_password|%', 'password');
        } else {
            $password = $helper->generatePassword(random_int(15, 16));
        }

        $userVals = Capsule::table('tblclients')->where('id', $userId)->first();
        $username = strtolower(str_replace(' ', '', $userVals->firstname.$userVals->lastname.$serviceId));

        $accountRes = $helper->createAccountCurl($username, $password);

        if ($accountRes['httpcode'] == 200 && $accountRes['result']->success = true) {

            $helper->insertcustomFieldVal($productId , $serviceId, $username, 'proxy_user|%', 'text');
            $helper->insertcustomFieldVal($productId , $serviceId, $password, 'proxy_password|%', 'password');

            // allocation
            $allocationRes = $helper->allocationCurl($username, $proxy_no);   
            if($allocationRes['httpcode'] == 200 && $allocationRes['result']->success = true) {
                // Email template
                $helper->createSquid_EmailTemplate();
                // Send Email
                $helper->sendSquidProxyEmail(
                    $serviceId,
                    $params['clientsdetails']['email'],
                    $username,
                    $password,
                    $allocationRes['result']->data->proxyList
                );
                return 'success';

            } else {
                return $allocationRes['result']->message;
            }
        } else {
            return $accountRes['result']->message;
        }
    } catch (Exception $e) {
        logActivity("Error account creation: " . $e->getMessage());
    }
}


function squidproxy_SuspendAccount(array $params)
{
    try {
        return true;
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'proxymodule',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );
 
        return $e->getMessage();
    }
 
 
}
 
function squidproxy_UnsuspendAccount(array $params)
{
    try {
        return true;
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'proxymodule',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );
 
        return $e->getMessage();
    }
}
 
 
function squidproxy_TerminateAccount(array $params)
{
    try {
        $helper = new Helper($params);

        $productId = $params['pid'];
        $serviceId = $params['serviceid'];
    
        $terminateRes = $helper->terminateCurl('Terminate User');

        if($terminateRes['httpcode'] == 200 && $terminateRes['result']->success = true) {
            $deleteProxyName = $helper->deleteProxyField($productId, $serviceId, 'proxy_user'); 
            $deleteProxyPass = $helper->deleteProxyField($productId, $serviceId, 'proxy_password'); 
            if($deleteProxyName['success'] = true && $deleteProxyPass['success'] = true) {
                return 'success';
            } else {
                return 'User does not exist';
            }
        } else {
            return $terminateRes['result']->message;
        }
        

    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'proxymodule',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );
 
        return $e->getMessage();
    }
}
 
// Admin Area
function squidproxy_AdminArea(array $params) {
    try {
        // global $CONFIG;
        // $helper = new Helper($params);
        
        // $username = $params['customfields']['proxy_user'];
        // $password = $params['customfields']['proxy_password'];
        // $serviceId = $params['serviceid'];
        
        // $allocationRes = $helper->getProxiesAllocations($username, 'Get Allocations');
        // if($allocationRes['httpcode'] && $allocationRes['result']->success = true) {
        //     $proxy_list = $allocationRes['result']->data->user->allocations[0];
        //     $allocationList = $helper->listAllocationsRange($proxy_list);
        // } 

        // $assets_link = $CONFIG["SystemURL"] . "/modules/servers/squidproxy/assets/";

        // return array(
        //     'templatefile' => "templates/overview.tpl",
        //     'vars' => array(
        //         'status' => $allocationRes['httpcode'],
        //         'serviceid' => $serviceId,
        //         'username' => $username,
        //         'password' => $password,
        //         'allocations' => $allocationList,
        //         'assets_link' => $assets_link,
        //     ),
        // );

    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'squidproxy',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        // In an error condition, display an error page.
        return array(
            'tabOverviewReplacementTemplate' => 'error.tpl',
            'templateVariables' => array(
                'usefulErrorHelper' => $e->getMessage(),
            ),
        );
    }
}

// Client Area 
function squidproxy_ClientArea(array $params) {
    try {
        global $CONFIG;
        $helper = new Helper($params);
        
        $username = $params['customfields']['proxy_user'];
        $password = $params['customfields']['proxy_password'];
        $serviceId = $params['serviceid'];
        
        $allocationRes = $helper->getProxiesAllocations($username, 'Get Allocations');
        if($allocationRes['httpcode'] && $allocationRes['result']->success = true) {
            $proxy_list = $allocationRes['result']->data->user->allocations[0];
            $allocationList = $helper->listAllocationsRange($proxy_list);
        } 

        $assets_link = $CONFIG["SystemURL"] . "/modules/servers/squidproxy/assets/";

        return array(
            'templatefile' => "templates/overview.tpl",
            'vars' => array(
                'status' => $allocationRes['httpcode'],
                'serviceid' => $serviceId,
                'username' => $username,
                'password' => $password,
                'allocations' => $allocationList,
                'assets_link' => $assets_link,
            ),
        );

    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'squidproxy',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        // In an error condition, display an error page.
        return array(
            'tabOverviewReplacementTemplate' => 'error.tpl',
            'templateVariables' => array(
                'usefulErrorHelper' => $e->getMessage(),
            ),
        );
    }
}

function squidproxy_AdminServicesTabFields(array $params)
{
    try {
        global $CONFIG;
        $helper = new Helper($params);
        
        $username = $params['customfields']['proxy_user'];
        $password = $params['customfields']['proxy_password'];
        $serviceId = $params['serviceid'];
        
        $allocationRes = $helper->getProxiesAllocations($username, 'Get Allocations');
        if($allocationRes['httpcode'] && $allocationRes['result']->success = true) {
            $proxy_list = $allocationRes['result']->data->user->allocations[0];
            $allocationList = $helper->listAllocationsRange($proxy_list);
            if (!empty($allocationList) && is_array($allocationList)) {
                $proxyHtml = ''; 
                foreach ($allocationList as $index => $proxy) {
                    $proxyHtml .= ($index + 1) . '.  <span>' . htmlspecialchars($proxy) . '</span>'.'</br>';
                }
            } else {
                $proxyHtml = 'No proxies allocated.';
            }

            $html = '<link href="' . $CONFIG["SystemURL"] . '/modules/servers/squidproxy/assets/css/admin-style.css" rel="stylesheet">
                <div class="container deviceCell">
                    <h4>Proxy Allocation List</h4>
                    <table class="ad_on_table_dash table table-striped" width="100%" cellspacing="0" cellpadding="0" border="0">
                        <tbody>
                            <tr>
                                <td style="width:50%" class="hading-td"> <div class="proxy-list">' . $proxyHtml . '</div></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <script src="' . $CONFIG["SystemURL"] . '/modules/servers/proxy/assets/js/admin-script.js"></script>';

        } else {
            $html = '<div class="alert alert-warning" role="alert">Please provide service id to get proxy info.</div>';
        }

        return ["Proxy Information" => $html];

    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'squidproxy',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        // In an error condition, display an error page.
        return array(
            'tabOverviewReplacementTemplate' => 'error.tpl',
            'templateVariables' => array(
                'usefulErrorHelper' => $e->getMessage(),
            ),
        );
    }
}