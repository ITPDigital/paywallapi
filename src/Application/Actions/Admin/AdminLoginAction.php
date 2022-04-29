<?php

declare(strict_types=1);

namespace App\Application\Actions\Admin;

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

class AdminLoginAction implements RequestHandlerInterface
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
        $this->logger->info('Admin Login action: handler dispatched');
        $data = $request->getParsedBody();
        $this->logger->info('$data["email"]'.$data["email"]);
        $validateData = (array)$request->getParsedBody();

        // Within the Application Service class: Do the validation
        $validationFactory = new CakeValidationFactory();
        $validator = $validationFactory->createValidator();
        //Form field validation
        $validator
            ->notEmptyString('email', 'Field required')
			->add('email', 'validFormat', [
				'rule' => 'email',
				'message' => 'E-mail must be valid'
			])
			->requirePresence('email')
            ->notEmptyString('password', 'Field required')
			->requirePresence('password');
        $validationResult = $validationFactory->createValidationResult(
            $validator->validate($validateData)
        );

        if ($validationResult->fails()) {//throws an error if validation fails
            throw new ValidationException('Please check your input', $validationResult);
        }
        try {
            $email = isset($data['email']) ? $data["email"] : '';
            $password = isset($data['password']) ? $data["password"] :  '';
			
			//$password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
			//echo $password;exit;
            $this->logger->info('Admin Login action: email'.$email);
            $db =  $this->connection;
            $sql = $db->prepare('SELECT id,comp_id,password,first_name,last_name,email,role from company_admin where email=:email and is_active=:status');
            $sql->execute(array(':email' => $email,':status' => 1));
            $data = $sql->fetchAll(PDO::FETCH_OBJ);
            //$payload = json_encode($data);
            $response = new Response();
			
            if(count($data)>0){
                if(password_verify($password, $data[0]->password)) {//validate password
                    $userId = $data[0]->id;
                    //$token = JWT::encode(['id' => $role, 'email' => $email], "TOURSECERTKEY", "HS256");//create new token for api authorization
                    $this->logger->info('Admin Login action: User found '.$email);
						$sql = $db->prepare("UPDATE company_admin set last_logged_on=:last_logged_on where email = :email");	
                        $sql->execute(array(':last_logged_on' => date('Y-m-d h:i:s'),':email' => $email));
                        $checksum = $userId . "|" . $data[0]->role ."|" . $data[0]->comp_id;
                        $checksum = hash('sha256', $checksum);  
                        $response->getBody()->write(
							json_encode(array(
                                "code" => 1,
                                "status" => 1,
                                "message" => "User found",
                                "id" => (int)$userId,
								"first_name" => $data[0]->first_name,
								"last_name" => $data[0]->last_name,
								"email" => $data[0]->email,
                                "role" => (int)$data[0]->role,
								"comp_id" => (int)$data[0]->comp_id,
								"checksum" => $checksum
                            ))
                        );
					/*$response->getBody()->write(
                        json_encode(array(
                            "code" => 1,
                            "status" => 1,
                            "message" => "Success"
                        ))
                    );*/
                } else {
                    $this->logger->info('Login action: Password mismatch '.$email);
                    $response->getBody()->write(
                        json_encode(array(
                            "code" => 0,
                            "status" => 2,
                            "message" => "Password mismatch"
                        ))
                    );
                }
            } else {
                $this->logger->info('Login action: User not found '.$email);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 0,
                        "status" => 1,
                        "message" => "User not found"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('AdminLoginAction: SQL error in login-----'.$email.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'SQL error in login'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('AdminLoginAction: Error in login-----'.$email.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in login'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

   
}