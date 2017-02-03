<?php

namespace RdstationPhpClient;

class RdstationPhpClient
{
    public $form_data;
    public $token;
    public $identifier;
    public $redirect_success = null;
    public $redirect_error = null;

    private $api_url = "http://www.rdstation.com.br/api/1.2/conversions";

    public function __construct($form_data)
    {
        $this->form_data = $form_data;
    }

    public function ignoreFields(array $fields)
    {
        foreach ($this->form_data as $field => $value) {
            if (in_array($field, $fields)) {
                unset($this->form_data[$field]);
            }
        }
    }

    private function canSaveLead($data)
    {
        $required_fields = ['email', 'token_rdstation', 'identificador'];
        foreach ($required_fields as $field) {
            if (empty($data[$field]) || is_null($data[$field])) {
                return false;
            }
        }
        return strlen($data['token_rdstation']) == 32 ? true : false;
    }

    public function createLead()
    {

        $data_array = $this->form_data;
        $data_array['token_rdstation'] = $this->token;
        $data_array['identificador'] = $this->identifier;

        if (empty($data_array["c_utmz"])) {
            $data_array["c_utmz"] = $_COOKIE["__utmz"];
        }

        if (isset($_COOKIE["__trf_src"]) && empty($data_array["traffic_source"])) {
            $data_array["traffic_source"] = $_COOKIE["__trf_src"];
        }

        if (empty($data_array["client_id"]) && !empty($_COOKIE["rdtrk"])) {
            $data_array["client_id"] = json_decode($_COOKIE["rdtrk"])->{'id'};
        }

        $data_query = http_build_query($data_array);

        if ($this->canSaveLead($data_array)) {
            if (in_array('curl', get_loaded_extensions())) {
                $data_json = json_encode($data_array);
                $header = [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data_json)
                ];

                $ch = curl_init($this->api_url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                curl_exec($ch);
                curl_close($ch);
            } else {
                $params = [
                    'http' => [
                    'method' => 'POST',
                    'content' => $data_query,
                    'ignore_errors' => true
                    ]
                ];
                $ctx = stream_context_create($params);
                $fp = @fopen($api_url, 'rb', false, $ctx);
            }
            $this->redirect_success ? header("Location: ".$this->redirect_success) : header("Location: /");
        } else {
            $this->redirect_error ? header("Location: ".$this->redirect_error) : header("Location: /");
        }
    }
}
