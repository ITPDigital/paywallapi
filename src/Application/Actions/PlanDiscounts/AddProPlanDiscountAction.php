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


class AddProPlanDiscountAction implements RequestHandlerInterface
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
        $this->logger->info('AddProPlanDiscountAction: handler dispatched');
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
            ->notEmptyString('discount_period', 'Field required')
			->requirePresence('discount_period')
			->requirePresence('promos')
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
            $discout_name = isset($data['discout_name']) ? $data["discout_name"] : '';
            $comp_id = $request->getAttribute('compid');
            $display_name = isset($data['display_name']) ? $data["display_name"] : '';
            $discount_type = isset($data['discount_type']) ? $data["discount_type"] : '';
            $currency = isset($data['currency']) ? $data["currency"] :  '';
            $discount_value = isset($data['discount_value']) ? $data["discount_value"] :  '';
            $discount_desc = isset($data['discount_desc']) ? $data["discount_desc"] :  '';
			$discount_period = isset($data['discount_period']) ? $data["discount_period"] :  '';
            $promo_id = isset($data['promos']) ? $data["promos"] :  '';
            $promo_id_array = array_values(array_unique($promo_id));
            $promo_id_count = count($promo_id_array);
            $user_id = $request->getAttribute('userid');
            $status = isset($data['status']) ? $data["status"] :  '';
            $date = date('Y-m-d h:i:s');
            $this->logger->info('AddProPlanDiscountAction: comp_id-'.$comp_id.'--discout_name--'.$discout_name);
            $db =  $this->connection;
            $response = new Response();
            $sql = $db->prepare("INSERT INTO product_plan_discount (discout_name,display_name,discount_type,currency,discount_value,discount_desc,discount_period,created_by,created_on,is_active,comp_id) VALUES (:discout_name,:display_name,:discount_type,:currency,:discount_value,:discount_desc,:discount_period,:created_by,:created_on,:status,:comp_id)");
            $sql->execute(array(':discout_name' => $discout_name,':display_name' => $display_name,':discount_type' => $discount_type,':currency' => $currency, ':discount_value' => $discount_value, ':discount_desc' => $discount_desc, ':discount_period' => $discount_period, ':created_by' => $user_id, ':created_on' => $date,':status' => $status,':comp_id' => $comp_id));
            $count = $sql->rowCount();
            $lastinserid = $db->lastInsertId();
            if($count && $lastinserid) {
                $this->logger->info('AddProPlanDiscountAction: New product plan discount has been created successfully for comp_id-'.$comp_id);
                if($promo_id_count>0) {    
                    $sql = $db->prepare("INSERT INTO map_promo_discount (comp_id, discount_id, promo_id, created_by, created_on, is_active ) VALUES (:comp_id, :discount_id, :promo_id, :created_by, :created_on, :status)");
                    $count = 0;
                    for($i = 0; $i<$promo_id_count; $i++) {
                        $sql->execute(array(':comp_id' => $comp_id,':discount_id' => $lastinserid,':promo_id' => $promo_id_array[$i],':created_by' => $user_id,':created_on' => $date, ':status' => 1 ));
                        $count += $sql->rowCount();
                    }
                }
                if($count > 0) {
                    $this->logger->info('AddProPlanDiscountAction: New product plan discount has been created successfully for comp_id-'.$comp_id);
                    $response->getBody()->write(
                        json_encode(array(
                            "code" => 1,
                            "status" => 1,
                            "message" => "New product plan discount has been created successfully",
                            "id" => $lastinserid
                        ))
                    );
                } else {
                    $this->logger->info('AddProPlanDiscountAction: Failed to create new product plan discount and promo mapping for comp_id-'.$comp_id);
                    $response->getBody()->write(
                        json_encode(array(
                            "code" => 1,
                            "status" => 2,
                            "message" => "Failed to create new product plan discount and promo mapping for comp_id"
                        ))
                    );
                }
            } else {
                $this->logger->info('AddProPlanDiscountAction: Failed to create new product plan discount for comp_id-'.$comp_id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "message" => "Failed to create new product plan discount for comp_id"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('AddProPlanDiscountAction: SQL error in creating new product plan discount for comp_id-'.$comp_id.'--'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'SQL error in creating new product plan discount'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('AddProPlanDiscountAction: Error in creating new product plan discount for comp_id-'.$comp_id.'--'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in creating new product plan discount'
                ))
            );
        }
		return $response->withHeader('Content-Type', 'application/json');
    }
}