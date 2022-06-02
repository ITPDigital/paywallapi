<?php

declare(strict_types=1);

namespace App\Application\Actions\PlanPromos;

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

class DeleteProPlanPromoAction implements RequestHandlerInterface
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
        $this->logger->info('DeleteProPlanPromoAction: handler dispatched');
        $commonHelper = new CommonHelper();
        $data = $request->getParsedBody();
        try {
            $id = $commonHelper->resolveArg($request,'id');
            $comp_id = $request->getAttribute('compid');
            $status = $commonHelper->resolveArg($request,'status');
            $user_id = $request->getAttribute('userid');
            $date = date('Y-m-d h:i:s');
            $this->logger->info('DeleteProPlanPromoAction: product plan feature id'.$id);
            $db =  $this->connection;
            $mappedPromo = $db->prepare('SELECT discount_id as id from map_promo_discount WHERE promo_id=:promo_id and is_active=:status;');
            $mappedPromo->execute(array(':promo_id' => $id,':status' => 1));
            $mappedPromo = $mappedPromo->fetchAll(PDO::FETCH_OBJ);
            $response = new Response();
            if(count($mappedPromo)>0) {
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "result" => $mappedPromo,
                        "message" => "Promo is mapped with discount"
                    ))
                );
            } else {
                $sql = $db->prepare('UPDATE product_plan_promos set is_active = :status,updated_by =:user_id, updated_on=:updated_on  where id = :id and comp_id = :comp_id');
                $sql->execute(array(':status' => $status,':user_id' => $user_id,'updated_on'=> $date,':id' => $id,':comp_id' => $comp_id));
                $count = $sql->rowCount();
                if($count > 0){
                    $this->logger->info('DeleteProPlanPromoAction: Product plan promo status updated successfully'.$id);
                    $response->getBody()->write(
                        json_encode(array(
                            "code" => 1,
                            "status" => 1,
                            "message" => "Product plan promo status updated successfully"
                        ))
                    );
                } else {
                    $this->logger->info('DeleteProPlanPromoAction: Product plan promo not found'.$id);
                    $response->getBody()->write(
                        json_encode(array(
                            "code" => 0,
                            "status" => 1,
                            "message" => "Product plan promo not found"
                        ))
                    );
                }
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('DeleteProPlanPromoAction: Error in update product plan promo status-'.$id.'---'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in update product plan promo status'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('DeleteProPlanPromoAction: Error in update product plan promo status-'.$id.'---'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in update product plan promo status'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }
}