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


class ViewTransHistoryAction implements RequestHandlerInterface
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
        $this->logger->info('ViewTransHistoryAction: handler dispatched');
        try {
            $commonHelper = new CommonHelper();
            $user_id = $commonHelper->resolveArg($request,'userId');
            $brand_id = $commonHelper->resolveArg($request,'brandId'); 
            $order_id = $commonHelper->resolveArg($request,'orderId');          
            $this->logger->info('ViewTransHistoryAction: user_id'.$user_id.'--brand_id'.$brand_id.'--order_id'.$order_id);
            $db =  $this->connection;
            $sql = $db->prepare('SELECT * from user_transaction_history where brand_id = :brand_id AND user_id = :user_id AND user_license_id = :order_id;');
            $sql->execute(array(':brand_id' => $brand_id, ':user_id' => $user_id, ':order_id' => $order_id));
            $transData = $sql->fetchAll(PDO::FETCH_OBJ);
            $response = new Response();
            $this->logger->info('ViewTransHistoryAction: Transaction available for user_id-'.$user_id.'--order_id'.$order_id);
            if(count($transData)>0){
                $sql = $db->prepare('SELECT * from user_license WHERE brand_id = :brand_id AND user_id = :user_id AND id = :order_id;');
                $sql->execute(array(':brand_id' => $brand_id, ':user_id' => $user_id, ':order_id' => $order_id));
                $orderData = $sql->fetchAll(PDO::FETCH_OBJ);
                $sql = $db->prepare('SELECT * from user_purchase_history WHERE brand_id = :brand_id AND user_id = :user_id AND user_license_id = :order_id;');
                $sql->execute(array(':brand_id' => $brand_id, ':user_id' => $user_id, ':order_id' => $order_id));
                $purchaseData = $sql->fetchAll(PDO::FETCH_OBJ);
                $data = array('transObj' => $transData, 
                    'purchaseObj' => $purchaseData,
                    'orderObj' => $orderData
                );
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 1,
                        "message" => "Order found",
                        "result" => $data
                    ))
                );
            } else {
                $this->logger->info('ViewTransHistoryAction: Transaction history not found'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "message" => "Transaction history not found"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('ViewTransHistoryAction: Error in fetching user transactions for user_id-'.$user_id.'--order_id'.$order_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in fetching user transactions'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('ViewTransHistoryAction: Error in fetching user transactions for user_id-'.$user_id.'--order_id'.$order_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in fetching user transactions'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

   
}