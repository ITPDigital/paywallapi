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


class ListAllEmailAction implements RequestHandlerInterface
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
        $this->logger->info('ListAllEmailAction: handler dispatched');
        $data = $request->getParsedBody();
        try {
            $comp_id = $request->getAttribute('compid');
            $this->logger->info('ListAllEmailAction: Comp id'.$comp_id);
            $db =  $this->connection;
            //echo $status;exit;
            $sql = $db->prepare('SELECT et.id,et.comp_id,et.brand_id,et.email_type_id,et.from_address,et.subject,et.created_on,et.updated_on,et.is_active,b.brand_name from email_templates et LEFT JOIN brands b ON b.id=et.brand_id where et.comp_id=:comp_id;');
            $sql->execute(array(':comp_id' => $comp_id));
            $data = $sql->fetchAll(PDO::FETCH_OBJ);
            $response = new Response();
            $this->logger->info('ListAllEmailAction: Number of email templates available for company id-'.$comp_id.'-is-'.count($data));
            $response->getBody()->write(
                json_encode(array(
                    "code" => 1,
                    "status" => 1,
                    "message" => "Data found",
                    "result" => $data
                ))
            );
        }
        catch(MySQLException $e) {
            $this->logger->info('ListAllEmailAction: Error in fetching all email templates---'.$comp_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in fetching all email templates'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('ListAllEmailAction: Error in fetching all email templates---'.$comp_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in fetching all email templates'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

   
}