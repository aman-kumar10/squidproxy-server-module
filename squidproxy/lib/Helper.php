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
    public $baseUrl = '';
    public $token = '';
    public $userId = '';
    public $username = '';
    public $serviceId = '';
    public $productId = '';
    public $proxynum = '';

    function __construct($params = [])
    {
        $this->servername = $params['serverusername'];
        $this->serverpass = $params['serverpassword'];
        $this->serverhost = $params['serverhostname'];
        $this->serverport = $params['serverport'];
        $this->userId = $params['userid'];
        $this->username = $params['username'];

        $this->serviceId = $params['serviceid'];
        $this->productId = $params['pid'];

        $this->proxynum = $params['configoption1'] ?? Capsule::table('tblproducts')->where('id', $params['pid'])->value('configoption1');

        $this->baseUrl = "http://" . $this->serverhost . ":" . $this->serverport . "/v1/";

        $endPoint = "auth/signin/" . $this->servername;

        if($this->serverpass != '') {
            $data = [
                'password' => $this->serverpass
            ];
            
            $getToken = $this->callCurl($endPoint, json_encode($data), 'GET', 'GetToken');
    
            if ($getToken['httpcode'] == 200 && $getToken['result']->success == true) {
                $this->token = $getToken['result']->data->token;
            }
        }
    }

    // Create Custom fields (Product type)
    function customfieldsProduct($id)
    {
        try {
            $field = [
                'fieldname'   => 'allocation_range|Proxy Allocations Range',
                'description' => 'Enter Proxy Allocations Range',
                'fieldtype'   => 'text',
                'relid'       => $id
            ];

            $fieldExist = Capsule::table('tblcustomfields')
                ->where('relid', $field['relid'])
                ->where('fieldname', $field['fieldname'])
                ->where('type', 'product')
                ->exists();

            if (!$fieldExist) {
                Capsule::table('tblcustomfields')->insert([
                    'type'        => 'product',
                    'relid'       => $field['relid'],
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
        } catch (Exception $e) {
            logActivity("Error in custom fields: " . $e->getMessage());
        }
    }

    // Test Connection
    function testConnectionCurl()
    {
        try {
            $endPoint = "auth/signin/" . $this->servername;
            $data = [
                'password' => $this->serverpass
            ];
            $curlResponse = $this->callCurl($endPoint, json_encode($data), 'GET', 'TestConnection');
            return $curlResponse;
        } catch (Exception $e) {
            logActivity("Error in API request: " . $e->getMessage());
        }
    }

    // Create Account
    function createAccountCurl($username, $password)
    {
        try {
            $endPoint = "users/". $username;
            $data = [
                'password' => $password,
                'comment' => ''
            ];
            $curlResponse = $this->callCurl($endPoint, json_encode($data), 'POST', 'Create Account');
            return $curlResponse;
        } catch (Exception $e) {
            logActivity("Error in API request: " . $e->getMessage());
        }
    }

    // Assign Allocations
    function allocationCurl($username, $proxy_no)
    {
        try {
            $endPoint = "users/" . $username . "/allocations/auto/" . $proxy_no;
            $data = [];
            $curlResponse = $this->callCurl($endPoint, json_encode($data), 'POST', 'Assign Allocations');
            return $curlResponse;
        } catch (Exception $e) {
            logActivity("Error in API request: " . $e->getMessage());
        }
    }

    // Terminate Account
    function terminateAccCurl($username)
    {
        try {
            $endPoint = "users/" . $username;
            $data = [];
            $curlResponse = $this->callCurl($endPoint, json_encode($data), 'DELETE', 'Terminate User');
            return $curlResponse;
        } catch (Exception $e) {
            logActivity("Error in API request: " . $e->getMessage());
        }
    }

    // Get Allocations List
    function getProxyList($username)
    {
        try {
            $url = "users/" . $username . '/allocations';
            $data = [];
            $curlResponse = $this->callCurl($url, json_encode($data), 'GET', 'Get Allocations');
            return $curlResponse;
        } catch (Exception $e) {
            logActivity("Error to get the allocations of user:" . $this->username . ", Error:" . $e->getMessage());
        }
    }

    // Chnage Password
    function changeUserPasswordCurl($username, $password, $action)
    {
        try {
            $url = "users/" . $username;
            $data = [
                'key' => 'password',
                'value' => $password
            ];
            $curlResponse = $this->callCurl($url, json_encode($data), 'PUT', $action);
            return $curlResponse;
        } catch (Exception $e) {
            logActivity("Error to change the password for :" . $this->username . ", Error:" . $e->getMessage());
        }
    }

    // Rotate Allocations 
    public function rotateAllocations($user, $range) {
        try {
            $url = "users/" . $user . "/allocations/" . $range . "/rotate";
            $data = [];
            $curlResponse = $this->callCurl($url, json_encode($data), 'POST', 'Rotate Allocations');
            return $curlResponse;
        } catch(Exception $e){
            logActivity("Unable to rotate allocations for user '".$user."' , Error: " . $e->getMessage());
        }
    }

    // Curl Call
    function callCurl($endPoint, $data, $method, $action)
    {
        try {
            $url =  $this->baseUrl . $endPoint;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 1000);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

            if(in_array($action, ['Create Account', 'GetToken', 'TestConnection', 'Change Password'])) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data),
                    'Authorization: Bearer ' . $this->token,
                ]);

            } else {
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->token,
                ]);
            }

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            /** Log the API request and response for the Proxy Server module */
            logModuleCall('Squid Proxy', $action, $url, $response, "", "");
            return ['httpcode' => $httpCode, 'result' => json_decode($response)];

        } catch (Exception $e) {
            logActivity("Error in API request: " . $e->getMessage());
        }
    }

    // Update or Insert values in custom fields
    function insertcustomFieldVal($value, $fieldname, $fieldtype)
    {
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

    // Email Template for Squid Proxy 
    function squidProxy_EmailTemplate()
    {
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
    function sendSquidProxyEmail($email, $username, $password, $proxyList)
    {
        try {
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
                logActivity("Proxy Access Email sent successfully to User ID: " . $this->serviceId);
                return true;
            } else {
                logActivity("Failed to send Proxy Access Email. Error: " . $result['message']);
                return false;
            }
        } catch (Exception $e) {
            logActivity("Unable to sent Squid Proxy email" . $e->getMessage());
        }
    }

    // Delete User Name and Password
    function deleteProxyField($field)
    {
        try {
            $existField = Capsule::table('tblcustomfields')->where('type', 'product')->where('relid', $this->productId)->where('fieldname', 'like', $field . "|%")->first();
            if ($existField->id) {
                $delete = Capsule::table('tblcustomfieldsvalues')->where('fieldid', $existField->id)->where('relid', $this->serviceId)->delete();
            }

            if ($delete) {
                return ['success' => 1, 'message' => 'User name deleted successfully'];
            } else {
                return ['success' => 0, 'message' => 'No user found'];
            }
        } catch (Exception $e) {
            logActivity("Unable to delete user name details:" . $e->getMessage());
        }
    }

    // Create Configurable Options
    public function createConfigurableOption($pid)
    {
        try {
            $groupname = 'Squid Proxy-' . $pid;

            if (Capsule::table('tblproductconfiggroups')->where('name', $groupname)->count() == 0) {
                $groupid = Capsule::table('tblproductconfiggroups')->insertGetId(
                    [
                        'name' => $groupname,
                        'description' => 'Squid Proxy'
                    ]
                );

                Capsule::table('tblproductconfiglinks')->insert([
                    'gid' => $groupid,
                    'pid' => $pid   
                ]);

                $productconfig = [
                    'Quota' => [
                        'gid' => $groupid,
                        'optionname' => 'proxy_no|No. of Proxy',
                        'optiontype' => '4',
                        'qtyminimum' => '1',
                        'qtymaximum'  => '255',
                        'order' => '1'
                    ]
                ];

                foreach ($productconfig as $key => $productconfigs) {

                    if (Capsule::table('tblproductconfigoptions')->where('optiontype', $productconfigs['optiontype'])->where('gid' , $productconfigs['gid'])->where('optionname', 'like', '%' . $productconfigs['optionname'] . '%')->count() == 0) {

                        $productconfigid = Capsule::table('tblproductconfigoptions')->insertGetId($productconfigs);
                        if ($productconfigid) {
                            if (Capsule::table('tblproductconfigoptionssub')->where('configid', $productconfigid)->where('optionname', 'like', '%' . $productconfigs['optionname'] . '%')->count() == 0) {
                                $productpriceid =  Capsule::table('tblproductconfigoptionssub')->insertGetId([
                                    'configid' => $productconfigid,
                                    'optionname' => $productconfigs['optionname'],
                                    'sortorder' => '1',
                                ]);
                                $this->insertPriceForOptions($productpriceid);
                            }
                        }
                    }
                }
            }

        } catch(Exception $e) {
            logActivity("Unable to create configurable options:" . $e->getMessage());
        }
    }

    public function insertPriceForOptions($subOptionId)
    {
        try {
            $currencies = Capsule::table('tblcurrencies')->get();

            if ($currencies->isEmpty()) {
                logActivity("No currencies found in tblcurrencies.");
                return;
            }

            foreach ($currencies as $currency) {
                $currId = $currency->id;
                $exists = Capsule::table('tblpricing')
                    ->where('type', 'configoptions')
                    ->where('currency', $currId)
                    ->where('relid', $subOptionId)
                    ->count() == 0;

                if ($exists) {
                    Capsule::table('tblpricing')->insert([
                        'type'       => 'configoptions',
                        'currency'   => $currId,
                        'relid'      => $subOptionId,
                        'msetupfee'  => 0.00,
                        'qsetupfee'  => 0.00,
                        'ssetupfee'  => 0.00,
                        'asetupfee'  => 0.00,
                        'bsetupfee'  => 0.00,
                        'tsetupfee'  => 0.00,
                        'monthly'    => 0.00,
                        'quarterly'  => 0.00,
                        'semiannually' => 0.00,
                        'annually'   => 0.00,
                        'biennially' => 0.00,
                        'triennially' => 0.00
                    ]);
                    logActivity("Pricing set for currency ID: $currId, SubOption ID: $subOptionId");
                } else {
                    logActivity("Pricing already exists for currency ID: $currId, SubOption ID: $subOptionId");
                }
            }
        } catch (Exception $e) {
            logActivity("Unable to setup pricing for config options: " . $e->getMessage());
        }
    }

}
