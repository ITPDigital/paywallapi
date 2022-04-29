<?php

declare(strict_types=1);

namespace App\Application\Actions\Brands;

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
use App\Application\Helpers\BrandHelper;


class AddBrandAction implements RequestHandlerInterface
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
        $this->logger->info('AddBrandAction: handler dispatched');
        $data = $request->getParsedBody();
        $validateData = (array)$request->getParsedBody();

        // Within the Application Service class: Do the validation
        $validationFactory = new CakeValidationFactory();
        $validator = $validationFactory->createValidator();
		//Form validation
        $validator
            ->notEmptyString('brand_name', 'Field required')
			->requirePresence('brand_name')
            ->notEmptyString('domain_name', 'Field required')
			->requirePresence('domain_name')
            ->notEmptyString('max_limit', 'Field required')
			->requirePresence('max_limit')
            ->notEmptyString('offered_limit', 'Field required')
			->requirePresence('offered_limit')
            ->notEmptyString('metering_period', 'Field required')
			->requirePresence('metering_period')
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
            $comp_id = $request->getAttribute('compid');
            $brand_name = isset($data['brand_name']) ? $data["brand_name"] : '';
            $domain_name = isset($data['domain_name']) ? $data["domain_name"] : '';
            $max_limit = isset($data['max_limit']) ? $data["max_limit"] :  '';
            $offered_limit = isset($data['offered_limit']) ? $data["offered_limit"] :  '';
            $metering_period = isset($data['metering_period']) ? $data["metering_period"] :  '';
            $user_id = $request->getAttribute('userid');
            $status = isset($data['status']) ? $data["status"] :  '';
            $date = date('Y-m-d h:i:s');
            $this->logger->info('AddBrandAction: brand_name'.$brand_name);
            $db =  $this->connection;
            $brandHelper = new BrandHelper();	
            $isBrandExists = $brandHelper->isBrandExists($db, $brand_name, $comp_id,0);
            $response = new Response();
            if($isBrandExists>0) {
                $this->logger->info('AddBrandAction: Brand name already exists'.$brand_name);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "message" => "Brand name already exists"
                    ))
                );
                return $response->withHeader('Content-Type', 'application/json');
            }
            $isDomainExists = $brandHelper->isDomainExists($db, $domain_name, $comp_id,0);
            if($isDomainExists>0) {
                $this->logger->info('AddBrandAction: Domain name already exists'.$domain_name);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 3,
                        "message" => "Domain name already exists"
                    ))
                );
                return $response->withHeader('Content-Type', 'application/json');
            }
            if($isBrandExists==0 && $isDomainExists==0) {
                $sql = $db->prepare("INSERT INTO brands (comp_id, brand_name, domain_name, max_limit, offered_limit, metering_period, created_by, created_on, is_active) VALUES (:comp_id, :brand_name, :domain_name, :max_limit, :offered_limit, :metering_period, :user_id, :created_on, :status)");
                $sql->execute(array(':comp_id' => $comp_id, ':brand_name' => $brand_name, ':domain_name' => $domain_name, ':max_limit' => $max_limit, ':offered_limit' => $offered_limit, ':metering_period' => $metering_period, ':user_id' => $user_id, ':created_on' => $date,':status' => $status));
                $count = $sql->rowCount();
                $lastinserid = $db->lastInsertId();
                if($count && $lastinserid) {
                    $this->logger->info('AddBrandAction: New brand created successfully'.$brand_name);
                    $response->getBody()->write(
                        json_encode(array(
                            "code" => 1,
                            "status" => 1,
                            "message" => "New brand created successfully",
                            "id" => (int)$lastinserid
                        ))
                    );
                } else {
                    $this->logger->info('AddBrandAction: Failed to create new brand'.$brand_name);
                    $response->getBody()->write(
                        json_encode(array(
                            "code" => 1,
                            "status" => 4,
                            "message" => "Failed to create new brand"
                        ))
                    );
                }
            } else {
                $this->logger->info('AddBrandAction: Failed to create new brand'.$brand_name);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 0,
                        "status" => 1,
                        "message" => "Failed to create new brand"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('AddBrandAction: Error in creating new brand--'.$brand_name.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in creating new brand'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('AddBrandAction: Error in creating new brand---'.$brand_name.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in creating new brand'
                ))
            );
        }
		return $response->withHeader('Content-Type', 'application/json');
    }
}