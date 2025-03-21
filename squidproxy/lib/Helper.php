<?php

namespace WHMCS\Module\Server\Squidproxy;

use WHMCS\Database\Capsule;
use Exception;

class Helper
{

    public $serverhost = '';
    public $serverport = '';
    public $servername = '';
    public $serverpass = '';
    public $token = '';


    function __construct($params = []) {
        $this->servername = $params['configoption1'];
        $this->serverpass = $params['configoption2'];
        $this->serverhost = $params['serverhostname'];
        $this->serverport = $params['serverport'];

        $url = "http://".$this->serverhost.":".$this->serverport."/auth/signin?username=".$this->servername."&password=".$this->serverpass;

        $getToken = $this->callCurl($url , 'Get Token');
        if ($getToken['httpcode'] == 200 && $getToken['result']->message == 'Success') {
            $this->token = $getToken['result']->data->token;
        } 
    }

    // Create Configurable Options
    function configurableOptions(){
        try {
            $pid = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : null;
            if (!$pid) {
                logActivity("Error: Product ID is missing or invalid");
                return;
            }

            // configurable group
            $group = Capsule::table('tblproductconfiggroups')->where('name', 'Squid Proxy')->first();
            if (!$group) {
                $groupId = Capsule::table('tblproductconfiggroups')->insertGetId([
                    'name' => 'Squid Proxy',
                    'description' => 'Squid proxy server module config options',
                ]);
            } else {
                $groupId = $group->id;
            }

            // configurable product
            $productExists = Capsule::table('tblproductconfiglinks')->where('gid', $groupId)->where('pid', $pid)->exists();
            if (!$productExists) {
                Capsule::table('tblproductconfiglinks')->insert([
                    'gid' => $groupId,
                    'pid' => $pid,
                ]);
            }

            // configurable option
            $configExists = Capsule::table('tblproductconfigoptions')->where('gid', $groupId)->where('optionname', 'proxy_no|No. of Proxy')->exists();

            if (!$configExists) {
                Capsule::table('tblproductconfigoptions')->insert([
                    'gid' => $groupId,
                    'optionname' => 'proxy_no|No. of Proxy',
                    'optiontype' => '4',
                    'qtyminimum' => '1',
                    'qtymaximum' => '255',
                ]);
            }
            logActivity("Configurable options set successfully.");

        } catch (Exception $e) {
            logActivity("Error in configurableOptions: " . $e->getMessage());
        }
    }

    // custom fields client type
    function customfieldsProduct($id){
        try {
            $fields = [
                [
                    'fieldname'   => 'Proxy Customer Name',
                    'description' => 'Enter Proxy Customer Name',
                    'fieldtype'   => 'text',
                    'relid' => $id
                ],
                [
                    'fieldname'   => 'Proxy Customer Password',
                    'description' => 'Enter Proxy Customer Password',
                    'fieldtype'   => 'password',
                    'relid' => $id
                ],
            ];

            foreach ($fields as $field) {
                $fieldExist = Capsule::table('tblcustomfields')
                    ->where('relid', $field['relid'])
                    ->where('fieldname', $field['fieldname'])
                    ->where('type', 'product')
                    ->exists();

                if (!$fieldExist) {
                    Capsule::table('tblcustomfields')->insert([
                        'type'        => 'product',
                        'relid'        => $field['relid'],
                        'fieldname'   => $field['fieldname'],
                        'description' => $field['description'],
                        'fieldtype'   => $field['fieldtype'],
                        'adminonly'   => 'on',
                        'required'    => '0',
                        'showorder'   => '0',
                        'showinvoice' => '0',
                        'sortorder'   => '0',
                    ]);

                    logActivity("Custom product field '{$field['fieldname']}' created successfully.");
                } else {
                    logActivity("Custom product field '{$field['fieldname']}' already exists.");
                }
            }
        } catch (Exception $e) {
            logActivity("Error in custom fields: " . $e->getMessage());
        }
    }

    // Test Connection
    function testConnectionCurl($servername, $serverpass){
        try {
            $url = "http://".$this->serverhost.":".$this->serverport."/auth/signin?username=".$servername."&password=".$serverpass;
            $curlResponse = $this->callCurl($url, 'Test Connection');
            return $curlResponse;
        } catch(Exception $e) {
            logActivity("Error in API request: " . $e->getMessage());
        }
    }

    // Create Account
    function createAccountCurl($username, $password){
        try {
            $url = "http://".$this->serverhost.":".$this->serverport."/admin/new_user?new_username=".$username."&new_password=".$password."&username=".$this->servername."&token=".$this->token;
            $curlResponse = $this->callCurl($url, 'Create Account');
            return $curlResponse;
        } catch(Exception $e) {
            logActivity("Error in API request: " . $e->getMessage());
        }
    }

