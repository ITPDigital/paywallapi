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


class AddAdminUserAction implements RequestHandlerInterface
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
        $this->logger->info('AddAdminUserAction: handler dispatched');
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
            ->notEmptyString('email', 'Field required')
			->add('email', 'validFormat', [
				'rule' => 'email',
				'message' => 'E-mail must be valid'
			])
			->requirePresence('email')
            ->notEmptyString('password', 'Field required')
			->add('password',[  
                'match'=>[  
                    'rule'=> ['compareWith','confirm_password'], 
                    'message'=>'The passwords does not match!', 
                ]  
            ])
			->requirePresence('password')
            ->notEmptyString('confirm_password', 'Field required')
			->add('confirm_password',[  
                'match'=>[  
                    'rule'=> ['compareWith','password'], 
                    'message'=>'The passwords does not match!', 
                ]  
            ])
			->requirePresence('confirm_password')
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

            $this->logger->info('AddAdminUserAction: email'.$email);
            $db =  $this->connection;
            $adminHelper = new AdminHelper();	
            $isAdminUserExists = $adminHelper->isAdminUserExists($db, $email, $comp_id);
            $response = new Response();
            if($isAdminUserExists>0) {
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
            if($isAdminUserExists==0) {
                $sql = $db->prepare("INSERT INTO company_admin (comp_id, first_name, last_name, email, password, role, created_by, created_on, is_active) VALUES (:comp_id, :first_name, :last_name, :email, :password, :role, :user_id, :created_on, :status)");
                $sql->execute(array(':comp_id' => $comp_id, ':first_name' => $first_name, ':last_name' => $last_name, ':email' => $email, ':password' => $password, ':role' => $role,':user_id' => $user_id, ':created_on' => $date,':status' => $status));
                $count = $sql->rowCount();
                $lastinserid = $db->lastInsertId();
                $this->logger->info('AddAdminUserAction: New Admin user created successfully'.$lastinserid);
                if($count && $lastinserid) {
                    $this->logger->info('AddAdminUserAction: New Admin user created successfully'.$email);
                    $response->getBody()->write(
                        json_encode(array(
                            "code" => 1,
                            "status" => 1,
                            "message" => "New admin user created successfully",
                            "id" => $lastinserid
                        ))
                    );
                } else {
                    $this->logger->info('AddAdminUserAction: Failed to create new Admin user'.$email);
                    $response->getBody()->write(
                        json_encode(array(
                            "code" => 0,
                            "status" => 1,
                            "message" => "Failed to create new admin user"
                        ))
                    );
                }
            } else {
                $this->logger->info('AddAdminUserAction: Failed to create new Admin user'.$email);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 0,
                        "status" => 1,
                        "message" => "Failed to create new admin user"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('AddAdminUserAction: Error in creating new admin user--'.$email.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in creating new admin user'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('AddAdminUserAction: Error in creating new admin user---'.$email.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in creating new admin user'
                ))
            );
        }
		return $response->withHeader('Content-Type', 'application/json');
    }
}