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


class AddProPlanAction implements RequestHandlerInterface
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
        $this->logger->info('AddProPlanAction: handler dispatched');
        $data = $request->getParsedBody();
        $validateData = (array)$request->getParsedBody();

        // Within the Application Service class: Do the validation
        $validationFactory = new CakeValidationFactory();
        $validator = $validationFactory->createValidator();
		//Form validation
        $validator
            ->notEmptyString('plan_name', 'Field required')
			->requirePresence('plan_name')
            ->notEmptyString('plan_display_name', 'Field required')
			->requirePresence('plan_display_name')
            ->notEmptyString('plan_desc', 'Field required')
			->requirePresence('plan_desc')
            ->notEmptyString('contract_length', 'Field required')
			->requirePresence('contract_length')
            ->notEmptyString('frequency', 'Field required')
			->requirePresence('frequency')
            ->notEmptyString('offset', 'Field required')
			->requirePresence('offset')
            ->notEmptyString('currency', 'Field required')
			->requirePresence('currency')
            ->notEmptyString('base_price', 'Field required')
			->requirePresence('base_price')
            ->notEmptyString('tax_code', 'Field required')
			->requirePresence('tax_code')
            ->notEmptyString('tax_type', 'Field required')
			->requirePresence('tax_type')
            ->notEmptyString('tax_value', 'Field required')
			->requirePresence('tax_value')
            ->notEmptyString('payment_type', 'Field required')
			->requirePresence('payment_type')
            ->notEmptyString('status', 'Field required')
			->requirePresence('status')
            ->notEmptyString('features', 'Field required')
			->requirePresence('features');

        $validationResult = $validationFactory->createValidationResult(
            $validator->validate($validateData)
        );
		//throw exception for validation failure
        if ($validationResult->fails()) {
            throw new ValidationException('Please check your input', $validationResult);
        }
        try {
            $comp_id = $request->getAttribute('compid');
            $plan_name = isset($data['plan_name']) ? $data["plan_name"] : '';
            $plan_display_name = isset($data['plan_display_name']) ? $data["plan_display_name"] : '';
            $plan_desc = isset($data['plan_desc']) ? $data["plan_desc"] :  '';
            $contract_length = isset($data['contract_length']) ? $data["contract_length"] :  '';
            $renewal_plan = isset($data['renewal_plan']) ? $data["renewal_plan"] :  '';
            $auto_renew = isset($data['auto_renew']) ? $data["auto_renew"] :  '';
            $frequency = isset($data['frequency']) ? $data["frequency"] : '';
            $offset = isset($data['offset']) ? $data["offset"] : '';
            $currency = isset($data['currency']) ? $data["currency"] : '';
            $base_price = isset($data['base_price']) ? (float)$data["base_price"] :  '';
            $promo_discounts = isset($data['promo_discounts']) ? array_values(array_unique($data["promo_discounts"])) :  [];
            $promo_discounts_count = count($promo_discounts);
            $features = isset($data['features']) ? array_values(array_unique($data["features"])) :  '';
            $features_count = count($features);
            $tax_code = isset($data['tax_code']) ? $data["tax_code"] :  '';
            $tax_type = isset($data['tax_type']) ? $data["tax_type"] :  '';
            $tax_value = isset($data['tax_value']) ? (float)$data["tax_value"] :  '';
            $plan_discount = isset($data['plan_discount']) ? $data["plan_discount"] :  0;
            $final_price = 0;
            $trial_price = 0;
            $payment_type = isset($data['payment_type']) ? $data["payment_type"] : '';
            $user_id = $request->getAttribute('userid');
            $is_comp_gift_enabled = isset($data['is_comp_gift_enabled']) ? $data["is_comp_gift_enabled"] :  '';
            $comp_gift_desc = isset($data['comp_gift_desc']) ? $data["comp_gift_desc"] :  '';
            $show_comp_gift_consent = isset($data['show_comp_gift_consent']) ? $data["show_comp_gift_consent"] :  '';
            $status = isset($data['status']) ? $data["status"] :  '';
            $date = date('Y-m-d h:i:s');
            $ev_plan_id = base64_encode(openssl_random_pseudo_bytes(3 * (28 >> 2)));//generate random alpha-numeric string
            $this->logger->info('AddProPlanAction: comp_id-'.$comp_id.'--plan name--'.$plan_name);
            $db =  $this->connection;
            $response = new Response();
            //calculate final price
            if($tax_type == "PERCENTAGE") {
                $final_price = $base_price + (($base_price * $tax_value) /100);
            } else if($tax_type == "AMOUNT") {
                $final_price = $base_price + $tax_value;
            }
            $final_price = number_format(floor($final_price*100)/100, 2);
            if($plan_discount) {
                $planDiscSql = $db->prepare("SELECT * from product_plan_discount where id=:discount_id and is_active=:status");
                $planDiscSql->execute(array(':discount_id' => $plan_discount,':status' => 1));
                $planDiscData = $planDiscSql->fetch(PDO::FETCH_ASSOC);
                if($planDiscData['discount_type'] == 'PERCENTAGE') {
                    $trialPrice = $final_price - (($final_price*(float)$planDiscData['discount_value'])/100);
                } else if($planDiscData['discount_type'] == 'AMOUNT') {
                    $trialPrice = $final_price - (float)$planDiscData['discount_value'];
                }
                $trial_price =  number_format(floor($trialPrice*100)/100, 2);
            } 
            $sql = $db->prepare("INSERT INTO product_plan (comp_id,plan_name,plan_display_name,plan_desc,contract_length,auto_renew,renewal_plan,discount_id,frequency,offset,currency,base_price,tax_code,tax_type,tax_value,final_price,trial_price,payment_type,created_by,created_on,is_comp_gift_enabled,comp_gift_desc,show_comp_gift_consent,ev_plan_id,is_active) VALUES (:comp_id,:plan_name,:plan_display_name,:plan_desc,:contract_length,:auto_renew,:renewal_plan,:discount_id,:frequency,:offset,:currency,:base_price,:tax_code,:tax_type,:tax_value,:final_price,:trial_price,:payment_type,:created_by,:created_on,:is_comp_gift_enabled,:comp_gift_desc,:show_comp_gift_consent,:ev_plan_id,:is_active)");
            $sql->execute(array(':comp_id' => $comp_id,':plan_name' => $plan_name, ':plan_display_name' => $plan_display_name, ':plan_desc' => $plan_desc, ':contract_length' => $contract_length, ':auto_renew' => $auto_renew, ':renewal_plan' => $renewal_plan,':frequency' => $frequency,':offset' => $offset,':currency' => $currency,':discount_id' => $plan_discount,':frequency' => $frequency,':base_price' => $base_price,':tax_code' => $tax_code,':tax_type' => $tax_type,':tax_value' => $tax_value,':final_price' => $final_price,':trial_price' => $trial_price,':payment_type' => $payment_type,':created_by' => $user_id,':created_on' => $date,':is_comp_gift_enabled' => $is_comp_gift_enabled,':comp_gift_desc' => $comp_gift_desc,':show_comp_gift_consent' => $show_comp_gift_consent,':ev_plan_id' => $ev_plan_id,':is_active' => $status));
            $count = $sql->rowCount();
            $lastinserid = $db->lastInsertId();
            //echo $lastinserid;exit;
            if($count && $lastinserid) {
                $this->logger->info('AddProPlanAction: New product plan created successfully for comp_id-'.$comp_id.'--plan name--'.$plan_name);
                if($promo_discounts_count>0) {
                    $sql = $db->prepare("INSERT INTO map_plan_discount (comp_id, plan_id, discount_id, created_by, created_on, is_active ) VALUES (:comp_id, :plan_id, :discount_id, :created_by, :created_on, :status)");
                    for($i = 0; $i<$promo_discounts_count; $i++) {
                        $sql->execute(array(':comp_id' => $comp_id,':plan_id' => $lastinserid,':discount_id' => $promo_discounts[$i], ':created_by' => $user_id,':created_on' => $date, ':status' => 1 ));
                    }
                }
                if($features_count>0) {
                    $sql = $db->prepare("INSERT INTO product_plan_features (product_plan_id,feature_desc,created_by,created_on,comp_id,is_active) VALUES (:product_plan_id,:feature_desc,:created_by,:created_on,:comp_id,:is_active)");
                    for($i = 0; $i<$features_count; $i++) {
                        $sql->execute(array(':product_plan_id' => $lastinserid,':feature_desc' => $features[$i],':created_by' => $user_id, ':created_on' => $date, ':comp_id' => $comp_id, ':is_active' => 1));
                    }
                }
                $this->logger->info('AddProPlanAction: New product plan mapping added successfully for comp_id-'.$comp_id.'--plan name--'.$plan_name);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 1,
                        "message" => "New product plan created successfully",
                        "id" => $lastinserid
                    ))
                );
            } else {
                $this->logger->info('AddProPlanAction: Failed to create new product plan for comp_id-'.$comp_id.'--plan name--'.$plan_name);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 4,
                        "message" => "Failed to create new product plan"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('AddBrandAction: SQL error in creating new product plan--'.$plan_name.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'SQL error in creating new product plan'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('AddBrandAction: Error in creating new product plan--'.$plan_name.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in creating new product plan'
                ))
            );
        }
		return $response->withHeader('Content-Type', 'application/json');
    }
}