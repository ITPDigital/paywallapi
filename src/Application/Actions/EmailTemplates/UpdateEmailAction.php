<?php

declare(strict_types=1);

namespace App\Application\Actions\EmailTemplates;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Response;
use Cake\Validation\Validator;
use Selective\Validation\ValidationResult;
use Selective\Validation\Factory\CakeValidationFactory;
use Selective\Validation\Exception\ValidationException;
use PDO;
use \Firebase\JWT\JWT;
use App\Application\Helpers\CommonHelper;
use App\Application\Helpers\EmailHelper;


class UpdateEmailAction implements RequestHandlerInterface
{
    private $logger;
    private $connection;

    public function __construct(PDO $connection,LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {	
        $commonHelper = new CommonHelper();
        $this->logger->info('UpdateEmailAction: handler dispatched');
        $data = $request->getParsedBody();
        $validateData = (array)$request->getParsedBody();

        // Within the Application Service class: Do the validation
        $validationFactory = new CakeValidationFactory();
        $validator = $validationFactory->createValidator();
		//Form validation
        $validator
            ->notEmptyString('emailType', 'Field required')
			->requirePresence('emailType')
            ->notEmptyString('brandId', 'Field required')
			->requirePresence('brandId')
            ->notEmptyString('fromAddress', 'Field required')
			->requirePresence('fromAddress')
            ->notEmptyString('subject', 'Field required')
			->requirePresence('subject')
            ->notEmptyString('template', 'Field required')
			->requirePresence('template')
            ->notEmptyString('status', 'Field required')
			->requirePresence('status');

        $validationResult = $validationFactory->createValidationResult(
            $validator->validate($validateData)
        );
		//throw exception for validation failure
        if ($validationResult->fails()) {
            throw new ValidationException('Please check your input', $validationResult);
        }
        try {
            $id = $commonHelper->resolveArg($request,'id');
            $comp_id = $request->getAttribute('compid');
            $emailType = isset($data['emailType']) ? $data["emailType"] : '';
            $brandId = isset($data['brandId']) ? $data["brandId"] : '';
            $fromAddress = isset($data['fromAddress']) ? $data["fromAddress"] :  '';
            $subject = isset($data['subject']) ? $data["subject"] :  '';
            $template = isset($data['template']) ? $data["template"] :  '';
            $status = isset($data['status']) ? $data["status"] :  '';
            $user_id = $request->getAttribute('userid');
            $date = date('Y-m-d h:i:s');
            $this->logger->info('UpdateEmailAction: widgetId'.$id);
            $db =  $this->connection;
            $response = new Response();
            $emailHelper = new EmailHelper();
            $isRuleExists = $emailHelper->isRuleExists($db, $brandId, $emailType, $comp_id, $id);
            if($isRuleExists>0) {
                $this->logger->info('UpdateEmailAction: Rule already exists'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "message" => "Rule already exists"
                    ))
                );
                return $response->withHeader('Content-Type', 'application/json');
            }
            $sql = $db->prepare("UPDATE email_templates set brand_id = :brand_id,email_type_id=:email_type_id,from_address=:from_address,subject=:subject,template=:template,updated_by=:updated_by,updated_on=:updated_on,is_active=:is_active where id = :id and comp_id=:comp_id");		
            $sql->execute(array(':brand_id'=>$brandId,
            ':email_type_id' => $emailType,
            ':from_address' => $fromAddress, 
            ':subject' => $subject, 
            ':template' => $template,
            ':updated_by' => $user_id,
            ':updated_on' => $date,
            ':is_active' => $status, 
            ':id' => $id,
            ':comp_id' => $comp_id));
            $count = $sql->rowCount();
            if($count) {
                $this->logger->info('UpdateEmailAction: Email template data updated successfully'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 1,
                        "message" => "Email template data updated successfully",
                        "id" => (int)$id,
                        "count" => $isRuleExists,
                    ))
                );
            } else {
                $this->logger->info('UpdateEmailAction: Failed to update Email template'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 3,
                        "message" => "Failed to update Email template"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('UpdateEmailAction: Error in updating email template detail---'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in updating email template detail'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('UpdateEmailAction: Error in updating email template detail---'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in updating email template detail'
                ))
            );
        }
		return $response->withHeader('Content-Type', 'application/json');
    }
}