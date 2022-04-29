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

class ListProPlanByStatusAction implements RequestHandlerInterface
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
        $this->logger->info('ListProPlanByStatusAction: handler dispatched');
        $commonHelper = new CommonHelper();
        try {
            $comp_id =  $request->getAttribute('compid');
            $status = $commonHelper->resolveArg($request,'status');
            $this->logger->info('ListProPlanByStatusAction: Comp id'.$comp_id);
            $db =  $this->connection;
            $sql = $db->prepare('SELECT id,plan_name,plan_display_name from product_plan where comp_id=:comp_id and is_active=:status');
            $sql->execute(array(':comp_id' => $comp_id, ':status' => $status));
            $planDatas = $sql->fetchAll(PDO::FETCH_ASSOC);
            $response = new Response();
            $response->getBody()->write(
                json_encode(array(
                    "code" => 1,
                    "status" => 1,
                    "message" => "Data found",
                    "result" => $planDatas
                ))
            );
        }
        catch(MySQLException $e) {
            $this->logger->info('ListProPlanByStatusAction: SQL error in fetching all product plans--'.$comp_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'SQL error in fetching all product plans'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('ListProPlanByStatusAction: Error in fetching all product plans--'.$comp_id.'----'.$e->getMessage());
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