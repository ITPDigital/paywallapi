<?php

declare(strict_types=1);

namespace App\Application\Actions\Brands;

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

class ViewBrandAction implements RequestHandlerInterface
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
        $commonHelper = new CommonHelper();
        $this->logger->info('ViewBrandAction: handler dispatched');
        $data = $request->getParsedBody();
        
        try {
            $id = $commonHelper->resolveArg($request,'id');
            $comp_id = $request->getAttribute('compid');
            $this->logger->info('ViewBrandAction: brand id'.$id);
            $db =  $this->connection;
            $sql = $db->prepare('SELECT * from brands where id=:id and comp_id=:comp_id');
            $sql->execute(array(':id' => $id,':comp_id' => $comp_id));
            $data = $sql->fetchAll(PDO::FETCH_OBJ);
            $response = new Response();
            if(count($data)>0){
                $this->logger->info('ViewBrandAction: Brand found"'.$id);
				$resData = array('id' => (int)$data[0]->id, 
					'brand_name' => $data[0]->brand_name, 
					'comp_id' => (int)$data[0]->comp_id, 
					'created_by' => (int)$data[0]->created_by, 
					'created_on' => $data[0]->created_on, 
					'domain_name' => $data[0]->domain_name, 
                    'metering_period' => $data[0]->metering_period, 
					'is_active' => (int)$data[0]->is_active, 
					'max_limit' => (int)$data[0]->max_limit, 
					'offered_limit' => (int)$data[0]->offered_limit, 
					'updated_by' => (int)$data[0]->updated_by, 
					'updated_on' => $data[0]->updated_on
				);   
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 1,
                        "message" => "Brand found",
                        "result" => $resData
                    ))
                );
            } else {
                $this->logger->info('ViewBrandAction: Brand not found"'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "message" => "Brand not found"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('ViewBrandAction: Error in getting brand detail---'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in getting brand detail'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('ViewBrandAction: Error in getting brand detail---'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in getting brand detail'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

   
}