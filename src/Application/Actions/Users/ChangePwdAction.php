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

class ChangePwdAction implements RequestHandlerInterface
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
        $this->logger->info('ChangePwdAction: handler dispatched----');
        $commonHelper = new CommonHelper();	
        $db =  $this->connection;
       // $commonHelper = $commonHelper->isValidRequest($db,$request);
       //$commonHelper = true;
        if(true) {
            $data = $request->getParsedBody();
            $validateData = (array)$request->getParsedBody();

            // Within the Application Service class: Do the validation
            $validationFactory = new CakeValidationFactory();
            $validator = $validationFactory->createValidator();
            
            //Form field validation
            $validator
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
            $response = new Response();
            try {
                $id = $commonHelper->resolveArg($request,'id');
                $brand_id = $request->getAttribute('brandid');
                $pwd = isset($data['password']) ? $data["password"] :  '';
                $pwd = password_hash($pwd, PASSWORD_BCRYPT, ['cost' => 12]);
                $date = date('Y-m-d h:i:s');
                $this->logger->info('ChangePwdAction: user id'.$id);
                $sql = $db->prepare('UPDATE users set password = :password, updated_on=:updated_on where id = :id and brand_id = :brand_id');
                $sql->execute(array(':password' => $pwd,':updated_on' => $date,':id' => $id,':brand_id' => $brand_id));
                $count = $sql->rowCount();
                if($count > 0){
                    $this->logger->info('ChangePwdAction: User password updated successfully'.$id);
                    $response->getBody()->write(
                        json_encode(array(
                            "code" => 1,
                            "status" => 1,
                            "message" => "User password updated successfully"
                        ))
                    );
                } else {
                    $this->logger->info('ChangePwdAction: User not found'.$id);
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
                $this->logger->info('ChangePwdAction: SQL error in updating user password-----'.$id.'----'.$e->getMessage());
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 0,
                        "status" => 0,
                        "message" => 'SQL error in updating user password'
                    ))
                );
            }
            catch(Exception $e) {
                $this->logger->info('ChangePwdAction: Error in updating user password-----'.$id.'----'.$e->getMessage());
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 0,
                        "status" => 0,
                        "message" => 'Error in updating user password'
                    ))
                );
            }
        } else {
            $response = new Response();
            $this->logger->info('ChangePwdAction: Invalid request-----');
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Invalid request'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }
}