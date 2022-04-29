<?php

declare(strict_types=1);

namespace App\Application\Actions\Products;

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


class ListProductAction implements RequestHandlerInterface
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
        $this->logger->info('ListProductAction: handler dispatched');
        $data = $request->getParsedBody();
        try {
            $comp_id = $request->getAttribute('compid');
            $this->logger->info('ListProductAction: comp_id'.$comp_id);
            $db =  $this->connection;
            $sql = $db->prepare('SELECT p.*,b.brand_name  from products p LEFT JOIN brands b ON p.brand_id=b.id WHERE p.comp_id=:comp_id');
            $sql->execute(array(':comp_id' => $comp_id));
            $data = $sql->fetchAll(PDO::FETCH_OBJ);
            $response = new Response();
            $this->logger->info('ListProductAction: Number of products available for comp id-'.$comp_id.'-is-'.count($data));
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
            $this->logger->info('ListProductAction: Error in fetching all products for comp id-'.$comp_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in fetching all products'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('ListProductAction: Error in fetching all products for comp id-'.$comp_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in fetching all products'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

   
}