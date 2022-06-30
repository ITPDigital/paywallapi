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
use App\Application\Helpers\EmailHelper;

class AddEmailAction implements RequestHandlerInterface
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
        $this->logger->info('AddEmailAction: handler dispatched');
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
            $comp_id = $request->getAttribute('compid');
            $emailType = isset($data['emailType']) ? $data["emailType"] : '';
            $brandId = isset($data['brandId']) ? $data["brandId"] : '';
            $fromAddress = isset($data['fromAddress']) ? $data["fromAddress"] :  '';
            $subject = isset($data['subject']) ? $data["subject"] :  '';
            $template = isset($data['template']) ? $data["template"] :  '';
            $status = isset($data['status']) ? $data["status"] :  '';
            $user_id = $request->getAttribute('userid');
            $date = date('Y-m-d h:i:s');
            $this->logger->info('AddEmailAction: emailType'.$emailType.'-brand-'.$brandId);
            $db =  $this->connection;
            $response = new Response();
            $emailHelper = new EmailHelper();
            $isRuleExists = $emailHelper->isRuleExists($db, $brandId, $emailType, $comp_id, 0);
            if($isRuleExists>0) {
                $this->logger->info('AddEmailAction: Rule already exists'.$emailType);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "message" => "Rule already exists"
                    ))
                );
                return $response->withHeader('Content-Type', 'application/json');
            }
            $sql = $db->prepare("INSERT INTO email_templates (comp_id, brand_id, email_type_id, from_address, subject, template, created_by, created_on, is_active) VALUES 
            (:comp_id, :brand_id, :email_type_id, :from_address, :subject, :template, :created_by, :created_on, :is_active)");
            $sql->execute(array(':comp_id' => $comp_id, ':brand_id' => $brandId, ':email_type_id' => $emailType, ':from_address' => $fromAddress, ':subject' => $subject, ':template' => $template, ':created_by' => $user_id, ':created_on' => $date, ':is_active' => $status));
            $lastinserid = $db->lastInsertId();
            if($lastinserid) {
                $this->logger->info('AddEmailAction: New Email template created successfully'.$emailType.'-brand-'.$brandId);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 1,
                        "message" => "New Email template created successfully",
                        "id" => (int)$lastinserid
                    ))
                );
            } else {
                $this->logger->info('AddEmailAction: Failed to create new email template'.$emailType.'-brand-'.$brandId);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "message" => "Failed to create new email template"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('AddEmailAction: Error in creating new email template--'.$emailType.'-brand-'.$brandId.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in creating new email template'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('AddEmailAction: Error in creating new email template---'.$emailType.'-brand-'.$brandId.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in creating new email template'
                ))
            );
        }
		return $response->withHeader('Content-Type', 'application/json');
    }
}