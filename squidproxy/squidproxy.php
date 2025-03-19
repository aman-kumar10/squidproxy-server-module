<?php

use WHMCS\Database\Capsule;
use WHMCS\Module\Server\Squidproxy\Helper;

if(!defined("WHMCS")) {
    die('You can not access this file directly.');
}

function squidproxy_MetaData() {
    return [
        'DisplayName' => 'Squid Proxy',
    ];
}

function squidproxy_ConfigOptions() {

    $helper = new Helper();
    // create configurable options
    $helper->configurableOptions();

    // custom fields
    $helper->customfieldsClient();



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


function squidproxy_TestConnection(array $params) {
  
    try {
        $errorMsg  = '';
        $success = '';
        $hostname = $params['serverhostname'];  
        $username = $params['serverusername'];
        $password = $params['serverpassword']; 
    
    
        $url = "http://".$hostname.":2048/auth/signin?username=".$username."&password=".$password;

 
        $helper = new Helper();
        $curlRes = $helper->curlCall($url, 'Test Connection');
     
        if($curlRes['httpcode'] == 200 && $curlRes['result']->message == 'Success') {
            $success = true;

            $tokenExists = Capsule::table('tblproducts')->where('servertype', 'squidproxy')->get();

           
            if($tokenExists) {
                foreach($tokenExists as $token) {
                    // echo "<pre>";
                    // print_r($token->id);
                    // die();
        
                    $configVal = Capsule::table('tblproducts')->where('id', $token->id)->value('configoption1');
                   
        
                    if(!empty($configVal)) {
                        Capsule::table('tblproducts')->where('id', $token->id)->update([
                            'configoption1'=> $curlRes['result']->data->token
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

    } catch(Exception $e) {

        return ['success' => false, 'error' => "Error: " . $e->getMessage()];
    }
}