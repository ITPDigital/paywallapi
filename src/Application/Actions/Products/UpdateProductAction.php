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

class UpdateProductAction implements RequestHandlerInterface
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
        $this->logger->info('UpdateProductAction: handler dispatched');
        $commonHelper = new CommonHelper();
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
            $id = $commonHelper->resolveArg($request,'id');
            $brand_id = isset($data['brand_id']) ? $data["brand_id"] : '';
            $comp_id = $request->getAttribute('compid');
            $product_name = isset($data['product_name']) ? $data["product_name"] : '';
            $display_name = isset($data['display_name']) ? $data["display_name"] : '';
            $product_desc = isset($data['product_desc']) ? $data["product_desc"] :  '';
            $start_date = isset($data['start_date']) ? $data["start_date"] :  '';
            $end_date = isset($data['end_date']) ? $data["end_date"] :  '';
            $user_id = $request->getAttribute('userid');
            $status = isset($data['status']) ? $data["status"] :  '';
            $date = date('Y-m-d h:i:s');

            $plans = isset($data['plans']) ? $data["plans"] :  '';
            $plans_count = count($plans);
            $this->logger->info('UpdateProductAction: Product id'.$id.'--comp_id----'.$comp_id);
            $db =  $this->connection;
            $response = new Response();
            if($end_date!='') {
                $sql = $db->prepare("UPDATE products set brand_id = :brand_id, product_name=:product_name, display_name=:display_name, product_desc=:product_desc, start_date=:start_date, end_date=:end_date, updated_by=:user_id, updated_on=:updated_on, is_active=:status where id = :id and comp_id = :comp_id");
                $sql->execute(array(':brand_id'=>$brand_id,':product_name'=>$product_name,':display_name' => $display_name,':product_desc' => $product_desc, 
            ':start_date' => $start_date,':end_date' => $end_date,':user_id' => $user_id,':updated_on' => $date,':status' => $status,':id' => $id,':comp_id' => $comp_id));
            } else {
                $sql = $db->prepare("UPDATE products set brand_id = :brand_id, product_name=:product_name, display_name=:display_name, product_desc=:product_desc, start_date=:start_date, updated_by=:user_id, updated_on=:updated_on, is_active=:status where id = :id and comp_id = :comp_id");
                $sql->execute(array(':brand_id'=>$brand_id,':product_name'=>$product_name,':display_name' => $display_name,':product_desc' => $product_desc, 
            ':start_date' => $start_date,':user_id' => $user_id,':updated_on' => $date,':status' => $status,':id' => $id,':comp_id' => $comp_id));
            }
            $count = $sql->rowCount();
            $this->logger->info('UpdateProductAction: Product detail updated successfully'.$id);
            if($plans_count>0) {
                foreach ($plans as $pm) {
                    $type =  $pm['version'];
                    if($type==1) {
                        $sql = $db->prepare("INSERT INTO map_plan_product (comp_id, product_id, plan_id, created_by, created_on, is_active ) VALUES (:comp_id, :product_id, :plan_id, :created_by, :created_on, :status)");
                        $sql->execute(array(':comp_id' => $comp_id,':product_id' => $id,':plan_id' => $pm['plan_id'],':created_by' => $user_id,':created_on' => $date, ':status' => $pm['status'] ));
                    }
                    else if($type==2) {
                        $sql = $db->prepare("UPDATE map_plan_product set is_active = :status,updated_by=:updated_by,updated_on=:updated_on where plan_id = :plan_id and product_id = :product_id");
                        $sql->execute(array(':status' => $pm['status'],':updated_by' => $user_id,':updated_on' => $date,':plan_id' => $pm['plan_id'],':product_id' => $id));
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
                $this->logger->info('UpdateProductAction: Failed to update product plan discount'.$id);
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
            $this->logger->info('UpdateProductAction: Error in update product plan discount---'.$id.'-----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in update product plan discount'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('UpdateProductAction: Error in update product plan discount---'.$id.'-----'.$e->getMessage());
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