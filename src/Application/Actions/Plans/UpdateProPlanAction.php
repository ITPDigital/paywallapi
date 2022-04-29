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

class UpdateProPlanAction implements RequestHandlerInterface
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
        $this->logger->info('UpdateProPlanAction: handler dispatched');
        $commonHelper = new CommonHelper();
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
            $id = $commonHelper->resolveArg($request,'id');
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
            $base_price = isset($data['base_price']) ? $data["base_price"] :  '';
            $tax_code = isset($data['tax_code']) ? $data["tax_code"] :  '';
            $tax_type = isset($data['tax_type']) ? $data["tax_type"] :  '';
            $tax_value = isset($data['tax_value']) ? $data["tax_value"] :  '';
            $final_price = 0;
            $trial_price = 0;
            $payment_type = isset($data['payment_type']) ? $data["payment_type"] : '';
            $user_id = $request->getAttribute('userid');
            $is_comp_gift_enabled = isset($data['is_comp_gift_enabled']) ? $data["is_comp_gift_enabled"] :  '';
            $comp_gift_desc = isset($data['comp_gift_desc']) ? $data["comp_gift_desc"] :  '';
            $show_comp_gift_consent = isset($data['show_comp_gift_consent']) ? $data["show_comp_gift_consent"] :  '';
            $status = isset($data['status']) ? $data["status"] :  '';
            $date = date('Y-m-d h:i:s');
            $plan_discount = isset($data['plan_discount']) ? $data["plan_discount"] :  0;

            $discounts = isset($data['discounts']) ? $data["discounts"] :  '';
            $discounts_count = count($discounts);
            $features = isset($data['features']) ? $data["features"] :  '';
            $features_count = count($features);

            $this->logger->info('UpdateProPlanAction: Product plan id'.$id.'--comp_id id----'.$comp_id);
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
            $sql = $db->prepare("UPDATE product_plan set plan_name=:plan_name,plan_display_name=:plan_display_name,plan_desc=:plan_desc,contract_length=:contract_length,auto_renew=:auto_renew,renewal_plan=:renewal_plan,frequency=:frequency,offset=:offset,currency=:currency,base_price=:base_price,tax_code=:tax_code,tax_type=:tax_type,tax_value=:tax_value,final_price=:final_price,trial_price=:trial_price,payment_type=:payment_type,updated_by=:updated_by,updated_on=:updated_on,is_comp_gift_enabled=:is_comp_gift_enabled,comp_gift_desc=:comp_gift_desc,show_comp_gift_consent=:show_comp_gift_consent,is_active=:status where id = :id and comp_id=:comp_id");		
            $sql->execute(array(':plan_name' => $plan_name,':plan_display_name' => $plan_display_name, ':plan_desc' => $plan_desc,':contract_length' => $contract_length,':auto_renew' => $auto_renew, ':renewal_plan' => $renewal_plan,':frequency' => $frequency, ':offset' => $offset,':currency' => $currency,':base_price' => $base_price,':tax_code' => $tax_code, ':tax_type' => $tax_type,':tax_value' => $tax_value,':final_price' => $final_price,':trial_price' => $trial_price,':payment_type' => $payment_type,':updated_by' => $user_id, ':updated_on' => $date,':is_comp_gift_enabled' => $is_comp_gift_enabled,':comp_gift_desc' => $comp_gift_desc,':show_comp_gift_consent' => $show_comp_gift_consent,':status' => $status, ':id' => $id,':comp_id' => $comp_id));
            $count = $sql->rowCount();
            if($count) {
                $this->logger->info('UpdateProPlanAction: Product plan details updated successfully'.$id);
                if($discounts_count>0) {
                    foreach ($discounts as $dc) {
                        $dc_type =  $dc['version'];
                        if($dc_type==1) {
                            $sql = $db->prepare("INSERT INTO map_plan_discount (comp_id, plan_id, discount_id, created_by, created_on, is_active ) VALUES (:comp_id, :plan_id, :discount_id, :created_by, :created_on, :status)");
                            $sql->execute(array(':comp_id' => $comp_id,':plan_id' => $id,':discount_id' => $dc['discount_id'],':created_by' => $user_id,':created_on' => $date, ':status' => $dc['status'] ));
                        }
                        else if($dc_type==2) {
                            $sql = $db->prepare("UPDATE map_plan_discount set is_active = :status,updated_by=:updated_by,updated_on=:updated_on where plan_id = :plan_id and discount_id = :discount_id and comp_id = :comp_id");
                            $sql->execute(array(':comp_id' => $comp_id,':plan_id' => $id,':discount_id' => $dc['discount_id'],':updated_by' => $user_id,':updated_on' => $date, ':status' => $dc['status'] ));
                         }
                    }
                }
                if($features_count>0) {
                    foreach ($features as $ft) {
                        $type =  $ft['version'];
                        if($type==1) {
                            $sql = $db->prepare("INSERT INTO product_plan_features (product_plan_id,feature_desc,created_by,created_on,comp_id,is_active) VALUES (:product_plan_id,:feature_desc,:created_by,:created_on,:comp_id,:is_active)");
                            $sql->execute(array(':product_plan_id' => $id,':feature_desc' => $ft['desc'],':created_by' => $user_id, ':created_on' => $date, ':comp_id' => $comp_id, ':is_active' => $ft['status']));
                        }
                        else if($type==2) {
                            $sql = $db->prepare("UPDATE product_plan_features set feature_desc = :feature_desc,is_active=:status,updated_by=:updated_by,updated_on=:updated_on where id = :id and product_plan_id=:product_plan_id and comp_id = :comp_id");	
                            $sql->execute(array(':feature_desc'=>$ft['desc'],':status' => $ft['status'],':updated_by' => $user_id, ':updated_on' => $date,':id' => $ft['id'],':product_plan_id' => $id,':comp_id' => $comp_id));
                        }
                    }
                }
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 1,
                        "message" => "Product plan details updated successfully",
                        "id" => $id
                    ))
                );
            } else {
                $this->logger->info('AddBrandAction: Failed to update product plan details'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "message" => "Failed to update product plan details"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('AddBrandAction: SQL error in updating product plan details--'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'SQL error in updating product plan details'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('AddBrandAction: Error in updating product plan details--'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in updating product plan details'
                ))
            );
        }
		return $response->withHeader('Content-Type', 'application/json');
    }
}