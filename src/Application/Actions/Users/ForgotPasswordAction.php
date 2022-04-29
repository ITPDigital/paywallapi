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
			->requirePresence('email');
        $validationResult = $validationFactory->createValidationResult(
            $validator->validate($validateData)
        );
        if ($validationResult->fails()) {//throws an error if validation fails
            throw new ValidationException('Please check your input', $validationResult);
        }
        try {
            $email = isset($data['email']) ? $data["email"] : '';
            $this->logger->info('Forgot Password action: email'.$email);
            $db =  $this->connection;
            $sql = $db->prepare('SELECT * from users where email= :email and status= :status');
            $sql->execute(array(':email' => $email, ':status' => 1));
            $data = $sql->fetchAll(PDO::FETCH_OBJ);
            $response = new Response();
            if(count($data)>0) {
				$mail = new PHPMailer;
				$userHelper = new UserHelper();
				$userdata = $userHelper->sendMail( $db, $mail, $data[0]);
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
                        "code" => 0,
                        "status" => 0,
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