<?php

namespace App\Nrgi\Services\Quality;

use App\Nrgi\Repositories\Contract\ContractRepositoryInterface;

/**
 * Checks the quality of Metadata,Text and Annotations
 *
 * Class QualityService
 * @package App\Nrgi\Quality
 */
class QualityService
{
    /**
     * @var ContractRepositoryInterface
     */
    protected $contract;

    /**
     * @param ContractRepositoryInterface $contract
     */
    public function __construct(ContractRepositoryInterface $contract)
    {
        $this->contract = $contract;
    }

    /**
     * Get the count of presence of contract's metadata
     *
     * @return array
     */
    public function getMetadataQuality()
    {
        $metadata       = [];
        $metadataSchema = config('metadata.schema.metadata');
        unset($metadataSchema['file_size'], $metadataSchema['company'], $metadataSchema['concession']);
        foreach ($metadataSchema as $key => $value) {
            $count          = $this->contract->getMetadataQuality($key);
            $metadata[$key] = $count;
        }
        $multipleMetadata                    = $this->contract->getQualityCountOfMultipleMeta();
        $multipleMetadata                    = $multipleMetadata[0]->get_quality_issue;
        $data                                = str_replace(['(', ')'], ['', ''], $multipleMetadata);
        $data                                = explode(',', $data);
        $metadata["Government Entity"] = $data[2];
        $metadata["Company"]           = $data[0];
        $metadata["Concession"]        = $data[1];

        return $metadata;
    }

    /**
     * Get the count of presence of annotation's category
     *
     * @return array
     */
    public function getAnnotationsQuality()
    {
        $annotations         = [];
        $annotationsCategory = trans('codelist/annotation.annotation_category');
        foreach ($annotationsCategory as $key => $value) {
            $response          = $this->contract->getAnnotationsQuality($key);
            $count             = !empty($response) ? count($response) : 0;
            $annotations[$key] = $count;
        }

        return $annotations;
    }

    /**
     * Get the total contract count
     *
     * @return int
     */
    public function getTotalContractCount()
    {
        return $this->contract->getTotalContractCount();
    }


}