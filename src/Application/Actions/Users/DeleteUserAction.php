<?php

declare(strict_types=1);

namespace App\Application\Actions\Users;

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


class DeleteUserAction implements RequestHandlerInterface
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
        $commonHelper = new CommonHelper();
        $this->logger->info('DeleteUserAction: handler dispatched');
        $data = $request->getParsedBody();
        $validateData = (array)$request->getParsedBody();

        // Within the Application Service class: Do the validation
        $validationFactory = new CakeValidationFactory();
        $validator = $validationFactory->createValidator();
        //Form field validation
        $validator
            ->requirePresence('brand_id')
            ->notEmptyString('brand_id', 'Field required');
        $validationResult = $validationFactory->createValidationResult(
            $validator->validate($validateData)
        );

        if ($validationResult->fails()) {//throws an error if validation fails
            throw new ValidationException('Please check your input', $validationResult);
        }
        try {
            $id =  $commonHelper->resolveArg($request,'id');
            $brand = isset($data['brand_id']) ? $data["brand_id"] :  '';
            $status =  $commonHelper->resolveArg($request,'status');
            $date = date('Y-m-d h:i:s');
            $this->logger->info('DeleteUserAction: user id'.$id);
            $db =  $this->connection;
            $sql = $db->prepare('UPDATE users set status = :status, updated_on=:updated_on where id = :id and brand_id = :brand');
            $sql->execute(array(':status' => $status,':updated_on' => $date,':id' => $id,':brand' => $brand));
            $count = $sql->rowCount();
            $response = new Response();
            if($count > 0){
                $this->logger->info('DeleteUserAction: User status updated successfully'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 1,
                        "message" => "User status updated successfully"
                    ))
                );
            } else {
                $this->logger->info('DeleteUserAction: User not found'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "message" => "User not found"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('DeleteUserAction: SQL error in updating user status-----'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'SQL error in updating user status'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('DeleteUserAction: Error in updating user status-----'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in updating user status'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }
}