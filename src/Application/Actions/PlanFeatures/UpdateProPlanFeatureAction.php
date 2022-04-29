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


class UpdateProPlanFeatureAction implements RequestHandlerInterface
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
        $this->logger->info('UpdateProPlanFeatureAction: handler dispatched');
        $data = $request->getParsedBody();
        $validateData = (array)$request->getParsedBody();

        // Within the Application Service class: Do the validation
        $validationFactory = new CakeValidationFactory();
        $validator = $validationFactory->createValidator();
		//Form validation
        $validator
            ->requirePresence('id')
            ->notEmptyString('id', 'Field required')
            ->requirePresence('product_plan_id')
            ->notEmptyString('product_plan_id', 'Field required')
            ->notEmptyString('feature_desc', 'Field required')
			->requirePresence('feature_desc')
            ->requirePresence('comp_id')
            ->notEmptyString('comp_id', 'Field required')
            ->requirePresence('brand_id')
            ->notEmptyString('brand_id', 'Field required')
            ->requirePresence('status')
            ->notEmptyString('status', 'Field required')
            ->requirePresence('user_id')
            ->notEmptyString('user_id', 'Field required');

        $validationResult = $validationFactory->createValidationResult(
            $validator->validate($validateData)
        );
		//throw exception for validation failure
        if ($validationResult->fails()) {
            throw new ValidationException('Please check your input', $validationResult);
        }
        try {
            $id = isset($data['id']) ? $data["id"] : '';
            $product_plan_id = isset($data['product_plan_id']) ? $data["product_plan_id"] :  '';
            $feature_desc = isset($data['feature_desc']) ? $data["feature_desc"] : '';
            $comp_id = isset($data['comp_id']) ? $data["comp_id"] :  '';
            $brand_id = isset($data['brand_id']) ? $data["brand_id"] :  '';
            $status = isset($data['status']) ? $data["status"] :  '';
            $user_id = isset($data['user_id']) ? $data["user_id"] :  '';
            $date = date('Y-m-d h:i:s');

            $this->logger->info('UpdateProPlanFeatureAction: Product plan feature id'.$id.'--brand id----'.$brand_id);
            $db =  $this->connection;
            $response = new Response();
            $sql = $db->prepare("UPDATE product_plan_features set feature_desc = :feature_desc,is_active=:status,updated_by=:updated_by,updated_on=:updated_on where id = :id and product_plan_id=:product_plan_id and comp_id = :comp_id and brand_id=:brand_id");		
            $sql->execute(array(':feature_desc'=>$feature_desc,':status' => $status,':updated_by' => $user_id, ':updated_on' => $date,':id' => $id,':product_plan_id' => $product_plan_id,':comp_id' => $comp_id,':brand_id' => $brand_id));
            $count = $sql->rowCount();
            if($count) {
                $this->logger->info('UpdateProPlanFeatureAction: Product plan feature detail updated successfully'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 1,
                        "message" => "Product plan feature detail updated successfully",
                        "id" => $id
                    ))
                );
            } else {
                $this->logger->info('UpdateProPlanFeatureAction: Failed to update product plan feature'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "message" => "Failed to update product plan feature detail"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('UpdateProPlanFeatureAction: SQL Error in updating product plan feature--'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'SQL Error in updating product plan feature'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('UpdateProPlanFeatureAction: Error in updating product plan feature--'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in updating product plan feature'
                ))
            );
        }
		return $response->withHeader('Content-Type', 'application/json');
    }
}