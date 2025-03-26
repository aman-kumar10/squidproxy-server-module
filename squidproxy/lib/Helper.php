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
    public $userId = '';
    public $username = ''; 
    public $serviceId = ''; 
    public $productId = ''; 

    function __construct($params = []) {
        $this->servername = $params['serverusername'];
        $this->serverpass = $params['serverpassword'];
        $this->serverhost = $params['serverhostname'];
        $this->serverport = $params['serverport'];
        $this->userId = $params['userid'];
        $this->username = $params['customfields']['proxy_user'];
        
        $this->serviceId = $params['serviceid'];
        $this->productId = $params['pid'];

        $url = "http://".$this->serverhost.":".$this->serverport."/auth/signin?username=".$this->servername."&password=".$this->serverpass;

        $getToken = $this->callCurl($url , 'Get Token');
        
        if ($getToken['httpcode'] == 200 && $getToken['result']->message == 'Success') {
            $this->token = $getToken['result']->data->token;
        } 
    }

    // Create Configurable Options
    function createConfigOptions(){
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

    // Create Custom fields (Product type)
    function customfieldsProduct($id){
        try {
            $fields = [
                [
                    'fieldname'   => 'proxy_user|Proxy Customer Name',
                    'description' => 'Enter Proxy Customer Name',
                    'fieldtype'   => 'text',
                    'relid' => $id
                ],
                [
                    'fieldname'   => 'proxy_password|Proxy Customer Password',
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
    function testConnectionCurl(){
        try {
            $url = "http://".$this->serverhost.":".$this->serverport."/auth/signin?username=".$this->servername."&password=".$this->serverpass;
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

    // Terminate Account
    function terminateAccCurl($action) {
        try {
            $url = "http://".$this->serverhost.":".$this->serverport."/admin/del_user?new_username=".$this->username."&username=".$this->servername."&token=".$this->token;
            $curlResponse = $this->callCurl($url, $action);
            return $curlResponse;

        } catch(Exception $e) {
            logActivity("Error in API request: " . $e->getMessage());
        }
    }

    // Get Allocations
    function getProxyList($action) {
        try {
            $url = "http://".$this->serverhost.":".$this->serverport."/admin/proxylist?new_username=".$this->username."&username=".$this->servername."&token=".$this->token;
            $curlResponse = $this->callCurl($url, $action);
            return $curlResponse;

        } catch(Exception $e) {
            logActivity("Error to get the allocations of user:".$this->username.", Error:" . $e->getMessage());
        }
    }

    // Chnage Password
    function changeUserPasswordCurl($password, $action) {
        try {
            $url = "http://".$this->serverhost.":".$this->serverport."/admin/user_write_password?new_username=".$this->username."&new_password=".$password."&username=".$this->servername."&token=".$this->token;
            $curlResponse = $this->callCurl($url, $action);
            return $curlResponse;

        } catch(Exception $e) {
            logActivity("Error to change the password for :".$this->username.", Error:" . $e->getMessage());
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
    
    // Get Custom Fields
    function getCustomFieldVal($fieldname, $fieldtype) {
        try {
            $customField = Capsule::table('tblcustomfields')
            ->where('type', 'product')
            ->where('relid', $this->productId)
            ->where('fieldname', 'like', $fieldname)
            ->where('fieldtype', $fieldtype)
            ->first();
            
            if (!$customField) {
                return null;
            }

            return Capsule::table('tblcustomfieldsvalues')
            ->where('fieldid', $customField->id)
            ->where('relid', $this->productId)
            ->value('value') ?? null;
            
        } catch (Exception $e) {
            logActivity("Error fetching custom field value: " . $e->getMessage());
            return null;
        }
    }
    
    // Update or Insert values in custom fields
    function insertcustomFieldVal($value, $fieldname, $fieldtype) {
        try {
            $customField = Capsule::table('tblcustomfields')
                ->where('type', 'product')
                ->where('relid', $this->productId)
                ->where('fieldname', 'like', $fieldname)
                ->where('fieldtype', $fieldtype)
                ->first();
    
            if ($customField && isset($customField->id)) {
                $updated = Capsule::table('tblcustomfieldsvalues')->updateOrInsert(
                    ['fieldid' => $customField->id, 'relid' => $this->serviceId],
                    ['value' => $value]
                );
                return $updated ? true : false;
            } 
            return false;

        } catch (Exception $e) {
            logActivity("Error inserting values in custom fields: " . $e->getMessage());
            return false;
        }
    }

    // Generate Random Password
    function generatePassword($length = 12){
        try {
            return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@$%^&*()-_=+'), 0, $length);
        } catch (Exception $e) {
            logActivity("Error generating password: " . $e->getMessage());
            return null;
        }
    }

    // Email Template for Squid Proxy 
    function squidProxy_EmailTemplate() {
        try {
            if (!Capsule::table('tblemailtemplates')->where('type', 'product')->where('name', 'Proxy Access Information')->count()) {
                Capsule::table('tblemailtemplates')->insert([
                    'type' => 'product',
                    'name' => 'Proxy Access Information',
                    'subject' => 'Your Proxy Account is Ready - Access Details Inside',
                    'message' => '<p>Dear {$client_name},</p>
            
                                  <p>Thank you for choosing our proxy service. Your Squid Proxy account has been successfully created and is now ready for use.</p>
            
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

    // Send Email
    function sendSquidProxyEmail($email, $username, $password, $proxyList) {
        try {
            $proxyList = $this->formatProxyList($proxyList);
            $postData = [
                'messagename' => 'Proxy Access Information',
                'id' => $this->serviceId,
                'customvars' => base64_encode(serialize([
                    'email' => $email,
                    'username' => $username,
                    'password' => $password,
                    'proxy_list' => $proxyList, 
                ])),
            ];
        
            $result = localAPI('SendEmail', $postData);
    
            if ($result['result'] == 'success') {
                logActivity("Proxy Access Email sent successfully to User ID: ". $this->serviceId);
                return true;
            } else {
                logActivity("Failed to send Proxy Access Email. Error: " . $result['message']);
                return false;
            }

        } catch(Exception $e) {
            logActivity("Unable to sent Squid Proxy email" . $e->getMessage());
        }
    }
    
    // Format Proxy Allocation List
    function formatProxyList($proxyList) {
        try {
            $lines = explode("\n", trim($proxyList));
            $cleaned = [];
            foreach ($lines as $line) {
                $parts = explode(":", $line);
                if (count($parts) >= 2) {
                    $cleaned[] = $parts[0] . ":" . $parts[1];
                }
            }
            return implode("\n", $cleaned);
            
        } catch(Exception $e) {
            logActivity("Unable to format proxy allocated list: " . $e->getMessage());
        }
    }

    // Delete User Name and Password
    function deleteProxyField($field) {
        try {
            $existField = Capsule::table('tblcustomfields')->where('type', 'product')->where('relid', $this->productId)->where('fieldname', 'like', $field."|%")->first();
            if($existField->id) {
                $delete = Capsule::table('tblcustomfieldsvalues')->where('fieldid', $existField->id)->where('relid', $this->serviceId)->delete();
            }

            if($delete) {
                return ['success'=> 1, 'message'=>'User name deleted successfully'];
            } else {
                return ['success'=> 0, 'message'=>'No user found'];
            }

        } catch(Exception $e) {
            logActivity("Unable to delete user name details:" . $e->getMessage());
        }
    }
    
}
