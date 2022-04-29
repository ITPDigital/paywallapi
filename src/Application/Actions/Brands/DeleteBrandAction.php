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
use App\Application\Helpers\CommonHelper;

class DeleteBrandAction implements RequestHandlerInterface
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
        $this->logger->info('DeleteBrandAction: handler dispatched');
        $commonHelper = new CommonHelper();
        $data = $request->getParsedBody();
        try {
            $id = $commonHelper->resolveArg($request,'id');
            $comp_id = $request->getAttribute('compid');
            $status = $commonHelper->resolveArg($request,'status');
            $user_id = $request->getAttribute('userid');
            $date = date('Y-m-d h:i:s');
            $this->logger->info('DeleteBrandAction: brand id'.$id);
            $db =  $this->connection;
            $sql = $db->prepare('UPDATE brands set is_active = :status,updated_by =:user_id, updated_on=:updated_on  where id = :id and comp_id = :comp_id');
            $sql->execute(array(':status' => $status,':user_id' => $user_id,'updated_on'=> $date,':id' => $id,':comp_id' => $comp_id));
            $count = $sql->rowCount();
            $response = new Response();
            if($count > 0){
                $this->logger->info('DeleteBrandAction: Brand status updated successfully'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 1,
                        "message" => "Brand status updated successfully"
                    ))
                );
            } else {
                $this->logger->info('DeleteBrandAction: Brand not found'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 0,
                        "status" => 1,
                        "message" => "Brand not found"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('DeleteBrandAction: Error in updating brand status---'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in updating brand status'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('DeleteBrandAction: Error in updating brand status---'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in updating brand status'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }
}