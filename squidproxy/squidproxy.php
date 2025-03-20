<?php

use WHMCS\Database\Capsule;
use WHMCS\Module\Server\Squidproxy\Helper;

if (!defined("WHMCS")) {
    die('You can not access this file directly.');
}

function squidproxy_MetaData()
{
    return [
        'DisplayName' => 'Squid Proxy',
        'DefaultSSLPort' => '2048', // Default SSL Connection Port
    ];
}

function squidproxy_ConfigOptions()
{

    $helper = new Helper();
    // create configurable options
    $helper->configurableOptions();

    // custom fields
    $pid = $_REQUEST['id'];
    $helper->customfieldsProduct($pid);



    return [
        // 'API URL' => [
        //     'Type' => 'text', 
        //     'Size' => '50', 
        //     'Description' => 'Enter API URL',
        // ],
        'API Token' => [
            'Type' => 'password',
            'Size' => '225',
        ]
        // 'Proxy Count' => [
        //     'Type' => 'text', 
        //     'Size' => '10', 
        //     'Default' => '10', 
        //     'Description' => 'Default no. of proxies',
        // ],
        // 'Enable Testing' => [
        //     'Type' => 'yesno',
        //     'Description' => 'Tick to enable',
        // ],
        // 'Textarea Field' => [
        //     'Type' => 'textarea',
        //     'Rows' => '3',
        //     'Cols' => '60',
        //     'Description' => 'Write description here...',
        // ],
    ];
}


function squidproxy_TestConnection(array $params)
{

    try {
        $errorMsg  = '';
        $success = '';
        $hostname = $params['serverhostname'];
        $username = $params['serverusername'];
        $password = $params['serverpassword'];
        $port = $params['serverport'];


        $url = "http://" . $hostname . ":" . $port . "/auth/signin?username=" . $username . "&password=" . $password;


        $helper = new Helper();
        $curlRes = $helper->curlCall($url, 'Test Connection');

        if ($curlRes['httpcode'] == 200 && $curlRes['result']->message == 'Success') {
            $success = true;

            $tokenExists = Capsule::table('tblproducts')->where('servertype', 'squidproxy')->get();


            if ($tokenExists) {
                foreach ($tokenExists as $token) {
                    // echo "<pre>";
                    // print_r($token->id);
                    // die();

                    $configVal = Capsule::table('tblproducts')->where('id', $token->id)->value('configoption1');


                    if (!empty($configVal)) {
                        Capsule::table('tblproducts')->where('id', $token->id)->update([
                            'configoption1' => $curlRes['result']->data->token
                        ]);
                    }
                    //  else {
                    //     Capsule::table('tblproducts')->where('id', $token->id)->insert([
                    //         'configoption1', $curlRes['result']->data->token
                    //     ]);
                    // }
                }
            } else {
                return false;
            }
        } else {
            $errorMsg = $curlRes['result']->getMessage;
        }


        return array('success' => $success, 'error' => $errorMsg);
    } catch (Exception $e) {

        return ['success' => false, 'error' => "Error: " . $e->getMessage()];
    }
}


// Create Account
function squidproxy_CreateAccount($params)
{
    // echo "<pre>";
    // print_r($params);
    // die();
    try {
        $helper = new Helper();

        $userId = $params['userid'];
        $tokenVal = $params['configoption1'];
        $serverhostname = $params['serverhostname'];
        $serverusername = $params['serverusername'];
        $serverport = $params['serverport'];
        $productId = $params['pid'];
        $serviceId = $params['serviceid'];


        // if(!empty($helper->getCustomFieldVal($productId, 'Proxy Customer Name', 'text'))) {
        //     $username = $helper->getCustomFieldVal($productId, 'Proxy Customer Name', 'text');
        // } else {

        // }

        if (!empty($helper->getCustomFieldVal($productId, 'Proxy Customer Password', 'password'))) {
            $password = $helper->getCustomFieldVal($productId, 'Proxy Customer Password', 'password');
        } else {
            $password = $helper->generatePassword(random_int(15, 16));
        }


        $userVals = Capsule::table('tblclients')->where('id', $userId)->first();
        $username = strtolower(str_replace(' ', '', $userVals->firstname.$userVals->lastname.$serviceId));

        $url = "http://".$serverhostname.":".$serverport."/admin/new_user?new_username=".$username."&new_password=".$password."&username=".$serverusername."&token=".$tokenVal;

        $curlRes = $helper->curlCall($url, 'Create User');

        if ($curlRes['httpcode'] == 200 && $curlRes['result']->success = true) {

            $helper->insertcustomFieldVal($productId , $serviceId, $username, 'Proxy Customer Name', 'text');
            $helper->insertcustomFieldVal($productId , $serviceId, $password, 'Proxy Customer Password', 'password');
            return 'success';
        } else {
            return $curlRes['result']->message;
        }
    } catch (Exception $e) {
        logActivity("Error account creation: " . $e->getMessage());
    }
}
