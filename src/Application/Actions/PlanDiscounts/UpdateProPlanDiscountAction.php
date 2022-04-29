<?php

declare(strict_types=1);

namespace App\Application\Actions\PlanDiscounts;

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

class UpdateProPlanDiscountAction implements RequestHandlerInterface
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
        $this->logger->info('UpdateProPlanDiscountAction: handler dispatched');
        $commonHelper = new CommonHelper();
        $data = $request->getParsedBody();
        $validateData = (array)$request->getParsedBody();

        // Within the Application Service class: Do the validation
        $validationFactory = new CakeValidationFactory();
        $validator = $validationFactory->createValidator();
		//Form validation
        $validator
            ->notEmptyString('discout_name', 'Field required')
			->requirePresence('discout_name')
            ->notEmptyString('display_name', 'Field required')
			->requirePresence('display_name')
            ->notEmptyString('discount_type', 'Field required')
			->requirePresence('currency')
            ->requirePresence('discount_value')
            ->notEmptyString('discount_value', 'Field required')
            ->requirePresence('discount_desc')
			->requirePresence('promos')
			->notEmptyString('discount_period', 'Field required')
			->requirePresence('discount_period')
            ->notEmptyString('status', 'Field required')
			->requirePresence('status');

        $validationResult = $validationFactory->createValidationResult(
            $validator->validate($validateData)
        );
		//throw exception for validation failure
        if ($validationResult->fails()) {
            throw new ValidationException('Please check your input', $validationResult);
        }
        try {
            $id = $commonHelper->resolveArg($request,'id');
            $discout_name = isset($data['discout_name']) ? $data["discout_name"] : '';
            $comp_id = $request->getAttribute('compid');
            $display_name = isset($data['display_name']) ? $data["display_name"] : '';
            $discount_type = isset($data['discount_type']) ? $data["discount_type"] : '';
            $currency = isset($data['currency']) ? $data["currency"] :  '';
            $discount_value = isset($data['discount_value']) ? $data["discount_value"] :  '';
            $discount_desc = isset($data['discount_desc']) ? $data["discount_desc"] :  '';
			$discount_period = isset($data['discount_period']) ? $data["discount_period"] :  '';
            $user_id = $request->getAttribute('userid');
            $status = isset($data['status']) ? $data["status"] :  '';
            $date = date('Y-m-d h:i:s');

            $promos = isset($data['promos']) ? $data["promos"] :  '';
            $promos_count = count($promos);

            $this->logger->info('UpdateProPlanDiscountAction: Product plan discount id'.$id.'--comp_id----'.$comp_id);
            $db =  $this->connection;
            $response = new Response();
            $sql = $db->prepare("UPDATE product_plan_discount set discout_name = :discout_name, display_name=:display_name, discount_type=:discount_type, currency=:currency, discount_value=:discount_value, discount_desc=:discount_desc, discount_period=:discount_period, updated_by=:user_id, updated_on=:updated_on where id = :id and comp_id = :comp_id");		
            $sql->execute(array(':discout_name'=>$discout_name,':display_name'=>$display_name,':discount_type' => $discount_type,':currency' => $currency, 
            ':discount_value' => $discount_value,':discount_desc' => $discount_desc,':discount_period' => $discount_period, ':user_id' => $user_id,':updated_on' => $date,':id' => $id,':comp_id' => $comp_id));
            $count = $sql->rowCount();
            $this->logger->info('UpdateProPlanDiscountAction: Product plan discount detail updated successfully'.$id);
            if($promos_count>0) {
                foreach ($promos as $pm) {
                    $type =  $pm['version'];
                    if($type==1) {
                        $sql = $db->prepare("INSERT INTO map_promo_discount (comp_id, discount_id, promo_id, created_by, created_on, is_active ) VALUES (:comp_id, :discount_id, :promo_id, :created_by, :created_on, :status)");
                        $sql->execute(array(':comp_id' => $comp_id,':discount_id' => $id,':promo_id' => $pm['promo_id'],':created_by' => $user_id,':created_on' => $date, ':status' => $pm['status'] ));
                    }
                    else if($type==2) {
                        $sql = $db->prepare("UPDATE map_promo_discount set is_active = :status,updated_by=:updated_by,updated_on=:updated_on where promo_id = :promo_id and discount_id = :discount_id");
                        $sql->execute(array(':status' => $pm['status'],':updated_by' => $user_id,':updated_on' => $date,':promo_id' => $pm['promo_id'],':discount_id' => $id));
                    }
                    $count += $sql->rowCount();
                }
            }
            if($count) {
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 1,
                        "message" => "Product plan discount detail updated successfully",
                        "id" => $id
                    ))
                );
            } else {
                $this->logger->info('UpdateProPlanDiscountAction: Failed to update product plan discount'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "message" => "Failed to update product plan discount detail"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('UpdateProPlanDiscountAction: Error in update product plan discount---'.$id.'-----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in update product plan discount'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('UpdateProPlanDiscountAction: Error in update product plan discount---'.$id.'-----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in update product plan discount'
                ))
            );
        }
		return $response->withHeader('Content-Type', 'application/json');
    }
}