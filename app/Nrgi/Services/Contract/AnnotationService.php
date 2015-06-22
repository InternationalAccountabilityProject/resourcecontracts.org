<?php namespace App\Nrgi\Services\Contract;

use App\Nrgi\Entities\Contract\Annotation;
use App\Nrgi\Repositories\Contract\AnnotationRepositoryInterface;
use App\Nrgi\Services\Contract\Comment\CommentService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Logging\Log;

/**
 * Class AnnotationService
 * @package Nrgi\Services\Contract
 */
class AnnotationService
{
    /**
     * @var AnnotationRepositoryInterface
     */
    protected $annotation;
    /**
     * @var Guard
     */
    protected $auth;
    /**
     * @var DatabaseManager
     */
    protected $database;
    /**
     * @var Comment
     */
    protected $comment;
    /**
     * @var Log
     */
    protected $logger;
    /**
     * @var ContractService
     */
    protected $contract;

    /**
     * Constructor
     * @param AnnotationRepositoryInterface $annotation
     * @param Guard                         $auth
     * @param DatabaseManager               $database
     * @param Comment|CommentService        $comment
     * @param LoggerInterface|Log           $logger
     * @param ContractService               $contract
     */

    public function __construct(
        AnnotationRepositoryInterface $annotation,
        Guard $auth,
        DatabaseManager $database,
        CommentService $comment,
        Log $logger,
        ContractService $contract
    ) {
        $this->annotation = $annotation;
        $this->auth       = $auth;
        $this->user       = $auth->user();
        $this->database   = $database;
        $this->comment    = $comment;
        $this->logger     = $logger;
        $this->contract   = $contract;
    }

    /**
     * Store/Update a contact annotation.
     * @param $annotation
     * @param $inputData
     * @return mixed
     */
    public function save($annotation, $inputData)
    {
        $data                                = json_decode($annotation, true);
        $contactAnnotation                   = $this->annotation->findOrCreate(
            isset($data['id']) ? $data['id'] : null
        );
        $contactAnnotation->annotation       = $data;
        $contactAnnotation->user_id          = $this->user->id;
        $contactAnnotation->contract_id      = $inputData['contract'];
        $contactAnnotation->url              = $inputData['url'];
        $contactAnnotation->document_page_no = $inputData['document_page_no'];
        $contactAnnotation->page_id          = $inputData['page_id'];
        $logMessage                          = 'annotation.annotation_created';
        if (isset($data['id'])) {
            $logMessage = 'annotation.annotation_updated';
        }
        $this->logger->activity(
            $logMessage,
            ['contract' => $inputData['contract'], 'page' => $inputData['document_page_no']],
            $inputData['contract']
        );

        return $this->annotation->save($contactAnnotation);
    }

    /**
     * @param       $annotation
     * @param array $inputs
     * @return boolean
     */
    public function delete($annotation, $inputs)
    {
        $contactAnnotationId = $inputs['id'];
        if ($this->annotation->delete($contactAnnotationId)) {
            $this->logger->activity('annotation.annotation_deleted', [$contactAnnotationId], $inputs['contract']);

            return true;
        }

        return false;
    }

    /**
     * search annotation
     * @param array $params
     * @return mixed
     */
    public function search(array $params)
    {
        $annotationData = [];
        $annotations    = $this->annotation->search($params);

        foreach ($annotations as $annotation) {
            $json             = $annotation->annotation;
            $json->id         = $annotation->id;
            $annotationData[] = $json;
        }

        return array('total' => count($annotationData), 'rows' => $annotationData);
    }

    /**
     * @param $contractId
     * return List of annotation
     */
    public function getAllByContractId($contractId)
    {
        return $this->annotation->getAllByContractId($contractId);
    }

    /**
     * @param $contractId
     * @return \App\Nrgi\Repositories\Contract\contract
     */
    public function getContractPagesWithAnnotations($contractId)
    {
        return $this->annotation->getContractPagesWithAnnotations($contractId);
    }

    /**
     * @param $contractId
     * @return annotation status
     */
    public function getStatus($contractId)
    {
        return $this->annotation->getStatus($contractId);
    }

    /**
     * @param $status
     * @param $contractId
     * @return bool
     */
    public function updateStatus($annotationStatus, $contractId)
    {
        $status = $this->annotation->updateStatus($annotationStatus, $contractId);
        if ($status) {
            $this->logger->activity(
                "annotation.status_update",
                ['status' => $annotationStatus],
                $contractId
            );
            $this->logger->info(
                'Annotation status updated.',
                ['Contract id' => $contractId, 'status' => $annotationStatus]
            );
        }

        return $status;
    }

    /**
     * @param $contractId
     * @param $message
     * @param $type
     * @return bool
     */
    public function comment($contractId, $message)
    {
        $this->database->beginTransaction();
        $status = $this->updateStatus(Annotation::REJECTED, $contractId);

        if ($status) {
            try {
                $this->comment->save($contractId, $message, "annotation");
                $this->logger->info(
                    'Comment successfully added.',
                    ['Contract id' => $contractId, 'type' => 'annotation']
                );
                $this->database->commit();

                return true;
            } catch (Exception $e) {
                $this->database->rollback();
                $this->logger->error($e->getMessage());
            }
        }

        return false;
    }
}
