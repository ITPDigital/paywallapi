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

class GetProductPromoPlanAction implements RequestHandlerInterface
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
        $this->logger->info('GetProductPromoPlanAction: handler dispatched');
        $commonHelper = new CommonHelper();
       // $data = $request->getParsedBody();
        try {
            //$comp_id = $request->getAttribute('compid');
            $product_id = $commonHelper->resolveArg($request,'proId');
            //echo $product_id;exit;
            $promo_code = $commonHelper->resolveArg($request,'promoCode');
            $status = 1;
            $this->logger->info('GetProductPromoPlanAction: --product_id id---'.$product_id.'------promoCode---'.$promo_code);
            $db =  $this->connection;
            $sql = $db->prepare('SELECT mpd.discount_id,md.plan_id,pp.start_date,pp.end_date,mpp.product_id,mpd.promo_id FROM product_plan_promos pp LEFT JOIN map_promo_discount mpd ON mpd.promo_id=pp.id LEFT JOIN map_plan_discount md on md.discount_id=mpd.discount_id LEFT JOIN map_plan_product mpp ON md.plan_id=mpp.plan_id where pp.promo_name=:promo_code AND pp.is_active=:status AND mpd.is_active=:status AND mpp.is_active=:status AND mpp.product_id=:product_id;');
            $sql->execute(array(':product_id' => $product_id, ':promo_code' => $promo_code,':status' => $status));
            $data = $sql->fetchAll(PDO::FETCH_OBJ);
            $resData;
            $response = new Response();
            $trialPrice = 0; 
            if(count($data)>0){
                $discSql = $db->prepare('SELECT * from product_plan_discount WHERE id=:discount_id and is_active=:status;');
                $discSql->execute(array(':discount_id' => (int)$data[0]->discount_id,':status' => 1));
                $discDatas = $discSql->fetch(PDO::FETCH_ASSOC); 
                $planSql = $db->prepare('SELECT pp.id,pp.currency,pp.final_price,cc.symbol from product_plan pp LEFT JOIN const_currencies cc ON pp.currency=cc.disp_id WHERE pp.id=:plan_id and pp.is_active=:status;');
                $planSql->execute(array(':plan_id' => (int)$data[0]->plan_id,':status' => 1));
                $planDatas = $planSql->fetch(PDO::FETCH_ASSOC); 
                $resData['discount'] = $discDatas;
                $resData['promo_id'] = (int)$data[0]->promo_id;
                $resData['plan'] = $planDatas;
                $finalPrice = (float)$planDatas['final_price'];
                if($discDatas['discount_type'] == 'PERCENTAGE') {
                    $trialPrice = $finalPrice - (($finalPrice*(float)$discDatas['discount_value'])/100);
                } else if($discDatas['discount_type'] == 'AMOUNT') {
                    $trialPrice = (float)$planDatas['final_price'] - (float)$discDatas['discount_value'];
                }
                $resData['trial_price'] = number_format(floor($trialPrice*100)/100, 2);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 1,
                        "message" => "Data found",
                        "result" => $resData
                    ))
                );
            } else {
                $this->logger->info('GetProductPromoPlanAction: Products not available for product_id id---'.$product_id.'------promoCode---'.$promo_code);
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
            $this->logger->info('GetProductPromoPlanAction: Error in fetching all products for brand_id-'.$brand_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in fetching all products'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('GetProductPromoPlanAction: Error in fetching all products for brand_id-'.$brand_id.'----'.$e->getMessage());
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