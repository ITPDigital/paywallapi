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


class ListProPlanDiscountAction implements RequestHandlerInterface
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
        $this->logger->info('ListProPlanDiscountAction: handler dispatched');
        $data = $request->getParsedBody();
        try {
            $comp_id = $request->getAttribute('compid');
            $this->logger->info('ListProPlanDiscountAction: comp_id'.$comp_id);
            $db =  $this->connection;
            $sql = $db->prepare('SELECT ppd.*,cp.display_name AS frequency,cc.symbol from product_plan_discount ppd LEFT JOIN const_periods cp ON cp.disp_id=ppd.discount_period LEFT JOIN const_currencies cc ON ppd.currency=cc.disp_id where ppd.comp_id=:comp_id');
            $sql->execute(array(':comp_id' => $comp_id));
            $data = $sql->fetchAll(PDO::FETCH_OBJ);
            $response = new Response();
            $this->logger->info('ListProPlanDiscountAction: Number of product plan discounts available for comp id-'.$comp_id.'-is-'.count($data));
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
            $this->logger->info('ListProPlanDiscountAction: Error in fetching all product plan discounts for comp id-'.$comp_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in fetching all product plan discounts'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('ListProPlanDiscountAction: Error in fetching all product plan discounts for comp id-'.$comp_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in fetching all product plan discounts'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

   
}