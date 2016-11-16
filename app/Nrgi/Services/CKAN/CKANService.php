<?php namespace App\Nrgi\Services\CKAN;

use App\Nrgi\Services\Contract\ContractService;
use GuzzleHttp\Client;
use App\Nrgi\Entities\Contract\Contract;
use Exception;

/**
 * Class CKANService
 * @package App\Nrgi\Services\CKAN
 */
class CKANService
{
    /**
     * @var Client
     */
    protected $http;
    /**
     * @var ContractService
     */
    protected $contract;

    /**
     * @param Client          $http
     * @param ContractService $contractService
     * @param Contract        $contract
     */
    public function __construct(Client $http, Contract $contract, ContractService $contractService)
    {
        $this->http            = $http;
        $this->contract        = $contract;
        $this->contractSerivce = $contractService;
    }

    public function callCkanApi($data)
    {
        $dataToCkan = $data;

        if ($dataToCkan["is_supporting_document"]) {
            //echo "inside support";
            $parentContractId = $this->contract->getParentContract();
            //var_dump($parentContractId);
            //echo "down";
        } else {
            // this runs for parent document i.e if contract is not a supporting document
            echo "outside";
            $datasetName = (string) $dataToCkan["contract_id"];
            if ($this->datasetExists($datasetName)) {
                $data = $this->prepareResourceDataForCkan($datasetName, $dataToCkan);
                $res  = $this->createResourceInCkan($data);
            } else {
                try {
                    //working
                    $this->createDatasetInCkan($datasetName);
                    $data = $this->prepareResourceDataForCkan($datasetName, $dataToCkan);
                    $this->createResourceInCkan($data);
                } catch (Exception $e) {
                    echo 'Error occurred during dataset creation: ', $e->getMessage(), "\n";
                }
            }
        }
    }

    public function datasetExists($name)
    {
        $datasets = null;
        try {
            $datasets = $this->http->get('http://demo.ckan.org/api/3/action/package_show?id=nrgi-test-'.$name);
            $datasets = $datasets->json();
            $status   = ($datasets['success'] == 1) ? true : false;
        } catch (Exception $e) {
            $status = $datasets['success'] == 1 ? true : false;
        }

        return $status;
    }

    public function prepareResourceDataForCkan($datasetName, $dataToCkan)
    {
        $data = [
            "package_id"  => 'nrgi-test-'.$datasetName,
            "id"          => (string) $dataToCkan["contract_id"],
            "name"        => $dataToCkan["contract_name"],
            "format"      => "PDF",
            "url"         => $dataToCkan["file_url"],
            "license"     => $dataToCkan["license"],
            "description" => $dataToCkan["contract_name"],
        ];

        return $data;
    }

    public function createResourceInCkan($data)
    {
        $res = $this->http->post(
            'http://demo.ckan.org/api/action/resource_create',
            [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                    'Authorization' => '2b89ee7d-44ee-4854-9931-a5276177163f',
                ],
                'body'    => json_encode($data),
            ]
        );

        return $res->json();
    }

    public function createDatasetInCkan($datasetName)
    {
        $data = [
            "name"  => 'nrgi-test-'.$datasetName,
            "title" => $datasetName,
        ];
        $res  = $this->http->post(
            'http://demo.ckan.org/api/action/package_create',
            [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                    'Authorization' => '2b89ee7d-44ee-4854-9931-a5276177163f',
                ],
                'body'    => json_encode($data),
            ]
        );

        return $res;
    }
}