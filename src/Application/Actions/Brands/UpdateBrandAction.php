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
use App\Application\Helpers\CommonHelper;

class UpdateBrandAction implements RequestHandlerInterface
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
        $this->logger->info('UpdateBrandAction: handler dispatched');
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
            $id = $commonHelper->resolveArg($request,'id');
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
            $response = new Response();
            $brandHelper = new BrandHelper();	
            $isBrandExists = $brandHelper->isBrandExists($db, $brand_name, $comp_id,$id);
            if($isBrandExists>0) {
                $this->logger->info('UpdateBrandAction: Brand name already exists-'.$brand_name.'-id-'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "message" => "Brand name already exists"
                    ))
                );
                return $response->withHeader('Content-Type', 'application/json');
            }
            $isDomainExists = $brandHelper->isDomainExists($db, $domain_name, $comp_id,$id);
            if($isDomainExists>0) {
                $this->logger->info('UpdateBrandAction: Domain name already exists'.$domain_name.'-id-'.$id);
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
                $sql = $db->prepare("UPDATE brands set brand_name = :brand_name,domain_name=:domain_name,max_limit=:max_limit,offered_limit=:offered_limit,metering_period=:metering_period,updated_by=:updated_by,updated_on=:updated_on,is_active=:status where id = :id and comp_id=:comp_id");		
                $sql->execute(array(':brand_name'=>$brand_name,':domain_name' => $domain_name,':max_limit' => $max_limit, ':offered_limit' => $offered_limit, ':metering_period' => $metering_period,':updated_by' => $user_id,':updated_on' => $date,':status' => $status,':id' => $id, ':comp_id' => $comp_id));
                
				$count = $sql->rowCount();
                if($count) {
                    $this->logger->info('UpdateBrandAction: Brand data updated successfully'.$id);
                    $response->getBody()->write(
                        json_encode(array(
                            "code" => 1,
                            "status" => 1,
                            "message" => "Brand data updated successfully",
                            "id" => (int)$id
                        ))
                    );
                } else {
                    $this->logger->info('UpdateBrandAction: Failed to update brand'.$id);
                    $response->getBody()->write(
                        json_encode(array(
                            "code" => 1,
                            "status" => 4,
                            "message" => "Failed to update brand"
                        ))
                    );
                }
            } else {
                $this->logger->info('UpdateBrandAction: Failed to update brand'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 0,
                        "status" => 1,
                        "message" => "Failed to update brand"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('UpdateBrandAction: Error in updating brand detail---'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in updating brand detail'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('UpdateBrandAction: Error in updating brand detail---'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in updating brand detail'
                ))
            );
        }
		return $response->withHeader('Content-Type', 'application/json');
    }
}