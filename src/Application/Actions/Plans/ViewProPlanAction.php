<?php

declare(strict_types=1);

namespace App\Application\Actions\Plans;

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

class ViewProPlanAction implements RequestHandlerInterface
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
        $this->logger->info('ViewProPlanAction: handler dispatched');
        $commonHelper = new CommonHelper();
        $data = $request->getParsedBody();
        try {
            $id = $commonHelper->resolveArg($request,'id');
            $comp_id = $request->getAttribute('compid');
            $this->logger->info('ViewProPlanAction: plan id'.$id);
            $db =  $this->connection;
            $sql = $db->prepare('SELECT * from product_plan where id=:id and comp_id=:comp_id');
            $sql->execute(array(':id' => $id,':comp_id' => $comp_id));
            $data = $sql->fetchAll(PDO::FETCH_OBJ);
            $response = new Response();
            if(count($data)>0){
                $sql = $db->prepare('SELECT md.id,md.discount_id,md.plan_id,pp.id AS pp_plan_id,md.comp_id,md.is_active from map_plan_discount md LEFT JOIN product_plan pp ON md.plan_id=pp.id where md.plan_id=:plan_id and md.comp_id=:comp_id and pp.is_active=:status and md.is_active=:status;');
                $sql->execute(array(':plan_id' => $id,':comp_id' => $comp_id,':status' => 1));
                $discData = $sql->fetchAll(PDO::FETCH_OBJ);
                $data['discounts'] = $discData;
                $sql = $db->prepare('SELECT * from product_plan_features where product_plan_id=:product_plan_id and comp_id=:comp_id and is_active=:status');
                $sql->execute(array(':product_plan_id' => $id,':comp_id' => $comp_id,':status' => 1));
                $featureData = $sql->fetchAll(PDO::FETCH_OBJ);
                $data['features'] = $featureData;
                $this->logger->info('ViewProPlanAction: Product plan found'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 1,
                        "message" => "Product plan found",
                        "result" => $data
                    ))
                );
            } else {
                $this->logger->info('ViewProPlanAction: Product plan found'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "message" => "Product plan not found"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('ViewProPlanAction: SQL Error in getting product plan details for plan id--'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'SQL Error in getting product plan details'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('ViewProPlanAction: Error in getting product plan details for plan id--'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in getting product plan details'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

   
}