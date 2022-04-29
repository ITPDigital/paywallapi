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

class ListProPlanWithPrice implements RequestHandlerInterface
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
        $this->logger->info('ListProPlanWithPrice: handler dispatched');
        $commonHelper = new CommonHelper();
        try {
            $comp_id =  $request->getAttribute('compid');
            $status = $commonHelper->resolveArg($request,'status');
            $this->logger->info('ListProPlanWithPrice: Comp id'.$comp_id);
            $db =  $this->connection;
            $sql = $db->prepare('SELECT pp.id,pp.plan_name,pp.plan_display_name,pp.discount_id,pp.plan_desc,pp.frequency,pp.final_price,pp.currency,cc.symbol,cp.display_name as frequency_text from product_plan pp LEFT JOIN const_currencies cc ON pp.currency=cc.disp_id LEFT JOIN const_periods cp on cp.disp_id=pp.frequency where pp.comp_id=:comp_id and pp.is_active=:status');
            $sql->execute(array(':comp_id' => $comp_id, ':status' => 1));
            $planDatas = $sql->fetchAll(PDO::FETCH_ASSOC);
            $response = new Response();
            if(count($planDatas)>0){
                $this->logger->info('ListProPlanWithPrice: Number of product plans available for company id-'.$comp_id.'-is-'.count($planDatas));
                $trialPrice;
                foreach ($planDatas as $key => $planData) {
                    $discountId = (int)$planData['discount_id'];
                    if($discountId) {
                        $discSql = $db->prepare('SELECT * from product_plan_discount where id=:discount_id and is_active=:status');
                        $discSql->execute(array(':discount_id' => $discountId,':status' => $status));
                        $discData = $discSql->fetch(PDO::FETCH_ASSOC);

                        $finalPrice = (float)$planData['final_price'];
                        if($discData['discount_type'] == 'PERCENTAGE') {
                            $trialPrice = $finalPrice - (($finalPrice*(float)$discData['discount_value'])/100);
                        } else if($discData['discount_type'] == 'AMOUNT') {
                            $trialPrice = (float)$planData['final_price'] - (float)$discData['discount_value'];
                        }
                        $planDatas[$key]['trial_price'] = $planData['symbol'].''.number_format(floor($trialPrice*100)/100, 2);
                    } else {
                        $planDatas[$key]['trial_price'] = $planData['symbol'].'0.00';
                    }
                }
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 1,
                        "message" => "Data found",
                        "result" => $planDatas
                    ))
                );
            } else {
                $this->logger->info('ListProPlanWithPrice: Product plan not found for company id-'.$comp_id);
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
            $this->logger->info('ListProPlanWithPrice: SQL error in fetching all product plans--'.$comp_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'SQL error in fetching all product plans'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('ListProPlanWithPrice: Error in fetching all product plans--'.$comp_id.'----'.$e->getMessage());
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