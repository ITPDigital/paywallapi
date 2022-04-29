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


class ListProPlanAction implements RequestHandlerInterface
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
        $this->logger->info('ListProPlanAction: handler dispatched');
        try {
            $comp_id =  $request->getAttribute('compid');
            $this->logger->info('ListProPlanAction: Comp id'.$comp_id);
            $db =  $this->connection;
            $sql = $db->prepare('SELECT pp.id, pp.plan_name, pp.plan_display_name, pp.frequency, pp.final_price, pp.created_on, pp.updated_on, pp.is_active, pp.currency,cc.symbol,cp.display_name AS frequency_str from product_plan pp LEFT JOIN const_currencies cc ON pp.currency=cc.disp_id LEFT JOIN const_periods cp ON cp.disp_id=pp.frequency where pp.comp_id=:comp_id');
            $sql->execute(array(':comp_id' => $comp_id));
            $data = $sql->fetchAll(PDO::FETCH_OBJ);
            $response = new Response();
            $this->logger->info('ListProPlanAction: Number of product plans available for company id-'.$comp_id.'-is-'.count($data));
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
            $this->logger->info('ListBrandAction: SQL error in fetching all product plans--'.$comp_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'SQL error in fetching all product plans'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('ListBrandAction: Error in fetching all product plans--'.$comp_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in fetching all product plans'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

   
}