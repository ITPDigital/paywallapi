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

class UpdateAdminUserPwdAction implements RequestHandlerInterface
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
        $this->logger->info('UpdateAdminUserPwdAction: handler dispatched');
        $data = $request->getParsedBody();
        $validateData = (array)$request->getParsedBody();

        // Within the Application Service class: Do the validation
        $validationFactory = new CakeValidationFactory();
        $validator = $validationFactory->createValidator();
		//Form validation
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
		//throw exception for validation failure
        if ($validationResult->fails()) {
            throw new ValidationException('Please check your input', $validationResult);
        }
        try {
            $id = $commonHelper->resolveArg($request,'id');
            $comp_id = $request->getAttribute('compid');
            $user_id =  $request->getAttribute('userid');
            $password = isset($data['password']) ? $data["password"] :  '';
            $date = date('Y-m-d h:i:s');
            $password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $date = date('Y-m-d h:i:s');
            $this->logger->info('UpdateAdminUserPwdAction: Id'.$id);
            $db =  $this->connection;
            $response = new Response();
            $sql = $db->prepare("UPDATE company_admin set password = :password,updated_by=:updated_by,updated_on=:updated_on where id = :id and comp_id=:comp_id");		
            $sql->execute(array(':password'=>$password,':updated_by' => $user_id,':updated_on' => $date,':id' => $id, ':comp_id' => $comp_id));
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
						"code" => 1,
						"status" => 2,
						"message" => "No changes has been made."
					))
				);
			}
        }
        catch(MySQLException $e) {
            $this->logger->info('UpdateAdminUserPwdAction: SQL Error in updating admin user password---'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'SQL Error in updating admin user password'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('UpdateAdminUserPwdAction: Error in updating admin user password---'.$id.'----'.$e->getMessage());
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