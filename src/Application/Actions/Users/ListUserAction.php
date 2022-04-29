<?php

declare(strict_types=1);

namespace App\Application\Actions\Users;

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


class ListUserAction implements RequestHandlerInterface
{
    private $logger;
    private $connection;

    public function __construct(PDO $connection,LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function isValidUserSession($userSession) {
        if($userSession >= strtotime('now')) {
            return true;
        } else {
            return false;
        }
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->logger->info('ListUserAction: handler dispatched');
        $data = $request->getParsedBody();
        $commonHelper = new CommonHelper();
        try {
            $brand =  $commonHelper->resolveArg($request,'brandId');
            $this->logger->info('ListUserAction: brand_id'.$brand);
            $db =  $this->connection;
            $sql = $db->prepare('SELECT id,brand_id,first_name,last_name,email,status from users where brand_id=:brand');
            $sql->execute(array(':brand' => $brand));
            $data = $sql->fetchAll(PDO::FETCH_OBJ);
            $response = new Response();
            $this->logger->info('ListUserAction: Number of users available for brand id-'.$brand.'-is-'.count($data));
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
            $this->logger->info('ListUserAction: SQL error in getting all users for brand-----'.$brand.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'SQL error in getting all users'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('ListUserAction: Error in getting all users for brand-----'.$brand.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in getting all users'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

   
}