    // Get Allocations
    function allocationCurl($username, $proxy_no){
        try {
            $url = "http://".$this->serverhost.":".$this->serverport."/admin/auto_allocate?new_username=".$username."&new_allocation_size=".$proxy_no."&username=".$this->servername."&token=".$this->token;
            $curlResponse = $this->callCurl($url, 'Allocation');
            return $curlResponse;
        } catch(Exception $e) {
            logActivity("Error in API request: " . $e->getMessage());
        }
    }

    // Curl Call
    function callCurl($url , $action){
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 1000);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
    
            /** Log the API request and response for the Proxy Server module */
            logModuleCall('Squid Proxy', $action, $url, $response, "", "");
    
            return ['httpcode' => $httpCode, 'result' => json_decode($response)];

        } catch(Exception $e) {
            logActivity("Error in API request: " . $e->getMessage());
        }
    }

    // update or insert values in custom fields
    function insertcustomFieldVal($pid, $sid, $value, $fieldname, $fieldtype) {
        try {
            $customField = Capsule::table('tblcustomfields')
            ->where('type', 'product')
            ->where('relid', $pid)
            ->where('fieldname', $fieldname)
            ->where('fieldtype', $fieldtype)->first();
    
            if($customField->id) {
                Capsule::table('tblcustomfieldsvalues')->updateOrInsert(
                    ['fieldid' => $customField->id, 'relid' => $sid],
                    ['value' => $value]
                );
            }
        } catch(Exception $e) {
            logActivity("Error to insert values in custom fields: " . $e->getMessage());
        }
    }

    // Get Custom Fields
    function getCustomFieldVal($id, $fieldname, $fieldtype) {
        try {
            $customField = Capsule::table('tblcustomfields')
                ->where('type', 'product')
                ->where('relid', $id)
                ->where('fieldname', $fieldname)
                ->where('fieldtype', $fieldtype)
                ->first();

            if (!$customField) {
                return null;
            }

            return Capsule::table('tblcustomfieldsvalues')
                ->where('fieldid', $customField->id)
                ->where('relid', $id)
                ->value('value') ?? null;
        } catch (Exception $e) {
            logActivity("Error fetching custom field value: " . $e->getMessage());
            return null;
        }
    }

    // generate password
    function generatePassword($length = 12){
        try {
            return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+'), 0, $length);
        } catch (Exception $e) {
            logActivity("Error generating password: " . $e->getMessage());
            return null;
        }
    }

    function sendAllocationEmail($command, ) {
        $postData = array(
            '//example1' => 'example',
            'messagename' => 'Client Signup Email',
            'id' => '1',
            '//example2' => 'example',
            'customtype' => 'product',
            'customsubject' => 'Product Welcome Email',
            'custommessage' => '<p>Thank you for choosing us</p><p>Your custom is appreciated</p><p>{$custommerge}<br />{$custommerge2}</p>',
            'customvars' => base64_encode(serialize(array("custommerge"=>$populatedvar1, "custommerge2"=>$populatedvar2))),
        );

        $results = localAPI($command, $postData, $adminUsername);
        print_r($results);
    }

    function createSquid_EmailTemplate() {
        try {
            if (!Capsule::table('tblemailtemplates')->where('type', 'product')->where('name', 'Proxy Access Information')->count()) {
                Capsule::table('tblemailtemplates')->insert([
                    'type' => 'product',
                    'name' => 'Proxy Access Information',
                    'subject' => 'Your Proxy Account is Ready - Access Details Inside',
                    'message' => '<p>Dear {$client_name},</p>
            
                                  <p>Your Squid Proxy account has been successfully created.</p>
            
                                  <p><strong>Login Details:</strong></p>
                                  <p>Email: <strong>{$email}</strong></p>
                                  <p>Username: <strong>{$username}</strong></p>
                                  <p>Password: <strong>{$password}</strong></p>
            
                                  <p><strong>Allocated Proxy List:</strong></p>
                                  <pre>{$proxy_list}</pre>
            
                                  <p>You can now start using your proxies.</p>
            
                                  <p>Thanks,<br>Support Team</p>',
                    'custom' => 1
                ]);
            }
            
        } catch (\Exception $e) {
            logActivity("Error Proxy Access Information Email" . $e->getMessage());
        }
    }

    function sendSquidProxyEmail($userId, $email, $username, $password, $proxyList) {
        $postData = [
            'messagename' => 'Proxy Access Information',
            'id' => $userId,
            'customvars' => base64_encode(serialize([
                'email' => $email,
                'username' => $username,
                'password' => $password,
                'proxy_list' => nl2br($proxyList), 
            ])),
        ];
    
        $result = localAPI('SendEmail', $postData);

        if ($result['result'] == 'success') {
            logActivity("Proxy Access Email sent successfully to User ID: $userId");
            return true;
        } else {
            logActivity("Failed to send Proxy Access Email. Error: " . $result['message']);
            return false;
        }
    }
    

}
