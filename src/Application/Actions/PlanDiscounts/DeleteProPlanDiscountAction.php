<?php

declare(strict_types=1);

namespace App\Application\Actions\PlanDiscounts;

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

class DeleteProPlanDiscountAction implements RequestHandlerInterface
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
        $this->logger->info('DeleteProPlanDiscountAction: handler dispatched');
        $commonHelper = new CommonHelper();
        $data = $request->getParsedBody();
        try {
            $id = $commonHelper->resolveArg($request,'id');
            $comp_id = $request->getAttribute('compid');
            $status = $commonHelper->resolveArg($request,'status');
            $user_id = $request->getAttribute('userid');
            $date = date('Y-m-d h:i:s');
            $this->logger->info('DeleteProPlanDiscountAction: product plan feature id'.$id);
            $db =  $this->connection;
            $response = new Response();

            $mappedDisc = $db->prepare('SELECT id from product_plan WHERE discount_id=:discount_id');
            $mappedDisc->execute(array(':discount_id' => $id));
            $mappedDisc = $mappedDisc->fetchAll(PDO::FETCH_OBJ);
            if(count($mappedDisc)>0) {//direct discount
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "result" => $mappedDisc,
                        "message" => "Discount is mapped with product plan"
                    ))
                );
                return $response->withHeader('Content-Type', 'application/json');
            } else {
                $mappedDisc = $db->prepare('SELECT plan_id as id from map_plan_discount WHERE discount_id=:discount_id and is_active=:status;');
                $mappedDisc->execute(array(':discount_id' => $id,':status' => 1));
                $mappedDisc = $mappedDisc->fetchAll(PDO::FETCH_OBJ);
                if(count($mappedDisc)>0) {//promo discount
                    $response->getBody()->write(
                        json_encode(array(
                            "code" => 1,
                            "status" => 2,
                            "result" => $mappedDisc,
                            "message" => "Discount is mapped with product plan"
                        ))
                    );
                    return $response->withHeader('Content-Type', 'application/json');
                }
            }
            $sql = $db->prepare('UPDATE product_plan_discount set is_active = :status,updated_by =:user_id, updated_on=:updated_on where id = :id and comp_id = :comp_id');
            $sql->execute(array(':status' => $status,':user_id' => $user_id,'updated_on'=> $date,':id' => $id,':comp_id' => $comp_id));
            $count = $sql->rowCount();
            if($count > 0){
                $this->logger->info('DeleteProPlanDiscountAction: Product plan discount status updated successfully'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 1,
                        "message" => "Product plan discount status updated successfully"
                    ))
                );
            } else {
                $this->logger->info('DeleteProPlanDiscountAction: Product plan discount not found'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 0,
                        "status" => 1,
                        "message" => "Product plan discount not found"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('DeleteProPlanDiscountAction: SQL error in update product plan discount status-'.$id.'---'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in update product plan discount status'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('DeleteProPlanDiscountAction: Error in update product plan discount status-'.$id.'---'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in update product plan discount status'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }
}