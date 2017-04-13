<?php

namespace RdstationPhpClient;

class RdstationPhpClient
{
    private $leadData;
    private $privateToken;
    private $publicToken;
    private $identifier;
    private $requiredFields = ['email', 'token_rdstation', 'identificador'];
    private $apiUrl = "http://www.rdstation.com.br/api/1.2/conversions";
    private $apiUrlLead = "http://www.rdstation.com.br/api/1.2/leads/";

    /**
     * setLeadData
     * @param array $leadData
     */
    public function setLeadData(array $leadData)
    {
        $this->leadData = $leadData;
    }

    /**
     * setPrivateToken
     * @param string $privateToken
     */
    public function setPrivateToken($privateToken)
    {
        $this->privateToken = $privateToken;
    }

    /**
     * setPublicToken
     * @param string $publicToken
     */
    public function setPublicToken($publicToken)
    {
        $this->publicToken = $publicToken;
    }

    /**
     * setIdentifier
     * @param string $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * Method for ignore fields of leadData
     * @param  array  $fields
     * @return void
     */
    public function ignoreFields(array $fields)
    {
        foreach ($this->leadData as $field => $value) {
            if (in_array($field, $fields)) {
                unset($this->leadData[$field]);
            }
        }
    }

    /**
     * Method for validate fields of leadData
     * @param  array $data
     * @return bool
     */
    private function canSaveLead(array $data)
    {
        foreach ($this->requiredFields as $field) {
            if (empty($data[$field]) || is_null($data[$field])) {
                return false;
            }
        }
        return strlen($data['token_rdstation']) == 32 ? true : false;
    }

    /**
     * Method for get errors for validation of leadData
     * @return array
     */
    private function getError()
    {
        $message = [];
        $dataArray = $this->leadData;
        $dataArray['token_rdstation'] = $this->publicToken;
        $dataArray['identificador'] = $this->identifier;
        // check error
        foreach ($this->requiredFields as $field) {
            if (empty($dataArray[$field]) || is_null($dataArray[$field])) {
                $message[] = 'This field '.$field.' can\'t is empty.';
            }
        }

        if (!strlen($dataArray['token_rdstation']) == 32) {
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

    /**
     * Method for create lead in RDStation
     * @return array
     */
    public function createLead()
    {

        $dataArray = $this->leadData;
        $dataArray['token_rdstation'] = $this->publicToken;
        $dataArray['identificador'] = $this->identifier;

        if ($this->canSaveLead($dataArray)) {
            $dataJson = json_encode($dataArray);
            $header = [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($dataJson)
            ];

            $ch = curl_init($this->apiUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataJson);
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

    public function updateLeadStageAndOpportunity($email, $newStage = 0, $opportunity = false)
    {
        if (empty($email)) {
            return [
                'status' => false,
                'message' => 'Inform lead email as the first argument.',
            ];
        }

        $url = $this->apiUrlLead.$email;
        $data = [
            "auth_token" => $this->privateToken,
            "lead" => [
                "lifecycle_stage" => $newStage,
                "opportunity" => $opportunity
            ]
        ];

        return $this->request("PUT", $url, $data);
    }

    public function request($method = "POST", $url, $data = [])
    {
        $data['token_rdstation'] = $this->privateToken;
        $JSONData = json_encode($data);
        $URLParts = parse_url($url);
        $fp = fsockopen(
            $URLParts['host'],
            isset($URLParts['port'])?$URLParts['port']:80,
            $errno,
            $errstr,
            30
        );
        $out = $method." ".$URLParts['path']." HTTP/1.1\r\n";
        $out .= "Host: ".$URLParts['host']."\r\n";
        $out .= "Content-Type: application/json\r\n";
        $out .= "Content-Length: ".strlen($JSONData)."\r\n";
        $out .= "Connection: Close\r\n\r\n";
        $out .= $JSONData;
        $written = fwrite($fp, $out);
        fclose($fp);
        return ($written==false)?false:true;
    }
}
