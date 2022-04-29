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


class ListProPlanFeatureAction implements RequestHandlerInterface
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
        $this->logger->info('ListProPlanFeatureAction: handler dispatched');
        $data = $request->getParsedBody();
        $validateData = (array)$request->getParsedBody();

        // Within the Application Service class: Do the validation
        $validationFactory = new CakeValidationFactory();
        $validator = $validationFactory->createValidator();
        //Form field validation
        $validator
            ->requirePresence('product_plan_id')
            ->notEmptyString('product_plan_id', 'Field required')
            ->requirePresence('brand_id')
            ->notEmptyString('brand_id', 'Field required')
            ->requirePresence('comp_id')
            ->notEmptyString('comp_id', 'Field required');
        $validationResult = $validationFactory->createValidationResult(
            $validator->validate($validateData)
        );

        if ($validationResult->fails()) {//throws an error if validation fails
            throw new ValidationException('Please check your input', $validationResult);
        }
        try {
            $product_plan_id = isset($data['product_plan_id']) ? $data["product_plan_id"] :  '';
            $brand_id = isset($data['brand_id']) ? $data["brand_id"] :  '';
            $comp_id = isset($data['comp_id']) ? $data["comp_id"] :  '';
            $this->logger->info('ListProPlanFeatureAction: Product Plan id'.$product_plan_id);
            $db =  $this->connection;
            $sql = $db->prepare('SELECT * from product_plan_features where product_plan_id=:product_plan_id and brand_id=:brand_id and comp_id=:comp_id');
            $sql->execute(array(':product_plan_id' => $product_plan_id,':brand_id' => $brand_id,':comp_id' => $comp_id));
            $data = $sql->fetchAll(PDO::FETCH_OBJ);
            $response = new Response();
            if(count($data)>0){
                $this->logger->info('ListProPlanFeatureAction: Number of product plan features available for product plan id-'.$product_plan_id.'-is-'.count($data));
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 1,
                        "message" => "Data found",
                        "result" => $data
                    ))
                );
            } else {
                $this->logger->info('ListProPlanFeatureAction: Product plan feature not available for product plan id-'.$product_plan_id);
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
            $this->logger->info('ListProPlanFeatureAction: SQL error in fetching all product plan features--'.$product_plan_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'SQL error in fetching all product plan features'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('ListProPlanFeatureAction: Error in fetching all product plan features--'.$product_plan_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in fetching all product plan features'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

   
}