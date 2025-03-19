<?php

namespace WHMCS\Module\Server\Squidproxy;

use WHMCS\Database\Capsule;
use Exception;

class Helper
{

    public function testConnection($params)
    {
        try {
            $this->method = 'GET';
            $this->endPoint .= "/auth/signin?username=" . $params['serverusername'] . "&password" . $params['serverpassword'];
            $this->action = 'Test Connection';
            $curlResponse = $this->curlCall();

            return $curlResponse;
        } catch (\Exception $e) {
            logActivity("Error: Proxy server module failed to execute the testConnection function - " . $e->getMessage());
        }
    }
    // configurable options
    function configurableOptions()
    {
        try {

            // product id
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
    function customfieldsClient()
    {
        try {
            $fields = [
                [
                    'fieldname'   => 'Customer Name',
                    'description' => 'Enter Customer Name',
                    'fieldtype'   => 'text',
                ],
                [
                    'fieldname'   => 'Customer Email',
                    'description' => 'Enter Customer Email',
                    'fieldtype'   => 'text',
                ],
            ];

            foreach ($fields as $field) {
                $fieldExist = Capsule::table('tblcustomfields')
                    ->where('fieldname', $field['fieldname'])
                    ->where('type', 'client')
                    ->exists();

                if (!$fieldExist) {
                    Capsule::table('tblcustomfields')->insert([
                        'type'        => 'client',
                        'fieldname'   => $field['fieldname'],
                        'description' => $field['description'],
                        'fieldtype'   => $field['fieldtype'],
                        'adminonly'   => '1',
                        'required'    => '0',
                        'showorder'   => '0',
                        'showinvoice' => '0',
                        'sortorder'   => '0',
                    ]);

                    logActivity("Custom client field '{$field['fieldname']}' created successfully.");
                } else {
                    logActivity("Custom client field '{$field['fieldname']}' already exists.");
                }
            }
        } catch (Exception $e) {
            logActivity("Error in custom fields: " . $e->getMessage());
        }
    }

    function curlCall($url , $action)
    {

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

        // if (curl_errno($ch)) {
        //     throw new \Exception(curl_error($ch));
        // }

        return ['httpcode' => $httpCode, 'result' => json_decode($response)];
    }
}
