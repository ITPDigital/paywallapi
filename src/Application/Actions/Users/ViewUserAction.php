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


class ViewUserAction implements RequestHandlerInterface
{
    private $logger;
    private $connection;
    private array $args;

    public function __construct(PDO $connection,LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function isValidUserSession($userSession) {
        if($userSession >= strtotime('now')) {
            return true;
        } else {
            return false;
        }
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->logger->info('ViewUserAction: handler dispatched');
        $commonHelper = new CommonHelper();
        $data = $request->getParsedBody();
        try {
            $id = $commonHelper->resolveArg($request,'id');
            $brand = $request->getAttribute('brandid');//customer portal
            if(!$brand) {
                $brand = $commonHelper->resolveArg($request,'brandId');//admin portal
            }
            $this->logger->info('ViewUserAction: user id'.$id);
            $db =  $this->connection;
            $sql = $db->prepare('SELECT * from users u LEFT JOIN user_details ud on ud.user_id=u.id WHERE u.id=:id AND u.brand_id=:brand;');
            $sql->execute(array(':id' => $id,':brand' => $brand));
            $data = $sql->fetchAll(PDO::FETCH_OBJ);
            $response = new Response();
            if(count($data)>0){
                /*$userSession = $data[0]->session;
                if(!$this->isValidUserSession($userSession)) {
                    $this->logger->info('ViewUserAction: session expired and new user session created'.$id);
                    $sql = $db->prepare("UPDATE users set session = :session where id = :id");		
                    $sql->execute(array(':session' => strtotime('+30 days'),':id' => $id));
                }*/
				$resData = array('id' => (int)$data[0]->id, 
				'brand' => (int)$data[0]->brand_id, 
				'first_name' => $data[0]->first_name, 
				'last_name' => $data[0]->last_name, 
				'email' => $data[0]->email, 
				'industry' => $data[0]->industry, 
				'job_ttl' => $data[0]->job_ttl, 
				'comp' => $data[0]->comp, 
				'comp_size' => $data[0]->comp_size, 
				'access_role' => (int)$data[0]->access_role, 
				'registered_on' => $data[0]->registered_on, 
				'last_logged_on' => $data[0]->last_logged_on, 
				'status' => (int)$data[0]->status, 
				'is_subscribed_user' => (int)$data[0]->is_subscribed_user, 
				'country' => $data[0]->country, 
				'dob' => $data[0]->dob, 
				'gender' => $data[0]->gender, 
				'phone' => $data[0]->phone, 
				'gift_address1' => $data[0]->gift_address1, 
				'gift_address2' => $data[0]->gift_address2, 
				'gift_address_state' => $data[0]->gift_address_state, 
				'gift_address_city' => $data[0]->gift_address_city,
				'shipping_contact_number' => $data[0]->shipping_contact_number, 
				'gift_address_country' => $data[0]->gift_address_country, 
				'tax_reg_no' => $data[0]->tax_reg_no, 
				'marketing_optin' => $data[0]->marketing_optin,
				'third_party_optin' => $data[0]->third_party_optin,
				'comp_gift_consent' => $data[0]->comp_gift_consent);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 1,
                        "message" => "User found",
                        "result" => $resData
                    ))
                );
            } else {
                $this->logger->info('ViewUserAction: User not found'.$id);
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
            $this->logger->info('ViewUserAction: SQL error in getting user detail for user id-----'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'SQL error in getting user detail'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('ViewUserAction: Error in getting user detail for user id-----'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in getting user detail'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

   
}