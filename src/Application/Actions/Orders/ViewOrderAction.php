<?php

declare(strict_types=1);

namespace App\Application\Actions\Orders;

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


class ViewOrderAction implements RequestHandlerInterface
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
        $this->logger->info('ViewOrderAction: handler dispatched');
        $data = $request->getParsedBody();
        try {
            $commonHelper = new CommonHelper();
            $user_id = $commonHelper->resolveArg($request,'userId');//$request->getAttribute('userid');
            $brand_id = $commonHelper->resolveArg($request,'brandId');//$request->getAttribute('brandid');            
            $this->logger->info('ViewOrderAction: user_id'.$user_id.'--brand_id'.$brand_id);
            $db =  $this->connection;
            $sql = $db->prepare('SELECT ul.*, p.display_name,pp.frequency from user_license ul left join products p  ON p.id=ul.product_id LEFT JOIN product_plan pp ON pp.id=ul.product_plan_id where ul.brand_id = :brand_id AND ul.user_id = :user_id;');
            $sql->execute(array(':brand_id' => $brand_id, ':user_id' => $user_id));
            $data = $sql->fetchAll(PDO::FETCH_OBJ);
            $response = new Response();
            $this->logger->info('ViewOrderAction: Order available for user_id-'.$user_id);
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
            $this->logger->info('ViewOrderAction: Error in fetching user orders for user_id-'.$user_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in fetching order'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('ViewOrderAction: Error in fetching user order for user_id-'.$user_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in fetching user order'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

   
}