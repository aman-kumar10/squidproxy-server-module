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

        if (!empty($helper->getCustomFieldVal($productId, 'Proxy Customer Password', 'password'))) {
            $password = $helper->getCustomFieldVal($productId, 'Proxy Customer Password', 'password');
        } else {
            $password = $helper->generatePassword(random_int(15, 16));
        }

        $userVals = Capsule::table('tblclients')->where('id', $userId)->first();
        $username = strtolower(str_replace(' ', '', $userVals->firstname.$userVals->lastname.$serviceId));

        $accountRes = $helper->createAccountCurl($username, $password);

        if ($accountRes['httpcode'] == 200 && $accountRes['result']->success = true) {

            $helper->insertcustomFieldVal($productId , $serviceId, $username, 'Proxy Customer Name', 'text');
            $helper->insertcustomFieldVal($productId , $serviceId, $password, 'Proxy Customer Password', 'password');

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
 