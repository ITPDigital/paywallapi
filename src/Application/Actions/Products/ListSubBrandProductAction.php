<?php

declare(strict_types=1);

namespace App\Application\Actions\Products;

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

class ListSubBrandProductAction implements RequestHandlerInterface
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
        $this->logger->info('ListSubBrandProductAction: handler dispatched');
        $commonHelper = new CommonHelper();
        $data = $request->getParsedBody();
        try {
            $brand = isset($data['brand_domain']) ? $data["brand_domain"] :  '';
            $status = 1;
            $db =  $this->connection;
            $brand_id = $this->getBrandId($db, $brand);
            $this->logger->info('ListSubBrandProductAction: --brand id---'.$brand_id);
            $sql = $db->prepare('SELECT * from products where brand_id=:brand_id and is_active=:status');
            $sql->execute(array(':brand_id' => $brand_id, ':status' => $status));
            $data = $sql->fetchAll(PDO::FETCH_OBJ);
            $response = new Response();
            if(count($data)>0){
                $sql = $db->prepare('SELECT pp.id,pp.plan_name,pp.plan_display_name,pp.plan_desc,pp.frequency,pp.final_price,pp.currency,cc.symbol,pp.discount_id,pp.trial_price from map_plan_product mp LEFT JOIN product_plan pp ON mp.plan_id=pp.id LEFT JOIN const_currencies cc ON pp.currency=cc.disp_id where mp.product_id=:product_id and mp.is_active=:status and pp.is_active=:status;');
                $sql->execute(array(':product_id' => (int)$data[0]->id,':status' => 1));
                $planDatas = $sql->fetchAll(PDO::FETCH_ASSOC); 
                $promoCount = 0;   
                $trialPrice = 0;             
				foreach ($planDatas as $key => $planData) {
                    $data['plans'][$key] = $planData;
                    $this->logger->info('ListSubBrandProductAction: Number of products available for brand_id-'.$brand_id.'-is-'.$planData['id']);
                    $sql = $db->prepare('SELECT * from product_plan_features where product_plan_id=:product_plan_id and is_active=:status');
                    $sql->execute(array(':product_plan_id' => (int)$planData['id'],':status' => 1));
                    $featureData = $sql->fetchAll(PDO::FETCH_ASSOC);
                    $data['plans'][$key]['features'] = $featureData;

                   /* $discSql = $db->prepare('SELECT * from product_plan_discount where id=:discount_id and is_active=:status');
                    $discSql->execute(array(':discount_id' => (int)$planData['discount_id'],':status' => 1));
                    $discData = $discSql->fetch(PDO::FETCH_ASSOC);
                    //$data['plans'][$key]['discData'] = $discData;
                    //echo '<pre>WWW=='; print_r($discData); echo '</pre>';exit;
                    $finalPrice = (float)$planData['final_price'];
                    if($discData['discount_type'] == 'PERCENTAGE') {
                        $trialPrice = $finalPrice - (($finalPrice*(float)$discData['discount_value'])/100);
                    } else if($discData['discount_type'] == 'AMOUNT') {
                        $trialPrice = $finalPrice - (float)$discData['discount_value'];
                    }
                    $data['plans'][$key]['trial_price'] = number_format(floor($trialPrice*100)/100, 2);*/
				}
                
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 1,
                        "message" => "Data found",
                        "result" => $data
                    ))
                );
            } else {
                $this->logger->info('ListSubBrandProductAction: Products not available for brand_id-'.$brand_id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "message" => "No data found"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('ListSubBrandProductAction: Error in fetching all products for brand_id-'.$brand_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in fetching all products'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('ListSubBrandProductAction: Error in fetching all products for brand_id-'.$brand_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in fetching all products'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

   
}