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


class ViewProPlanAction implements RequestHandlerInterface
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
        $this->logger->info('ViewProPlanAction: handler dispatched');
        $data = $request->getParsedBody();
        $validateData = (array)$request->getParsedBody();

        // Within the Application Service class: Do the validation
        $validationFactory = new CakeValidationFactory();
        $validator = $validationFactory->createValidator();
        //Form field validation
        $validator
			->requirePresence('id')
            ->notEmptyString('id', 'Field required')
            ->requirePresence('brand_id')
            ->notEmptyString('brand_id', 'Field required');
        $validationResult = $validationFactory->createValidationResult(
            $validator->validate($validateData)
        );

        if ($validationResult->fails()) {//throws an error if validation fails
            throw new ValidationException('Please check your input', $validationResult);
        }
        $id = isset($data['id']) ? $data["id"] : '';
        $brand_id = isset($data['brand_id']) ? $data["brand_id"] :  '';
        $this->logger->info('ViewProPlanAction: plan id'.$id);
        $db =  $this->connection;
        $sql = $db->prepare('SELECT * from product_plan where id=:id and brand_id=:brand_id');
        $sql->execute(array(':id' => $id,':brand_id' => $brand_id));
        $data = $sql->fetchAll(PDO::FETCH_OBJ);
        $response = new Response();
        if(count($data)>0){
            $this->logger->info('ViewProPlanAction: Product plan found"'.$id);
            $response->getBody()->write(
                json_encode(array(
                    "code" => 1,
                    "status" => 1,
                    "message" => "Product plan found",
                    "result" => $data
                ))
            );
        } else {
            $this->logger->info('ViewProPlanAction: Product plan found"'.$id);
            $response->getBody()->write(
                json_encode(array(
                    "code" => 1,
                    "status" => 2,
                    "message" => "Product plan not found"
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

   
}