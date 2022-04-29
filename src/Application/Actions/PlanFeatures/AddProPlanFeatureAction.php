<?php

declare(strict_types=1);

namespace App\Application\Actions\PlanFeatures;

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


class AddProPlanFeatureAction implements RequestHandlerInterface
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
        $this->logger->info('AddProPlanFeatureAction: handler dispatched');
        $data = $request->getParsedBody();
        $validateData = (array)$request->getParsedBody();

        // Within the Application Service class: Do the validation
        $validationFactory = new CakeValidationFactory();
        $validator = $validationFactory->createValidator();
		//Form validation
        $validator
            ->notEmptyString('brand_id', 'Field required')
			->requirePresence('brand_id')
            ->notEmptyString('comp_id', 'Field required')
			->requirePresence('comp_id')
            ->notEmptyString('product_plan_id', 'Field required')
			->requirePresence('product_plan_id')
            ->notEmptyString('feature_desc', 'Field required')
			->requirePresence('feature_desc')
            ->notEmptyString('user_id', 'Field required')
			->requirePresence('user_id')
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
            $comp_id = isset($data['comp_id']) ? $data["comp_id"] : '';
            $product_plan_id = isset($data['product_plan_id']) ? $data["product_plan_id"] : '';
            $feature_desc = isset($data['feature_desc']) ? $data["feature_desc"] : '';
            $user_id = isset($data['user_id']) ? $data["user_id"] :  '';
            $status = isset($data['status']) ? $data["status"] :  '';
            $date = date('Y-m-d h:i:s');
            $this->logger->info('AddProPlanFeatureAction: brand_id-'.$brand_id.'--product plan id--'.$product_plan_id.'--feature description--'.$feature_desc);
            $db =  $this->connection;
            $response = new Response();
            $sql = $db->prepare("INSERT INTO product_plan_features (product_plan_id,feature_desc,created_by,created_on,comp_id,brand_id,is_active) VALUES (:product_plan_id,:feature_desc,:created_by,:created_on,:comp_id,:brand_id,:is_active)");
            $sql->execute(array(':product_plan_id' => $product_plan_id,':feature_desc' => $feature_desc,':created_by' => $user_id, ':created_on' => $date, ':comp_id' => $comp_id, ':brand_id' => $brand_id, ':is_active' => $status));
            $count = $sql->rowCount();
            $lastinserid = $db->lastInsertId();
            if($count && $lastinserid) {
                $this->logger->info('AddProPlanFeatureAction: New product plan feature added successfully for product_plan_id-'.$product_plan_id.'--brand id--'.$brand_id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 1,
                        "message" => "New product plan feature created successfully",
                        "id" => $lastinserid
                    ))
                );
            } else {
                $this->logger->info('AddProPlanFeatureAction: Failed to add new product plan feature for product_plan_id-'.$product_plan_id.'--brand id--'.$brand_id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 4,
                        "message" => "Failed to add new product plan feature"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('AddProPlanFeatureAction: Error in adding new product plan feature for product_plan_id-'.$product_plan_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in adding new product plan feature'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('AddProPlanFeatureAction: Error in adding new product plan feature for product_plan_id-'.$product_plan_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in adding new product plan feature'
                ))
            );
        }
		return $response->withHeader('Content-Type', 'application/json');
    }
}