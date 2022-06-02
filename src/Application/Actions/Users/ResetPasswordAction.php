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

class ResetPasswordAction implements RequestHandlerInterface
{
    private $logger;
    private $connection;

    public function __construct(PDO $connection,LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function getBrandId($db, $brand_domain){
		$sql = $db->prepare('SELECT id from brands where domain_name=:domain_name and is_active=:status');
		$sql->execute(array(':domain_name' => $brand_domain,':status' => 1));
		$brandDatas = $sql->fetch(PDO::FETCH_ASSOC);
		$brandId = 0;
        $count = $sql->rowCount();
        if($count>0){
            $brandId = (int)$brandDatas['id'];
        }
        return $brandId;
	}	

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->logger->info('Reset Password action: handler dispatched');
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
			->requirePresence('confirm_password')
            ->notEmptyString('brand_domain', 'Field required')
			->requirePresence('brand_domain');	
        $validationResult = $validationFactory->createValidationResult(
            $validator->validate($validateData)
        );
        if ($validationResult->fails()) {//throws an error if validation fails
            throw new ValidationException('Please check your input', $validationResult);
        }
        try {
            $commonHelper = new CommonHelper();
            $brand_domain = isset($data['brand_domain']) ? $data["brand_domain"] :  '';
			$password = isset($data['password']) ? password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]) : '';
			$token = $commonHelper->resolveArg($request,'token');
            $this->logger->info('Reset Password action: token'.$token);
            $db =  $this->connection;
            $brand_id = $this->getBrandId($db, $brand_domain);
            $sql = $db->prepare('SELECT email from user_reset_password where brand_id= :brand_id and reset_key= :reset_key and is_active= :is_active AND expire > :timestamp');
            $sql->execute(array(':brand_id' => $brand_id, ':reset_key' => $token, ':is_active' => 1, ':timestamp' => time()));
            $data = $sql->fetch(PDO::FETCH_OBJ);
			$count = $sql->rowCount();
            $response = new Response();
            if ( $count > 0 ) {
              $sql = $db->prepare("UPDATE users SET password = :password WHERE email = :email AND brand_id = :brand_id");
              $sql->execute(array(':password' => $password, ':email' => $data->email, ':brand_id' => $brand_id));
			  $response->getBody()->write(
				json_encode(array(
					"code" => 1,
					"status" => 1,
					"message" => "Successfully reset password"
				))
			  );
              $sql = $db->prepare("UPDATE user_reset_password SET is_active = :is_active WHERE email = :email AND brand_id = :brand_id");
              $sql->execute(array(':is_active' => 0, ':email' => $data->email, ':brand_id' => $brand_id));              			  
			}
            else {
			  $response->getBody()->write(
				json_encode(array(
					"code" => 1,
					"status" => 2,
					"message" => "Reset password link is expired"
				))
			  );
            }
		}			
        catch(MySQLException $e) {
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'SQL error in reset password page'
                ))
            );
        }
        catch(Exception $e) {
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in reset password page'
                ))
            );
        }
		/*$response = $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization');*/
        return $response->withHeader('Content-Type', 'application/json');
    }
}