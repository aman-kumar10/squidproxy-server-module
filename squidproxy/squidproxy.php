<?php

use WHMCS\Database\Capsule;
use WHMCS\Module\Server\Squidproxy\Helper;

if (!defined("WHMCS")) {
    die('You can not access this file directly.');
}

// Squid Proxy Meta Data
function squidproxy_MetaData(){
    return [
        'DisplayName' => 'Squid Proxy',
        'DefaultSSLPort' => '2048', // Default SSL Connection Port
    ];
}

// Config options
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

// Test Connection
function squidproxy_TestConnection(array $params){
    try {
        $errorMsg  = '';
        $success = '';

        $helper = new Helper($params);
        $curlRes = $helper->testConnectionCurl();

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

        if ($accountRes['httpcode'] == 200 && $accountRes['result']->success == true) {

            $helper->insertcustomFieldVal($username, 'proxy_user|%', 'text');
            $helper->insertcustomFieldVal( $password, 'proxy_password|%', 'password');

            // allocation
            $allocationRes = $helper->allocationCurl($username, $proxy_no);   
            if($allocationRes['httpcode'] == 200 && $allocationRes['result']->success == true) {
                // Email template
                $helper->createSquid_EmailTemplate();
                // Send Email
                $helper->sendSquidProxyEmail(
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

// Suspend Account
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
 
// Unsuspend Account
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
 
// Terminate Account
function squidproxy_TerminateAccount(array $params)
{
    try {
        $helper = new Helper($params);

        $productId = $params['pid'];
        $serviceId = $params['serviceid'];
    
        $terminateRes = $helper->terminateAccCurl('Terminate User');

        if($terminateRes['httpcode'] == 200 && $terminateRes['result']->success == true) {
            $deleteProxyName = $helper->deleteProxyField( 'proxy_user'); 
            $deleteProxyPass = $helper->deleteProxyField( 'proxy_password'); 
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

// Client Area Proxy Details
function squidproxy_ClientArea(array $params) {
    try {
        global $CONFIG;
        $helper = new Helper($params);
        
        $username = $params['customfields']['proxy_user'];
        $password = $params['customfields']['proxy_password'];
        $serviceId = $params['serviceid'];
        
        $allocationRes = $helper->getProxyAllocations( 'Get Allocations');
        if($allocationRes['httpcode'] == 200 && $allocationRes['result']->success == true) {
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

// Admin Area Proxy Details
function squidproxy_AdminServicesTabFields(array $params)
{
    try {
        global $CONFIG;
        $helper = new Helper($params);
        
        $username = $params['customfields']['proxy_user'];
        $password = $params['customfields']['proxy_password'];

        if($username == '' && $password == '') {
            $html = '<div class="alert alert-warning" role="alert"> This user does not have an active proxy account. </div>';
        } elseif($username == '') {
            $html = '<div class="alert alert-warning" role="alert"> Proxy Customer Name is Empty!</div>';
        } elseif(($password == '')) {
            $html = '<div class="alert alert-warning" role="alert"> Proxy Customer Password is Empty. </div>';
        } else {
            $getUserDetails = $helper->getProxyAllocations( 'Get Allocations');
    
            $password = $getUserDetails['result']->data->user->password;
            $username = $getUserDetails['result']->data->user->username;
    
            if($getUserDetails['httpcode'] == 200 && $getUserDetails['result']->success == true) {

                $proxy_list = $getUserDetails['result']->data->user->allocations[0];
                $allocationList = $helper->listAllocationsRange($proxy_list);
                if (!empty($allocationList) && is_array($allocationList)) {
                    $proxyHtml = ''; 
                    foreach ($allocationList as $index => $proxy) {
                        $proxyHtml .= htmlspecialchars($proxy).'</br>';
                    }
                } else {
                    $proxyHtml = '<div class="alert alert-warning" role="alert">No proxies allocated.</div>';
                }
    
                $html = '<link href="' . $CONFIG["SystemURL"] . '/modules/servers/squidproxy/assets/css/admin-style.css" rel="stylesheet">
                        <script src="' . $CONFIG["SystemURL"] . '/modules/servers/squidproxy/assets/js/admin-script.js"></script>
                    <div class="container deviceCell">
                        <h4>Proxy Allocation List</h4>
                        <table class="ad_on_table_dash table table-striped" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tbody>
                                <tr>
                                    <td style="width:50%" class="hading-td">
                                        <div class="container proxy-details">
                                            <table class="ad_on_table_dash table table-striped" width="100%" cellspacing="0" cellpadding="0" border="0">
                                                <tbody>
                                                    <tr>
                                                        <td class="hading-td">Username :</td>
                                                        <td class="hading-td">' . $username . '</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="hading-td">Password :</td>
                                                        <td class="hading-td">
                                                            <span id="passwordField" class="hidden-password">.................</span>
                                                            <button id="togglePassword" type="button" 
                                                                data-password="' . htmlspecialchars($password, ENT_QUOTES, "UTF-8") . '">
                                                                <i id="eyeIcon" class="fa fa-eye"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="hading-td">Proxy List :</td>
                                                        <td class="hading-td text-area">
                                                            <div class="list-textarea">
                                                                ' . $proxyHtml . '
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>';
    
            } else {
                $html = '<div class="alert alert-warning" role="alert">'. $getUserDetails['result']->message .'</div>';
            }        
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

// Change password
function squidproxy_ChangePassword(array $params)
{
    try {
        $helper = new Helper($params);
        $password = $params['password'];

        $changepssRes = $helper->changeUserPasswordCurl($password, 'Change Password');
        
        if($changepssRes['httpcode'] == 200 && $changepssRes['result']->success == true) {
            $updatePass = $helper->insertcustomFieldVal($password, 'proxy_password|%', 'password');

            return ($updatePass || empty($updatePass)) ? 'success' : 'Unable to change password in custom fields!';

        } else {
            return $changepssRes['result']->message;
        }

    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'squidproxy',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }
}
