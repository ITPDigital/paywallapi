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

class DeleteProPlanAction implements RequestHandlerInterface
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
        $this->logger->info('DeleteProPlanAction: handler dispatched');
        $commonHelper = new CommonHelper();
        $data = $request->getParsedBody();
        try {
            $id = $commonHelper->resolveArg($request,'id');
            $comp_id = $request->getAttribute('compid');
            $status = $commonHelper->resolveArg($request,'status');
            $user_id = $request->getAttribute('userid');
            $date = date('Y-m-d h:i:s');
            $this->logger->info('DeleteProPlanAction: product plan id'.$id);
            $db =  $this->connection;
            $response = new Response();
            $mappedPlan = $db->prepare('SELECT product_id as id from map_plan_product WHERE plan_id=:plan_id and is_active=:status;');
            $mappedPlan->execute(array(':plan_id' => $id,':status' => 1));
            $mappedPlan = $mappedPlan->fetchAll(PDO::FETCH_OBJ);
            $response = new Response();
            if(count($mappedPlan)>0) {
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "result" => $mappedPlan,
                        "message" => "Plan is mapped with product"
                    ))
                );
            } else {
                $sql = $db->prepare('UPDATE product_plan set is_active = :status,updated_by =:user_id, updated_on=:updated_on  where id = :id and comp_id = :comp_id');
                $sql->execute(array(':status' => $status,':user_id' => $user_id,'updated_on'=> $date,':id' => $id,':comp_id' => $comp_id));
                $count = $sql->rowCount();
                if($count > 0){
                    $this->logger->info('DeleteBrandAction: Product plan status updated successfully'.$id);
                    $response->getBody()->write(
                        json_encode(array(
                            "code" => 1,
                            "status" => 1,
                            "message" => "Product plan updated successfully"
                        ))
                    );
                } else {
                    $this->logger->info('DeleteBrandAction: Product plan not found'.$id);
                    $response->getBody()->write(
                        json_encode(array(
                            "code" => 0,
                            "status" => 1,
                            "message" => "Product plan not found"
                        ))
                    );
                }
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('DeleteBrandAction: SQL error in updating brand status--'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'SQL error in updating brand status'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('DeleteBrandAction: Error in updating brand status--'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in updating brand status'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }
}