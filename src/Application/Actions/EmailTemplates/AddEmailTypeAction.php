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


class AddEmailTypeAction implements RequestHandlerInterface
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
        $this->logger->info('AddEmailTypeAction: handler dispatched');
        $data = $request->getParsedBody();
        $validateData = (array)$request->getParsedBody();

        try {
            $comp_id = $request->getAttribute('compid');
            $user_id = $request->getAttribute('userid');
            $email_type = isset($data['email_type']) ? $data["email_type"] : '';
            $date = date('Y-m-d h:i:s');
            $this->logger->info('AddEmailTypeAction: email_type'.$email_type);
            $db =  $this->connection;
            $response = new Response();
            $sql = $db->prepare("INSERT INTO email_type (comp_id, email_temp_name, created_by, created_on) VALUES (:comp_id, :email_temp_name, :created_by, :created_on)");
            $sql->execute(array(':comp_id' => $comp_id, ':email_temp_name' => $email_type, ':created_by' => $user_id, ':created_on' => $date));
            $count = $sql->rowCount();
            $lastinserid = $db->lastInsertId();
            if($count && $lastinserid) {
                $this->logger->info('AddEmailTypeAction: New Email Type created successfully'.$email_type);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 1,
                        "message" => "New email type created successfully",
                        "id" => (int)$lastinserid
                    ))
                );
            } else {
                $this->logger->info('AddEmailTypeAction: Failed to create new email type'.$email_type);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 4,
                        "message" => "Failed to create new email type"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('AddEmailTypeAction: Error in creating new email type for comp id--'.$comp_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in creating new email type for comp id'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('AddEmailTypeAction: Error in creating new email type for comp id---'.$comp_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in creating new email type for comp id'
                ))
            );
        }
		return $response->withHeader('Content-Type', 'application/json');
    }
}