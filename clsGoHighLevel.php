<?php
class clsGoHighLevel
{
    private string $ghl_api_url;
    private string $ghl_api_key;

    function __construct()
    {
        $this->ghl_api_url = "https://rest.gohighlevel.com/v1";
        $this->ghl_api_key = get_option("ghl_api_key");

        add_action('rest_api_init', function () {
            register_rest_route('gohighlevel', '/execute/', array(
                'methods' => 'POST',
                'callback' => [$this, 'sendto_gohighlevel'],
                'permission_callback' => '__return_true'
            ));
        });
    }

    function ilab_test()
    {
        if (isset($_GET['type']) && $_GET['type'] === "test")
        {
            echo "in test ";
            $strContactId = $this->createContact("Name ABC", "email@example.com", "1231231234");
            if ($strContactId !== false)
            {
                $this->executeWorkFlow( $strContactId, "" );
            }
            exit;
        }
    }

    function createContact(string $name, string $email, string $phone)
    {
        $api_url = $this->ghl_api_url . "/contacts/";
        $api_key = $this->ghl_api_key;

        $arrName = explode(" ", $name);
        $data = [
            "firstName" => @$arrName[0],
            "lastName" => @$arrName[1],
            "email" => $email,
            "phone" => $phone
        ];

        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $api_key",
            "Content-Type: application/json"
        ]);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        
        if ($response === false) 
        {
            return false;
        } 
        else 
        {
            $response_data = json_decode($response, true);
            if (isset($response_data['contact']['id'])) 
            {
                return $response_data['contact']['id'];
            } 
            else 
            {
                return false;
            }
        }

        curl_close($ch);

        return false;
    }

    function executeWorkFlow(string $contactId, string $workflowId)
    {

        $apiKey = $this->ghl_api_key;
        $url =  $this->ghl_api_url . "/contacts/$contactId/workflow/{$workflowId}";

        $data = [
            //'contact_id' => $contactId,
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $apiKey",
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
        
        if ($httpCode === 200) 
        {
            return true;
        } 
        else 
        {
            return false;
        }

    }

    function sendto_gohighlevel()
    {
        $input = file_get_contents("php://input");
        parse_str($input, $data);

        $name = isset($data['fields']['name']['value']) ? $data['fields']['name']['value'] : null;
        $phone = isset($data['fields']['phone']['value']) ? $data['fields']['phone']['value'] : null;
        $email = isset($data['fields']['email']['value']) ? $data['fields']['email']['value'] : null;
        $workflowId = isset($data['fields']['workflow_id']['value']) ? $data['fields']['workflow_id']['value'] : null;

        $strContactId = $this->createContact($name, $email, $phone);
        if ($strContactId !== false && $workflowId !== null)
        {
            $this->executeWorkFlow( $strContactId, $workflowId );
        }

        //return new WP_REST_Response($d, 200);
    }
}

new clsGoHighLevel();

if (!function_exists('p_r')){function p_r($s){echo "<pre>";print_r($s);echo "</pre>";}}