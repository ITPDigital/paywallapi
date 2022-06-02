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
use App\Application\Helpers\UserHelper;


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


class ForgotPasswordAction implements RequestHandlerInterface
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
    
    public function resetPasswordMail($db, $data) {
        $reset_key = bin2hex(random_bytes(20));
        $service_name = 'resetpassword_' . $data->brand_id;
        $domian = $_SERVER['HTTP_HOST'];
        $expire = time() + (60 * 60 * 24);
        $cquery = $db->prepare('SELECT count(*) from user_reset_password WHERE email= :email and is_active= :status');
        $cquery->execute(array(':email' => $data->email, ':status' => 1));
        $ncount =  $cquery->fetchColumn();
        if ($ncount) {
          $sql = $db->prepare('UPDATE user_reset_password SET brand_id= :brand_id, reset_key= :reset_key, expire= :expire WHERE email= :email AND brand_id= :brand_id');
          $sql->execute(array(':brand_id' => $data->brand_id, ':email' => $data->email,':reset_key' => $reset_key,':expire' => $expire));					
        }
        else {
          $sql = $db->prepare('INSERT INTO user_reset_password (brand_id, email, reset_key, expire, is_active) VALUES (:brand_id, :email, :reset_key, :expire, :status)');
          $sql->execute(array(':brand_id' => $data->brand_id, ':email' => $data->email, ':reset_key' => $reset_key, ':expire' => $expire, ':status' => 1));					
        }

        $link = $domian . '/subscriptions-new/reset-password.html?resetAttributeKey='  . $reset_key . '&serviceName=' . $service_name;
        $mail_body = '<h2>Please click the link below to reset your password.</h2>';
        $mail_body .= '<a href="' . $link . '">' . $link . '</a>';
        
        $mail = new PHPMailer;
        $mail->Body = $mail_body;
        $mail->Subject="Reset Your Password";
        $mail->addAddress($data->email);
        $userHelper = new UserHelper();        

        $output = $userHelper->sendMail($mail);

        return  $output;
    }    

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->logger->info('Forgot Password action: handler dispatched');
        $data = $request->getParsedBody();
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
            ->notEmptyString('brand_domain', 'Field required')
			->requirePresence('brand_domain');
        $validationResult = $validationFactory->createValidationResult(
            $validator->validate($validateData)
        );
        if ($validationResult->fails()) {//throws an error if validation fails
            throw new ValidationException('Please check your input', $validationResult);
        }
        try {
            $email = isset($data['email']) ? $data["email"] : '';
            $brand = isset($data['brand_domain']) ? $data["brand_domain"] :  '';
            $this->logger->info('Forgot Password action: email'.$email);
            $db =  $this->connection;
            $brand_id = $this->getBrandId($db, $brand);
            $response = new Response();
           // $brand_id = $brand;
            if($brand_id != 0) {
                $sql = $db->prepare('SELECT * from users where brand_id=:brand_id and email= :email and status= :status');
                $sql->execute(array(':brand_id' => $brand_id, ':email' => $email, ':status' => 1));
                $data = $sql->fetchAll(PDO::FETCH_OBJ);
                if(count($data)>0) {
                    $userdata = $this->resetPasswordMail( $db, $data[0] );
                    if ($userdata) {
                    $this->logger->info('Forgot Password action: Reset Password Link Sent '.$email);
                    $response->getBody()->write(
                        json_encode(array(
                            "code" => 1,
                            "status" => 1,
                            "message" => "Reset Password Link Sent"
                        ))
                    );
                    }
                    else {
                    $this->logger->info('Forgot Password action: Reset Password Mail is not Sent '.$email);
                    $response->getBody()->write(
                        json_encode(array(
                            "code" => 1,
                            "status" => 4,
                            "message" => "Reset Password Mail is not Sent"
                        ))
                    );					
                    }				
				
                } else {
                    $this->logger->info('Forgot Password action: Email not found '.$email);
                    $response->getBody()->write(
                        json_encode(array(
                            "code" => 1,
                            "status" => 3,
                            "message" => "Email not found"
                        ))
                    );
                }
            } else {

                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "message" => "Invalid Brand"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'SQL error in forgot password page'
                ))
            );
        }
        catch(Exception $e) {
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in forgot password page'
                ))
            );
        }
		/*$response = $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization');*/
        return $response->withHeader('Content-Type', 'application/json');
    }
}