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
use App\Application\Helpers\CommonHelper;

class AdminChangePwdAction implements RequestHandlerInterface
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
        $this->logger->info('Admin Change password action: handler dispatched');
        $data = $request->getParsedBody();
        $validateData = (array)$request->getParsedBody();

        // Within the Application Service class: Do the validation
        $validationFactory = new CakeValidationFactory();
        $validator = $validationFactory->createValidator();
        //Form field validation
        $validator
            ->notEmptyString('old_password', 'Field required')
            ->requirePresence('old_password')
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
            ->requirePresence('confirm_password');

        $validationResult = $validationFactory->createValidationResult(
            $validator->validate($validateData)
        );

        if ($validationResult->fails()) {//throws an error if validation fails
            throw new ValidationException('Please check your input', $validationResult);
        }
        try {
            $oldPwd = isset($data['old_password']) ? $data["old_password"] : '';
            $password = isset($data['password']) ? $data["password"] :  '';
            $commonHelper = new CommonHelper();
            $comp_id = $request->getAttribute('compid');
            $user_id =  $request->getAttribute('userid');
            $date = date('Y-m-d h:i:s');

            $this->logger->info('Admin Change password action: email'.$user_id);
            $db =  $this->connection;
            $sql = $db->prepare('SELECT password from company_admin where id=:user_id and comp_id=:comp_id and is_active=:status');
            $sql->execute(array(':user_id' => $user_id,':comp_id' => $comp_id,':status' => 1));
            $data = $sql->fetchAll(PDO::FETCH_OBJ);
            $response = new Response();
            if(count($data)>0){
                if(password_verify($oldPwd, $data[0]->password)) {//validate password
                    //$token = JWT::encode(['id' => $role, 'email' => $user_id], "TOURSECERTKEY", "HS256");//create new token for api authorization
                    $this->logger->info('Admin Change password action: User found '.$user_id);
                        $password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
						$sql = $db->prepare("UPDATE company_admin set password = :password,updated_by=:updated_by,updated_on=:updated_on where id = :id and comp_id=:comp_id");		
                        $sql->execute(array(':password'=>$password,':updated_by' => $user_id,':updated_on' => $date,':id' => $user_id, ':comp_id' => $comp_id));
                        $count = $sql->rowCount();
                        if( $count > 0 ) {
                            $response->getBody()->write(
                                json_encode(array(
                                    "code" => 1,
                                    "status" => 1,
                                    "message" => "Admin user password updated successfully"
                                ))
                            );
                        } else {
                            $response->getBody()->write(
                                json_encode(array(
                                    "code" => 0,
                                    "status" => 3,
                                    "message" => "No changes has been made."
                                ))
                            );
                        }
                } else {
                    $this->logger->info('Change password action: Password mismatch '.$user_id);
                    $response->getBody()->write(
                        json_encode(array(
                            "code" => 0,
                            "status" => 2,
                            "message" => "Password mismatch"
                        ))
                    );
                }
            } else {
                $this->logger->info('Change password action: User not found '.$user_id);
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
            $this->logger->info('AdminChangePwdAction: SQL Error in updating admin user password-----'.$user_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'SQL Error in updating admin user password'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('AdminChangePwdAction: Error in updating admin user password-----'.$user_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in updating admin user password'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

   
}