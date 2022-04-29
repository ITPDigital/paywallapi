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


class AddProductAction implements RequestHandlerInterface
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
        $this->logger->info('AddProductAction: handler dispatched');
        $data = $request->getParsedBody();
        $validateData = (array)$request->getParsedBody();

        // Within the Application Service class: Do the validation
        $validationFactory = new CakeValidationFactory();
        $validator = $validationFactory->createValidator();
		//Form validation
        $validator
            ->notEmptyString('brand_id', 'Field required')
            ->requirePresence('brand_id')
            ->notEmptyString('product_name', 'Field required')
			->requirePresence('product_name')
            ->notEmptyString('display_name', 'Field required')
			->requirePresence('display_name')
            ->notEmptyString('product_desc', 'Field required')
			->requirePresence('product_desc')
            ->requirePresence('start_date')
            ->notEmptyString('start_date', 'Field required')
            ->requirePresence('end_date')
            ->notEmptyString('plans', 'Field required')
			->requirePresence('plans')
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
            $brand_id = isset($data['brand_id']) ? $data["brand_id"] : '';
            $comp_id = $request->getAttribute('compid');
            $product_name = isset($data['product_name']) ? $data["product_name"] : '';
            $display_name = isset($data['display_name']) ? $data["display_name"] : '';
            $product_desc = isset($data['product_desc']) ? $data["product_desc"] :  '';
            $start_date = isset($data['start_date']) ? $data["start_date"] :  '';
            $end_date = isset($data['end_date']) ? $data["end_date"] : '';
            $user_id = $request->getAttribute('userid');
            $status = isset($data['status']) ? $data["status"] :  '';
            $date = date('Y-m-d h:i:s');

            $plans = isset($data['plans']) ? array_values(array_unique($data["plans"])) :  '';
            $plans_count = count($plans);
            //echo $end_date;exit;
            $this->logger->info('AddProductAction: comp_id-'.$comp_id.'--product_name--'.$product_name);
            $db =  $this->connection;
            $response = new Response();
            if($end_date!='') {
                $sql = $db->prepare("INSERT INTO products (brand_id,comp_id,product_name,display_name,product_desc,created_by,created_on,start_date,end_date,is_active) VALUES (:brand_id,:comp_id,:product_name,:display_name,:product_desc,:created_by,:created_on,:start_date,:end_date,:status)");
                $sql->execute(array(':brand_id' => $brand_id,':comp_id' => $comp_id,':product_name' => $product_name,':display_name' => $display_name, ':product_desc' => $product_desc, ':created_by' => $user_id, ':created_on' => $date,':start_date' => $start_date,':end_date' => $end_date,':status' => $status));
            } else {
                $sql = $db->prepare("INSERT INTO products (brand_id,comp_id,product_name,display_name,product_desc,created_by,created_on,start_date,is_active) VALUES (:brand_id,:comp_id,:product_name,:display_name,:product_desc,:created_by,:created_on,:start_date,:status)");
                $sql->execute(array(':brand_id' => $brand_id,':comp_id' => $comp_id,':product_name' => $product_name,':display_name' => $display_name, ':product_desc' => $product_desc, ':created_by' => $user_id, ':created_on' => $date,':start_date' => $start_date,':status' => $status));
            }
            $count = $sql->rowCount();
            $lastinserid = $db->lastInsertId();
            if($count && $lastinserid) {
                $this->logger->info('AddProductAction: New product plan discount has been created successfully for comp_id-'.$comp_id);
                if($plans_count>0) {   
                    $sql = $db->prepare("INSERT INTO map_plan_product (comp_id, product_id, plan_id, created_by, created_on, is_active ) VALUES (:comp_id, :product_id, :plan_id, :created_by, :created_on, :status)");
                    $count = 0;
                    for($i = 0; $i<$plans_count; $i++) {
                        $sql->execute(array(':comp_id' => $comp_id,':product_id' => $lastinserid,':plan_id' => $plans[$i],':created_by' => $user_id,':created_on' => $date, ':status' => 1 ));
                        $count += $sql->rowCount();
                    }
                }
                if($count > 0) {
                    $this->logger->info('AddProductAction: New product has been created successfully for comp_id-'.$comp_id);
                    $response->getBody()->write(
                        json_encode(array(
                            "code" => 1,
                            "status" => 1,
                            "message" => "New product has been created successfully",
                            "id" => $lastinserid
                        ))
                    );
                } else {
                    $this->logger->info('AddProductAction: Failed to create new product and plan mapping for comp_id-'.$comp_id);
                    $response->getBody()->write(
                        json_encode(array(
                            "code" => 1,
                            "status" => 2,
                            "message" => "Failed to create new product and plan mapping"
                        ))
                    );
                }
            } else {
                $this->logger->info('AddProductAction: Failed to create new product for comp_id-'.$comp_id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "message" => "Failed to create new product"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('AddProductAction: SQL error in creating new product for comp_id-'.$comp_id.'--'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'SQL error in creating new product'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('AddProductAction: Error in creating new product for comp_id-'.$comp_id.'--'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in creating new product'
                ))
            );
        }
		return $response->withHeader('Content-Type', 'application/json');
    }
}