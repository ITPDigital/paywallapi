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

class ViewProPlanPromoAction implements RequestHandlerInterface
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
        $this->logger->info('ViewProPlanPromoAction: handler dispatched');
        $commonHelper = new CommonHelper();
        $data = $request->getParsedBody();
        try {
            $id = $commonHelper->resolveArg($request,'id');
            $comp_id = $request->getAttribute('compid');
            $this->logger->info('ViewProPlanPromoAction: promo id'.$id);
            $db =  $this->connection;
            $sql = $db->prepare('SELECT * from product_plan_promos where id=:id and comp_id=:comp_id');
            $sql->execute(array(':id' => $id,':comp_id' => $comp_id));
            $data = $sql->fetch(PDO::FETCH_ASSOC);
            $response = new Response();
            if(count($data)>0){
                $this->logger->info('ViewProPlanPromoAction: Product plan promo found"'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 1,
                        "message" => "Product plan promo found",
                        "result" => $data
                    ))
                );
            } else {
                $this->logger->info('ViewProPlanPromoAction: Product plan promo not found"'.$id);
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
            $this->logger->info('ViewProPlanPromoAction: Error in getting product plan promo detail---'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in getting product plan promo detail'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('ViewProPlanPromoAction: Error in getting product plan promo detail---'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in getting product plan promo detail'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

   
}