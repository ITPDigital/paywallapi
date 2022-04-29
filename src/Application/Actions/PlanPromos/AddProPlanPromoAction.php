<?php

declare(strict_types=1);

namespace App\Application\Actions\PlanPromos;

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


class AddProPlanPromoAction implements RequestHandlerInterface
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
        $this->logger->info('AddProPlanPromoAction: handler dispatched');
        $data = $request->getParsedBody();
        $validateData = (array)$request->getParsedBody();

        // Within the Application Service class: Do the validation
        $validationFactory = new CakeValidationFactory();
        $validator = $validationFactory->createValidator();
		//Form validation
        $validator
            ->notEmptyString('promo_name', 'Field required')
			->requirePresence('promo_name')
            ->notEmptyString('promo_code', 'Field required')
			->requirePresence('promo_code')			
			->requirePresence('promo_desc')
            ->notEmptyString('start_date', 'Field required')
			->requirePresence('start_date')
			->requirePresence('end_date')
            ->notEmptyString('status', 'Field required')
			->requirePresence('status');

        $validationResult = $validationFactory->createValidationResult(
            $validator->validate($validateData)
        );
		//throw exception for validation failure
        if ($validationResult->fails()) {
            throw new ValidationException('Please check your input', $validationResult);
        }
        try {
            $promo_code = isset($data['promo_code']) ? $data["promo_code"] : '';			
            $promo_name = isset($data['promo_name']) ? $data["promo_name"] : '';
            $comp_id = $request->getAttribute('compid');
            $promo_desc = isset($data['promo_desc']) ? $data["promo_desc"] : '';
            $start_date = isset($data['start_date']) ? $data["start_date"] : '';
            $end_date = isset($data['end_date']) ? $data["end_date"] :  '';
            $user_id = $request->getAttribute('userid');
            $status = isset($data['status']) ? $data["status"] :  '';
            $date = date('Y-m-d h:i:s');
            $this->logger->info('AddProPlanPromoAction: comp_id-'.$comp_id.'--promo_name--'.$promo_name);
            $db =  $this->connection;
            $response = new Response();
            if($end_date!='') {
                $sql = $db->prepare("INSERT INTO product_plan_promos (comp_id,promo_code,promo_name,description,start_date,end_date,created_by,created_on,is_active) VALUES (:comp_id,:promo_code,:promo_name,:promo_desc,:start_date,:end_date,:created_by,:created_on,:status)");
                $sql->execute(array(':comp_id' => $comp_id,':promo_code' => $promo_code,':promo_name' => $promo_name,':promo_desc' => $promo_desc, ':start_date' => $start_date, ':end_date' => $end_date, ':created_by' => $user_id, ':created_on' => $date,':status' => $status));
            } else {
                $sql = $db->prepare("INSERT INTO product_plan_promos (comp_id,promo_code,promo_name,description,start_date,created_by,created_on,is_active) VALUES (:comp_id,:promo_code,:promo_name,:promo_desc,:start_date,:created_by,:created_on,:status)");
                $sql->execute(array(':comp_id' => $comp_id,':promo_code' => $promo_code,':promo_name' => $promo_name,':promo_desc' => $promo_desc, ':start_date' => $start_date, ':created_by' => $user_id, ':created_on' => $date,':status' => $status));
            }
            $count = $sql->rowCount();
            $lastinserid = $db->lastInsertId();
            if($count && $lastinserid) {
                $this->logger->info('AddProPlanPromoAction: New product plan promo created successfully for comp_id-'.$comp_id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 1,
                        "message" => "New product plan promo created successfully",
                        "id" => $lastinserid
                    ))
                );
            } else {
                $this->logger->info('AddProPlanPromoAction: Failed to create new product plan promo for comp_id-'.$comp_id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 4,
                        "message" => "Failed to create new product plan promo"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('AddProPlanPromoAction: Error in create new product plan promo for comp_id-'.$comp_id.'--'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in create new product plan promo'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('AddProPlanPromoAction: Error in create new product plan promo for comp_id-'.$comp_id.'--'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in create new product plan promo'
                ))
            );
        }
		return $response->withHeader('Content-Type', 'application/json');
    }
}