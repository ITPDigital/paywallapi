<?php

declare(strict_types=1);

namespace App\Application\Actions\Tracking;

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

class GetBrandDetailsAction implements RequestHandlerInterface
{
    private $logger;
    private $connection;

    public function __construct(PDO $connection,LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function getBrandId($db, $brand_domain){
		$sql = $db->prepare('SELECT id from brands where domain_name=:domain_name and is_active=:status');
		$sql->execute(array(':domain_name' => $brand_domain,':status' => 1));
		$brandDatas = $sql->fetch(PDO::FETCH_ASSOC);
		$brandId = 0;
        $count = $sql->rowCount();
        if($count>0){
            $brandId = (int)$brandDatas['id'];
        }
        return $brandId;
	} 

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $commonHelper = new CommonHelper();
        $this->logger->info('GetBrandDetailsAction: handler dispatched');
        $data = $request->getParsedBody();
        
        try {
            $brand = isset($data['brand_domain']) ? $data["brand_domain"] :  '';
            $this->logger->info('GetBrandDetailsAction: brand id'.$brand);
            $db =  $this->connection;
            $brand_id = $this->getBrandId($db, $brand);
            $sql = $db->prepare('SELECT b.*,cp.number_of_days  from brands b LEFT JOIN const_periods cp on cp.disp_id=b.metering_period where b.id=:id and b.is_active=:status');
            $sql->execute(array(':id' => $brand_id,':status' => 1));
            $data = $sql->fetchAll(PDO::FETCH_OBJ);
            $response = new Response();
            if(count($data)>0){
                $this->logger->info('GetBrandDetailsAction: Brand found"'.$brand_id);
				$resData = array('id' => (int)$data[0]->id, 
					'brand_name' => $data[0]->brand_name, 
					'comp_id' => (int)$data[0]->comp_id, 
					'created_by' => (int)$data[0]->created_by, 
					'created_on' => $data[0]->created_on, 
					'domain_name' => $data[0]->domain_name, 
                    'metering_period' => $data[0]->number_of_days, 
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
                $this->logger->info('GetBrandDetailsAction: Brand not found"'.$brand);
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
            $this->logger->info('GetBrandDetailsAction: Error in getting brand detail---'.$brand.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in getting brand detail'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('GetBrandDetailsAction: Error in getting brand detail---'.$brand.'----'.$e->getMessage());
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