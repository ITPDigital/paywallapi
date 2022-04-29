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


class DeleteProPlanAction implements RequestHandlerInterface
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
        $this->logger->info('DeleteProPlanAction: handler dispatched');
        $data = $request->getParsedBody();
        $validateData = (array)$request->getParsedBody();

        // Within the Application Service class: Do the validation
        $validationFactory = new CakeValidationFactory();
        $validator = $validationFactory->createValidator();
        //Form field validation
        $validator
			->requirePresence('id')
            ->notEmptyString('id', 'Field required')
            ->requirePresence('comp_id')
            ->notEmptyString('comp_id', 'Field required')
            ->requirePresence('status')
            ->notEmptyString('status', 'Field required')
            ->requirePresence('user_id')
            ->notEmptyString('user_id', 'Field required');
        $validationResult = $validationFactory->createValidationResult(
            $validator->validate($validateData)
        );

        if ($validationResult->fails()) {//throws an error if validation fails
            throw new ValidationException('Please check your input', $validationResult);
        }
        $id = isset($data['id']) ? $data["id"] : '';
        $comp_id = isset($data['comp_id']) ? $data["comp_id"] :  '';
        $status = isset($data['status']) ? $data["status"] :  '';
        $user_id = isset($data['user_id']) ? $data["user_id"] :  '';
        $date = date('Y-m-d h:i:s');
        $this->logger->info('DeleteProPlanAction: product plan id'.$id);
        $db =  $this->connection;
        $sql = $db->prepare('UPDATE product_plan set is_active = :status,updated_by =:user_id, updated_on=:updated_on  where id = :id and comp_id = :comp_id');
        $sql->execute(array(':status' => $status,':user_id' => $user_id,'updated_on'=> $date,':id' => $id,':comp_id' => $comp_id));
        $count = $sql->rowCount();
        $response = new Response();
        if($count > 0){
            $this->logger->info('DeleteBrandAction: Product plan status updated successfully'.$id);
            $response->getBody()->write(
                json_encode(array(
                    "code" => 1,
                    "status" => 1,
                    "message" => "Product plan updated successfully"
                ))
            );
        } else {
            $this->logger->info('DeleteBrandAction: Product plan not found'.$id);
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 1,
                    "message" => "Product plan not found"
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }
}