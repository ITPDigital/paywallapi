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

class AddUserFromAdminAction implements RequestHandlerInterface
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
        $this->logger->info('AddUserFromAdminAction handler dispatched');
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
            ->notEmptyString('industry', 'Field required')
			->requirePresence('industry')
            ->notEmptyString('job_title', 'Field required')
			->requirePresence('job_title')
            ->notEmptyString('country', 'Field required')
			->requirePresence('country')
            ->notEmptyString('company_size', 'Field required')
			->requirePresence('company_size')
			->notEmptyString('brand_id', 'Field required')
			->requirePresence('brand_id');

        $validationResult = $validationFactory->createValidationResult(
            $validator->validate($validateData)
        );
		//throw exception for validation failure
        if ($validationResult->fails()) {
            throw new ValidationException('Please check your input', $validationResult);
        }
		$db =  $this->connection;		
		$userHelper = new UserHelper();	
       // $userdata = $userHelper->createUser($db, $data);
        $first_name = isset($data['first_name']) ? $data["first_name"] : '';
        $last_name = isset($data['last_name']) ? $data["last_name"] : '';
        $email = isset($data['email']) ? $data["email"] : '';
        $password = isset($data['password']) ? $data["password"] :  '';
        $confirm_password = isset($data['confirm_password']) ? $data["confirm_password"] :  '';
		$industry = isset($data['industry']) ? $data["industry"] :  '';
		$job_title = isset($data['job_title']) ? $data["job_title"] :  '';
		$country = isset($data['country']) ? $data["country"] :  '';
		$company_size = isset($data['company_size']) ? $data["company_size"] :  '';
		$brand_id = isset($data['brand_id']) ? $data["brand_id"] :  '';
		$password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
		$date = date('Y-m-d h:i:s');
		$marketing_optin = isset($data['marketing_optin']) ? $data['marketing_optin'] : '';
		$third_party_optin = isset($data['third_party_optin']) ? $data['third_party_optin'] : '';
        $status = isset($data['status']) ? $data['status'] : 1;
		$access_role = isset($data['access_role']) ? $data['access_role'] : 1;

        $dob = isset($data['dob']) ? $data['dob'] : '';
        $gender = isset($data['gender']) ? $data['gender'] : '';
        $phone = isset($data['phone']) ? $data['phone'] : '';
        $gift_address1 = isset($data['gift_address1']) ? $data['gift_address1'] : '';
        $gift_address2 = isset($data['gift_address2']) ? $data['gift_address2'] : '';
        $gift_address_city = isset($data['gift_address_city']) ? $data['gift_address_city'] : '';
        $gift_address_state = isset($data['gift_address_state']) ? $data['gift_address_state'] : '';
        $shipping_contact_number = isset($data['shipping_contact_number']) ? $data['shipping_contact_number'] : '';
        $gift_address_country = isset($data['gift_address_country']) ? $data['gift_address_country'] : '';
        $tax_reg_no = isset($data['tax_reg_no']) ? $data['tax_reg_no'] : '';
        $comp_gift_consent = isset($data['comp_gift_consent']) ? $data['comp_gift_consent'] : '';
        $comp_name = isset($data['comp_name']) ? $data['comp_name'] : '';

        $sub_start_date = isset($data['sub_start_date']) ? $data['sub_start_date'] : '';
        $sub_end_date = isset($data['sub_end_date']) ? $data['sub_end_date'] : '';

		$response = new Response();
        if($brand_id) {
            $isUserExists = $userHelper->isUserNameExists($db, $email, $brand_id);
            if($isUserExists == 0) {
                $isSubUser = 0;
                if($access_role==2 || $access_role==3 || $access_role==4) {//2-Free User, 3-Corporate User, 4-Student User
                    $isSubUser = 1;
                }
                $sql = $db->prepare("INSERT INTO users (brand_id, first_name, last_name, email, password, industry, job_ttl, comp, comp_size, registered_on, status, access_role, is_subscribed_user) 
                values (:brand_id,:first_name,:last_name,:email,:password,:industry,:job_title,:comp_name,:company_size,:registered_on,:status,:access_role,:is_subscribed_user)");
                $sql->execute(array(':brand_id' => $brand_id, ':first_name' => $first_name, ':last_name' => $last_name, ':email' => $email, ':password' => $password, ':industry' => $industry, ':job_title' => $job_title, ':comp_name' => $comp_name, ':company_size' => $company_size, ':registered_on' => date('Y-m-d h:i:s'), ':status' => 1, ':access_role' => $access_role, ':is_subscribed_user' => $isSubUser));
                //$sql->debugDumpParams();
                $count = $sql->rowCount();
                $lastinserid = $db->lastInsertId();
                if ($count && $lastinserid) {
                    $user_dt_sql = $db->prepare("INSERT INTO user_details (user_id, brand_id,country,dob,gender,phone,gift_address1,gift_address2,gift_address_city,gift_address_state,shipping_contact_number,gift_address_country,tax_reg_no,marketing_optin, third_party_optin, comp_gift_consent) VALUES (:user_id,:brand_id,:country,:dob,:gender,:phone,:gift_address1,:gift_address2,:gift_address_city,:gift_address_state,:shipping_contact_number,:gift_address_country,:tax_reg_no, :marketing_optin, :third_party_optin, :comp_gift_consent)");
                    $user_dt_sql->execute(array(':user_id' => $lastinserid, ':brand_id' => $brand_id, ':country' => $country, ':dob' => $dob, ':gender' => $gender, ':phone' => $phone, ':gift_address1' => $gift_address1, ':gift_address2' => $gift_address2, ':gift_address_city' => $gift_address_city, ':gift_address_state' => $gift_address_state, ':shipping_contact_number' => $shipping_contact_number, ':gift_address_country' => $gift_address_country, ':tax_reg_no' => $tax_reg_no, ':comp_gift_consent' => $comp_gift_consent, ':marketing_optin' => $marketing_optin, ':third_party_optin' => $third_party_optin));
                    $count += $sql->rowCount();
                }
                if( $count > 0 ) {
                    if($access_role==2 || $access_role==3 || $access_role==4) {//2-Free User, 3-Corporate User, 4-Student User
                         if($sub_start_date == "") {
                             $sub_start_date = $date;
                         }
                         if($sub_end_date == "") {
                             $sub_end_date = null;
                         }
                         $sql = $db->prepare("INSERT INTO user_license (brand_id, user_id, start_date, end_date, status) VALUES (:brand_id, :user_id, :start_date, :end_date, :status)");
                         $sql->execute(array(':brand_id' => $brand_id, ':user_id' => $lastinserid, ':start_date' => $sub_start_date, ':end_date' => $sub_end_date, ':status' => 1));
                     }

                     $response->getBody()->write(
                         json_encode(array(
                             "code" => 1,
                             "status" => 1,
                             "message" => "New User created from Admin portal"
                         ))
                     );
                } else {
                    $response->getBody()->write(
                        json_encode(array(
                            "code" => 1,
                            "status" => 3,
                            "message" => "Failed to create new account. Please try again later."
                        ))
                    );
                }
            } else {
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "message" => "User already exists"
                    ))
                );
            }
        } else {
            $response->getBody()->write(
                json_encode(array(
                    "code" => 1,
                    "status" => 4,
                    "message" => "Invalid Brand"
                ))
            );
        }
		return $response->withHeader('Content-Type', 'application/json');
    }
}