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
        $data = $request->getParsedBody();
        $validateData = (array)$request->getParsedBody();

        // Within the Application Service class: Do the validation
        $validationFactory = new CakeValidationFactory();
        $validator = $validationFactory->createValidator();
		//Form validation
        $validator
            ->notEmptyString('id', 'Field required')
            ->requirePresence('id')
            ->notEmptyString('brand_id', 'Field required')
			->requirePresence('brand_id')
            ->notEmptyString('comp_id', 'Field required')
			->requirePresence('comp_id')
            ->notEmptyString('plan_name', 'Field required')
			->requirePresence('plan_name')
            ->notEmptyString('plan_display_name', 'Field required')
			->requirePresence('plan_display_name')
            ->notEmptyString('plan_desc', 'Field required')
			->requirePresence('plan_desc')
            ->notEmptyString('product_id', 'Field required')
			->requirePresence('product_id')
            ->notEmptyString('contract_length', 'Field required')
			->requirePresence('contract_length')
            ->notEmptyString('renewal_plan', 'Field required')
			->requirePresence('renewal_plan')
            ->notEmptyString('frequency', 'Field required')
			->requirePresence('frequency')
            ->notEmptyString('offset', 'Field required')
			->requirePresence('offset')
            ->notEmptyString('currency', 'Field required')
			->requirePresence('currency')
            ->notEmptyString('base_price', 'Field required')
			->requirePresence('base_price')
            ->notEmptyString('disocunt_id', 'Field required')
			->requirePresence('disocunt_id')
            ->notEmptyString('tax_code', 'Field required')
			->requirePresence('tax_code')
            ->notEmptyString('tax_type', 'Field required')
			->requirePresence('tax_type')
            ->notEmptyString('tax_value', 'Field required')
			->requirePresence('tax_value')
            ->notEmptyString('final_price', 'Field required')
			->requirePresence('final_price')
            ->notEmptyString('payment_type', 'Field required')
			->requirePresence('payment_type')
            ->notEmptyString('user_id', 'Field required')
			->requirePresence('user_id')
            ->notEmptyString('is_comp_gift_enabled', 'Field required')
			->requirePresence('is_comp_gift_enabled')
			->requirePresence('comp_gift_desc')
            ->notEmptyString('show_comp_gift_consent', 'Field required')
			->requirePresence('show_comp_gift_consent')
            ->notEmptyString('status', 'Field required')
			->requirePresence('status');

        $validationResult = $validationFactory->createValidationResult(
            $validator->validate($validateData)
        );
		//throw exception for validation failure
        if ($validationResult->fails()) {
            throw new ValidationException('Please check your input', $validationResult);
        }
        $id = isset($data['id']) ? $data["id"] : '';
		$brand_id = isset($data['brand_id']) ? $data["brand_id"] : '';
        $comp_id = isset($data['comp_id']) ? $data["comp_id"] : '';
        $plan_name = isset($data['plan_name']) ? $data["plan_name"] : '';
        $plan_display_name = isset($data['plan_display_name']) ? $data["plan_display_name"] : '';
        $plan_desc = isset($data['plan_desc']) ? $data["plan_desc"] :  '';
        $product_id = isset($data['product_id']) ? $data["product_id"] :  '';
		$contract_length = isset($data['contract_length']) ? $data["contract_length"] :  '';
		$renewal_plan = isset($data['renewal_plan']) ? $data["renewal_plan"] :  '';
        $frequency = isset($data['frequency']) ? $data["frequency"] : '';
        $offset = isset($data['offset']) ? $data["offset"] : '';
        $currency = isset($data['currency']) ? $data["currency"] : '';
        $base_price = isset($data['base_price']) ? $data["base_price"] :  '';
        $disocunt_id = isset($data['disocunt_id']) ? $data["disocunt_id"] :  '';
		$tax_code = isset($data['tax_code']) ? $data["tax_code"] :  '';
		$tax_type = isset($data['tax_type']) ? $data["tax_type"] :  '';
        $tax_value = isset($data['tax_value']) ? $data["tax_value"] :  '';
        $final_price = isset($data['final_price']) ? $data["final_price"] : '';
        $payment_type = isset($data['payment_type']) ? $data["payment_type"] : '';
        $user_id = isset($data['user_id']) ? $data["user_id"] : '';
        $is_comp_gift_enabled = isset($data['is_comp_gift_enabled']) ? $data["is_comp_gift_enabled"] :  '';
		$comp_gift_desc = isset($data['comp_gift_desc']) ? $data["comp_gift_desc"] :  '';
		$show_comp_gift_consent = isset($data['show_comp_gift_consent']) ? $data["show_comp_gift_consent"] :  '';
        $status = isset($data['status']) ? $data["status"] :  '';
		$date = date('Y-m-d h:i:s');

        $this->logger->info('UpdateProPlanAction: Product plan id'.$id.'--brand id----'.$brand_id);
        $db =  $this->connection;
        $response = new Response();
        $sql = $db->prepare("UPDATE product_plan set brand_id = :brand_id,plan_name=:plan_name,plan_display_name=:plan_display_name,plan_desc=:plan_desc,product_id=:product_id,contract_length=:contract_length,renewal_plan=:renewal_plan,frequency=:frequency,offset=:offset,currency=:currency,base_price=:base_price,disocunt_id=:disocunt_id,tax_code=:tax_code,tax_type=:tax_type,tax_value=:tax_value,final_price=:final_price,payment_type=:payment_type,updated_by=:updated_by,updated_on=:updated_on,is_comp_gift_enabled=:is_comp_gift_enabled,comp_gift_desc=:comp_gift_desc,show_comp_gift_consent=:show_comp_gift_consent,is_active=:status where id = :id and comp_id=:comp_id");		
        $sql->execute(array(':brand_id'=>$brand_id,':plan_name' => $plan_name,':plan_display_name' => $plan_display_name, ':plan_desc' => $plan_desc,':product_id' => $product_id,':contract_length' => $contract_length,':renewal_plan' => $renewal_plan,':frequency' => $frequency, ':offset' => $offset,':currency' => $currency,':base_price' => $base_price,':disocunt_id' => $disocunt_id,':tax_code' => $tax_code, ':tax_type' => $tax_type,':tax_value' => $tax_value,':final_price' => $final_price,':payment_type' => $payment_type,':updated_by' => $user_id, ':updated_on' => $date,':is_comp_gift_enabled' => $is_comp_gift_enabled,':comp_gift_desc' => $comp_gift_desc,':show_comp_gift_consent' => $show_comp_gift_consent,':status' => $status, ':id' => $id,':comp_id' => $comp_id));
        $count = $sql->rowCount();
        if($count) {
            $this->logger->info('UpdateProPlanAction: Product plan details updated successfully'.$id);
            $response->getBody()->write(
                json_encode(array(
                    "code" => 1,
                    "status" => 1,
                    "message" => "Product plan details updated successfully",
                    "brand_id" => $id
                ))
            );
        } else {
            $this->logger->info('AddBrandAction: Failed to update brand'.$id);
            $response->getBody()->write(
                json_encode(array(
                    "code" => 1,
                    "status" => 2,
                    "message" => "Failed to update product plan details"
                ))
            );
        }
		return $response->withHeader('Content-Type', 'application/json');
    }
}