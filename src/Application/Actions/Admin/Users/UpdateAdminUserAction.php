<?php

declare(strict_types=1);

namespace App\Application\Actions\Admin\Users;

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
use App\Application\Helpers\AdminHelper;
use App\Application\Helpers\CommonHelper;

class UpdateAdminUserAction implements RequestHandlerInterface
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
        $this->logger->info('UpdateAdminUserAction: handler dispatched');
        $data = $request->getParsedBody();
        $validateData = (array)$request->getParsedBody();

        // Within the Application Service class: Do the validation
        $validationFactory = new CakeValidationFactory();
        $validator = $validationFactory->createValidator();
		//Form validation
        $validator
            ->notEmptyString('first_name', 'Field required')
			->requirePresence('first_name')
            ->notEmptyString('last_name', 'Field required')
			->requirePresence('last_name')
			->requirePresence('email')
            ->notEmptyString('role', 'Field required')
			->requirePresence('role')
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
            $comp_id = $request->getAttribute('compid');
            $user_id =  $request->getAttribute('userid');
            $first_name = isset($data['first_name']) ? $data["first_name"] : '';
            $last_name = isset($data['last_name']) ? $data["last_name"] : '';
            $email = isset($data['email']) ? $data["email"] :  '';
            $password = isset($data['password']) ? $data["password"] :  '';
            $role = isset($data['role']) ? $data["role"] :  '';
            $status = isset($data['status']) ? $data["status"] :  '';
            $date = date('Y-m-d h:i:s');
            $password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $date = date('Y-m-d h:i:s');

            $this->logger->info('UpdateAdminUserAction: Id'.$id);
            $db =  $this->connection;
            $response = new Response();
            $adminHelper = new AdminHelper();		
            if($email) {
                $isAdminUserExists = $adminHelper->isAdminUserExists($db, $email, $comp_id);
                if($isAdminUserExists==0) {
                    $sql = $db->prepare("UPDATE company_admin set first_name = :first_name,last_name=:last_name,email=:email,role=:role,updated_by=:updated_by,updated_on=:updated_on,is_active=:status where id = :id and comp_id=:comp_id");		
                    $sql->execute(array(':first_name'=>$first_name,':last_name' => $last_name,':email' => $email, ':role' => $role, ':updated_by' => $user_id,':updated_on' => $date,':status' => $status,':id' => $id, ':comp_id' => $comp_id));
                    $count = $sql->rowCount();
                } else {
                    $this->logger->info('AddAdminUserAction: User name already exists'.$email);
                    $response->getBody()->write(
                        json_encode(array(
                            "code" => 1,
                            "status" => 2,
                            "message" => "User name already exists"
                        ))
                    );
                    return $response->withHeader('Content-Type', 'application/json');
                }
            } else {
                $sql = $db->prepare("UPDATE company_admin set first_name = :first_name,last_name=:last_name,role=:role,updated_by=:updated_by,updated_on=:updated_on,is_active=:status where id = :id and comp_id=:comp_id");		
                $sql->execute(array(':first_name'=>$first_name,':last_name' => $last_name, ':role' => $role, ':updated_by' => $user_id,':updated_on' => $date,':status' => $status,':id' => $id, ':comp_id' => $comp_id));
                $count = $sql->rowCount();
            }

            if( $count > 0 ) {
                $response->getBody()->write(
					json_encode(array(
						"code" => 1,
						"status" => 1,
						"message" => "Admin user profile update successfully"
					))
				);
			} else {
				$response->getBody()->write(
					json_encode(array(
						"code" => 1,
						"status" => 2,
						"message" => "No changes has been made."
					))
				);
			}
        }
        catch(MySQLException $e) {
            $this->logger->info('UpdateAdminUserAction: Error in updating admin user details---'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in updating admin user details'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('UpdateAdminUserAction: Error in updating brand detail---'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in updating brand detail'
                ))
            );
        }
		return $response->withHeader('Content-Type', 'application/json');
    }
}