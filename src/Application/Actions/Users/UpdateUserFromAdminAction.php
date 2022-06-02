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
use App\Application\Helpers\CommonHelper;

class UpdateUserFromAdminAction implements RequestHandlerInterface
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
        $this->logger->info('Home page handler dispatched');
		$commonHelper = new CommonHelper();
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
            ->notEmptyString('industry', 'Field required')
			->requirePresence('industry')
            ->notEmptyString('job_title', 'Field required')
			->requirePresence('job_title')
            ->notEmptyString('country', 'Field required')
			->requirePresence('country')
            ->notEmptyString('company_size', 'Field required')
			->requirePresence('company_size')
			->notEmptyString('comp_name', 'Field required')
			->requirePresence('comp_name')
            ->notEmptyString('access_role', 'Field required')
			->requirePresence('access_role');

        $validationResult = $validationFactory->createValidationResult(
            $validator->validate($validateData)
        );
		//throw exception for validation failure
        if ($validationResult->fails()) {
            throw new ValidationException('Please check your input', $validationResult);
        }	
		$db =  $this->connection;		
		$userHelper = new UserHelper();	
        $first_name = isset($data['first_name']) ? $data["first_name"] : '';
        $last_name = isset($data['last_name']) ? $data["last_name"] : '';
        $email = isset($data['email']) ? $data["email"] : '';
		$industry = isset($data['industry']) ? $data["industry"] :  '';
		$job_title = isset($data['job_title']) ? $data["job_title"] :  '';
		$country = isset($data['country']) ? $data["country"] :  '';
		$company_size = isset($data['company_size']) ? $data["company_size"] :  '';
		$comp_name = isset($data['comp_name']) ? $data["comp_name"] :  '';
		$brand_id = $commonHelper->resolveArg($request,'brandId');
		$gender = isset($data['gender']) ? $data["gender"] :  '';
		$dob = isset($data['dob']) ? $data["dob"] :  '';
		$phone = isset($data['phone']) ? $data["phone"] :  '';
		$user_id = $commonHelper->resolveArg($request,'id');
		$marketing_optin = isset($data['marketing_optin']) ? $data['marketing_optin'] : '';
		$third_party_optin = isset($data['third_party_optin']) ? $data['third_party_optin'] : '';
        $access_role = isset($data['access_role']) ? $data['access_role'] : '';
        $gift_address1 = isset($data['gift_address1']) ? $data['gift_address1'] : '';
        $gift_address2 = isset($data['gift_address2']) ? $data['gift_address2'] : '';
        $gift_address_city = isset($data['gift_address_city']) ? $data['gift_address_city'] : '';
        $gift_address_state = isset($data['gift_address_state']) ? $data['gift_address_state'] : '';
        $shipping_contact_number = isset($data['shipping_contact_number']) ? $data['shipping_contact_number'] : '';
        $gift_address_country = isset($data['gift_address_country']) ? $data['gift_address_country'] : '';
        $tax_reg_no = isset($data['tax_reg_no']) ? $data['tax_reg_no'] : '';
        $comp_gift_consent = isset($data['comp_gift_consent']) ? $data['comp_gift_consent'] : '';
        $status = isset($data['status']) ? $data['status'] : '';
        $sub_start_date = isset($data['sub_start_date']) ? $data['sub_start_date'] : '';
        $sub_end_date = isset($data['sub_end_date']) ? $data['sub_end_date'] : '';
        $sub_id = isset($data['sub_id']) ? $data['sub_id'] : '';
        $sub_pro_id = isset($data['sub_pro_id']) ? $data['sub_pro_id'] : '';
        $date = date('Y-m-d h:i:s');
        $updated_by = $request->getAttribute('userid');

       // $is_subscribed_user =  ($access_role == 2 || $access_role == 3 || $access_role == 4) ?  1: 0;
        $updateUserDetails = true;
        if($email) {
            $isUserExists = $userHelper->isUserNameExists($db, $email, $brand_id);
            if($isUserExists == 0) {
                $sql = $db->prepare("UPDATE users SET first_name = :first_name, last_name = :last_name, email = :email, industry = :industry, job_ttl = :job_ttl, comp_size = :comp_size, comp = :comp, status = :status, access_role = :access_role, updated_on = :date, updated_by = :updated_by 
                WHERE id = :user_id AND brand_id = :brand_id");
                $sql->execute(array(':first_name' => $first_name, ':last_name' => $last_name, ':email' => $email, ':industry' => $industry, ':job_ttl' => $job_title, ':comp_size' => $company_size, ':comp' => $comp_name, ':status' => $status, ':access_role' => $access_role, ':date' => $date, ':updated_by' => $updated_by, ':user_id' => $user_id, ':brand_id' => $brand_id));
                $count = $sql->rowCount();
            } else {
                $updateUserDetails = false;
                $response->getBody()->write(
					json_encode(array(
						"code" => 1,
						"status" => 3,
						"message" => "User email already exists."
					))
				);
            }
        } else {
            $sql = $db->prepare("UPDATE users SET first_name = :first_name, last_name = :last_name, industry = :industry, job_ttl = :job_ttl, comp_size = :comp_size, comp = :comp, status = :status, access_role = :access_role, updated_on = :date, updated_by = :updated_by 
            WHERE id = :user_id AND brand_id = :brand_id");
            $sql->execute(array(':first_name' => $first_name, ':last_name' => $last_name, ':industry' => $industry, ':job_ttl' => $job_title, ':comp_size' => $company_size, ':comp' => $comp_name, ':status' => $status, ':access_role' => $access_role, ':date' => $date, ':updated_by' => $updated_by, ':user_id' => $user_id, ':brand_id' => $brand_id));
            $count = $sql->rowCount();
        }
        if($updateUserDetails) {
            $user_dt_sql = $db->prepare("UPDATE user_details SET country = :country, dob = :dob, gender = :gender, phone = :phone, marketing_optin = :marketing_optin, third_party_optin = :third_party_optin, tax_reg_no = :tax_reg_no, gift_address1 = :gift_address1, gift_address2 = :gift_address2, gift_address_city = :gift_address_city, gift_address_state = :gift_address_state, shipping_contact_number = :shipping_contact_number, gift_address_country = :gift_address_country, comp_gift_consent = :comp_gift_consent WHERE user_id = :user_id AND brand_id = :brand_id");
            $user_dt_sql->execute(array(':country' => $country, ':dob' => $dob, ':gender' => $gender, ':phone' => $phone, ':marketing_optin' => $marketing_optin, ':third_party_optin' => $third_party_optin, ':tax_reg_no' => $tax_reg_no, ':gift_address1' => $gift_address1, ':gift_address2' => $gift_address2, ':gift_address_city' => $gift_address_city, ':gift_address_state' => $gift_address_state, ':shipping_contact_number' => $shipping_contact_number, ':gift_address_country' => $gift_address_country, ':comp_gift_consent' => $comp_gift_consent, ':user_id' => $user_id, ':brand_id' => $brand_id));
            $count += $user_dt_sql->rowCount();
        }
		$response = new Response();
		//if( $count > 0 ) {
            if($access_role ==2 || $access_role ==3 || $access_role ==4) {
               /* $sql = $db->prepare("UPDATE users SET is_subscribed_user = :is_subscribed_user, updated_on = :date, updated_by = :updated_by 
                WHERE id = :user_id AND brand_id = :brand_id and access_role = :access_role");
                $sql->execute(array(':is_subscribed_user' => 1,':access_role' => $access_role, ':date' => $date, ':updated_by' => $updated_by, ':user_id' => $user_id, ':brand_id' => $brand_id));
                */
                if($sub_start_date == "") {
                    $sub_start_date = $date;
                }
                if($sub_end_date == "") {
                    $sub_end_date = null;
                }
                //echo  $sub_end_date;exit;
                if($sub_id!='') {
                    $sql = $db->prepare("UPDATE user_license SET start_date = :start_date, end_date = :end_date WHERE id = :id AND user_id = :user_id AND brand_id = :brand_id");
                    $sql->execute(array(':start_date' => $sub_start_date,':end_date' => $sub_end_date, ':id' => $sub_id, ':user_id' => $user_id, ':brand_id' => $brand_id));
                } else {
                    $sql = $db->prepare("INSERT INTO user_license (brand_id, user_id, start_date, end_date, status) VALUES (:brand_id, :user_id, :start_date, :end_date, :status)");
                    $sql->execute(array(':brand_id' => $brand_id, ':user_id' => $user_id, ':start_date' => $sub_start_date, ':end_date' => $sub_end_date, ':status' => 1)); 
                }
                $count += $sql->rowCount();
            } else if($access_role ==1 && $sub_pro_id=='' && $sub_id!='') {
                $sql = $db->prepare("DELETE FROM user_license WHERE id = :id AND user_id = :user_id AND brand_id = :brand_id");
                $sql->execute(array(':id' => $sub_id, ':user_id' => $user_id, ':brand_id' => $brand_id));
            }
            if( $count > 0 ) {
                $response->getBody()->write(
					json_encode(array(
						"code" => 1,
						"status" => 1,
						"message" => "User profile update successfully"
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
		return $response->withHeader('Content-Type', 'application/json');
    }
}