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


class ListProPlanAction implements RequestHandlerInterface
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
        $this->logger->info('ListProPlanAction: handler dispatched');
        $data = $request->getParsedBody();
        $validateData = (array)$request->getParsedBody();

        // Within the Application Service class: Do the validation
        $validationFactory = new CakeValidationFactory();
        $validator = $validationFactory->createValidator();
        //Form field validation
        $validator
            ->requirePresence('comp_id')
            ->notEmptyString('comp_id', 'Field required');
        $validationResult = $validationFactory->createValidationResult(
            $validator->validate($validateData)
        );

        if ($validationResult->fails()) {//throws an error if validation fails
            throw new ValidationException('Please check your input', $validationResult);
        }
        $comp_id = isset($data['comp_id']) ? $data["comp_id"] :  '';
        $this->logger->info('ListProPlanAction: Comp id'.$comp_id);
        $db =  $this->connection;
        $sql = $db->prepare('SELECT * from product_plan where comp_id=:comp_id');
        $sql->execute(array(':comp_id' => $comp_id));
        $data = $sql->fetchAll(PDO::FETCH_OBJ);
        $response = new Response();
        if(count($data)>0){
            $this->logger->info('ListProPlanAction: Number of product plans available for company id-'.$comp_id.'-is-'.count($data));
            $response->getBody()->write(
                json_encode(array(
                    "code" => 1,
                    "status" => 1,
                    "message" => "Data found",
                    "result" => $data
                ))
            );
        } else {
            $this->logger->info('ListBrandAction: Product plan not found for company id-'.$comp_id);
            $response->getBody()->write(
                json_encode(array(
                    "code" => 1,
                    "status" => 2,
                    "message" => "No data found"
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

   
}