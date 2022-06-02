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
use App\Application\Helpers\CommonHelper;

class UpdateProPlanPromoAction implements RequestHandlerInterface
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
        $this->logger->info('UpdateProPlanPromoAction: handler dispatched');
        $commonHelper = new CommonHelper();
        $data = $request->getParsedBody();
        $validateData = (array)$request->getParsedBody();

        // Within the Application Service class: Do the validation
        $validationFactory = new CakeValidationFactory();
        $validator = $validationFactory->createValidator();
		//Form validation
        $validator
			->notEmptyString('promo_code', 'Field required')
			->requirePresence('promo_code')
            ->notEmptyString('promo_name', 'Field required')
			->requirePresence('promo_name')
			->requirePresence('promo_desc')
            ->notEmptyString('start_date', 'Field required')
			->requirePresence('start_date')
            ->notEmptyString('end_date', 'Field required')
			->requirePresence('end_date');

        $validationResult = $validationFactory->createValidationResult(
            $validator->validate($validateData)
        );
		//throw exception for validation failure
        if ($validationResult->fails()) {
            throw new ValidationException('Please check your input', $validationResult);
        }
        try {
            $id = $commonHelper->resolveArg($request,'id');
            $promo_name = isset($data['promo_name']) ? $data["promo_name"] : '';
			$promo_code = isset($data['promo_code']) ? $data["promo_code"] : '';
            $comp_id = $request->getAttribute('compid');
            $description = isset($data['promo_desc']) ? $data["promo_desc"] : '';
            $start_date = isset($data['start_date']) ? $data["start_date"] : '';
            $end_date = isset($data['end_date']) ? $data["end_date"] :  '';
            $user_id = $request->getAttribute('userid');
            $date = date('Y-m-d h:i:s');

            $this->logger->info('UpdateProPlanPromoAction: Product plan feature id'.$id.'--comp_id----'.$comp_id);
            $db =  $this->connection;
            $response = new Response();
            $sql = $db->prepare("UPDATE product_plan_promos set promo_code = :promo_code,promo_name = :promo_name,description = :description,updated_by=:updated_by,updated_on=:updated_on,start_date=:start_date,end_date=:end_date,updated_on=:updated_on where id = :id and comp_id = :comp_id");		
            $sql->execute(array(':promo_code'=>$promo_code,':promo_name'=>$promo_name,':description'=>$description,':updated_by' => $user_id, ':updated_on' => $date,':start_date' => $start_date,':end_date' => $end_date,':id' => $id,':comp_id' => $comp_id));
            $count = $sql->rowCount();
            if($count) {
                $this->logger->info('UpdateProPlanPromoAction: Product plan promo detail updated successfully'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 1,
                        "message" => "Product plan promo detail updated successfully",
                        "id" => $id
                    ))
                );
            } else {
                $this->logger->info('UpdateProPlanPromoAction: Failed to update product plan promo'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "message" => "Failed to update product plan promo detail"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('UpdateProPlanPromoAction: Error in update product plan promo---'.$id.'-----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in update product plan promo'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('UpdateProPlanPromoAction: Error in update product plan promo---'.$id.'-----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in update product plan promo'
                ))
            );
        }
		return $response->withHeader('Content-Type', 'application/json');
    }
}