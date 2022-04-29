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

class ViewProPlanDiscountAction implements RequestHandlerInterface
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
        $this->logger->info('ViewProPlanDiscountAction: handler dispatched');
        $commonHelper = new CommonHelper();
        $data = $request->getParsedBody();
        try {
            $id = $commonHelper->resolveArg($request,'id');
            $comp_id = $request->getAttribute('compid');
            $this->logger->info('ViewProPlanDiscountAction: promo id'.$id);
            $db =  $this->connection;
            $sql = $db->prepare('SELECT * from product_plan_discount where id=:id and comp_id=:comp_id');
            $sql->execute(array(':id' => $id,':comp_id' => $comp_id));
            $data = $sql->fetchAll(PDO::FETCH_OBJ);
            $response = new Response();
            if(count($data)>0){
                $sql = $db->prepare('SELECT * from map_promo_discount mp LEFT JOIN product_plan_promos pp ON mp.promo_id=pp.id  where mp.discount_id=:discount_id and mp.comp_id=:comp_id and pp.is_active=:status;');
                $sql->execute(array(':discount_id' => $id,':comp_id' => $comp_id,':status' => 1));
                $promoData = $sql->fetchAll(PDO::FETCH_OBJ);
                $data['promo'] = $promoData;
                $this->logger->info('ViewProPlanDiscountAction: Product plan discount found"'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 1,
                        "message" => "Product plan promo found",
                        "result" => $data
                    ))
                );
            } else {
                $this->logger->info('ViewProPlanDiscountAction: Product plan discount not found"'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "message" => "Product plan promo not found"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('ViewProPlanDiscountAction: Error in getting product plan discount detail---'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in getting product plan discount detail'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('ViewProPlanDiscountAction: Error in getting product plan discount detail---'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in getting product plan discount detail'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

   
}