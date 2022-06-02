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


class ViewUserWithOrderAction implements RequestHandlerInterface
{
    private $logger;
    private $connection;
    private array $args;

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
        $this->logger->info('ViewUserWithOrderAction: handler dispatched');
        $commonHelper = new CommonHelper();
        $data = $request->getParsedBody();
        $orderData = [];
        $userData = [];
        try {
            $id = $commonHelper->resolveArg($request,'id');
            $brand = $request->getAttribute('brandid');//customer portal
            if(!$brand) {
                $brand = $commonHelper->resolveArg($request,'brandId');//admin portal
            }
            $this->logger->info('ViewUserWithOrderAction: user id'.$id);
            $db =  $this->connection;
            $sql = $db->prepare('SELECT * from users u LEFT JOIN user_details ud on ud.user_id=u.id WHERE u.id=:id AND u.brand_id=:brand;');
            $sql->execute(array(':id' => $id,':brand' => $brand));
            $userData = $sql->fetchAll(PDO::FETCH_OBJ);
            $response = new Response();
            if(count($userData)>0){
                $sql = $db->prepare('SELECT id, product_id, product_plan_id, start_date, end_date, status from user_license WHERE user_id=:id AND brand_id=:brand;');
                $sql->execute(array(':id' => $id,':brand' => $brand));
                $orderData = $sql->fetchAll(PDO::FETCH_OBJ);
                $data = array('userObj' => $userData, 
                    'orderObj' => $orderData
                );
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 1,
                        "message" => "User found",
                        "result" => $data
                    ))
                );
            } else {
                $this->logger->info('ViewUserWithOrderAction: User not found'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "message" => "User not found"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('ViewUserWithOrderAction: SQL error in getting user detail for user id-----'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'SQL error in getting user detail'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('ViewUserWithOrderAction: Error in getting user detail for user id-----'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in getting user detail'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

   
}