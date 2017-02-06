<?php

namespace RdstationPhpClient;

class RdstationPhpClient
{
    public $form_data;
    public $token;
    public $identifier;
    public $redirect_success = null;
    public $redirect_error = null;
    private $requiredFields = ['email', 'token_rdstation', 'identificador'];

    private $api_url = "http://www.rdstation.com.br/api/1.2/conversions";

    public function setLeadData(array $form_data)
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
        foreach ($this->requiredFields as $field) {
            if (empty($data[$field]) || is_null($data[$field])) {
                return false;
            }
        }
        return strlen($data['token_rdstation']) == 32 ? true : false;
    }

    private function getError()
    {
        $message = [];
        $data_array = $this->form_data;
        $data_array['token_rdstation'] = $this->token;
        $data_array['identificador'] = $this->identifier;
        // check error
        foreach ($this->requiredFields as $field) {
            if (empty($data_array[$field]) || is_null($data_array[$field])) {
                $message[] = 'This field '.$field.' can\'t is empty.';
            }
        }

        if (!strlen($data_array['token_rdstation']) == 32) {
            $message[] = 'Invalid token.';
        }

        if (count($message) == 0) {
            $message[] = 'Error including the lead on the RdStation.';
        }

        return [
            'status' => false,
            'message' => json_encode($message),
        ];
    }

    public function createLead()
    {

        $data_array = $this->form_data;
        $data_array['token_rdstation'] = $this->token;
        $data_array['identificador'] = $this->identifier;
        $data_query = http_build_query($data_array);

        if ($this->canSaveLead($data_array)) {
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

            return [
                'status' => true,
                'message' => 'Lead successfully included.',
            ];
        } else {
            return $this->getError();
        }
    }
}
