<?php

declare(strict_types=1);

namespace App\Application\Actions\Config\ContentType;

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

class ListAllContentTypeAction implements RequestHandlerInterface
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
        $this->logger->info('ListAllContentTypeAction: handler dispatched');
        $data = $request->getParsedBody();
        try {
            $comp_id = $request->getAttribute('compid');
            $this->logger->info('ListAllContentTypeAction: Comp id'.$comp_id);
            $db =  $this->connection;
            $sql = $db->prepare('SELECT * from config_content_type where comp_id=:comp_id');
            $sql->execute(array(':comp_id' => $comp_id));
            $data = $sql->fetchAll(PDO::FETCH_OBJ);
            $response = new Response();
            $this->logger->info('ListAllContentTypeAction: Number of content type available for company id-'.$comp_id.'-is-'.count($data));
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
            $this->logger->info('ListAllContentTypeAction: Error in fetching all content type---'.$comp_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in fetching all content type'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('ListAllContentTypeAction: Error in fetching all content type---'.$comp_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in fetching all content type'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

   
}