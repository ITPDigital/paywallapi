<?php

declare(strict_types=1);

namespace App\Application\Actions\Admin\Users;

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

class ViewAdminUserAction implements RequestHandlerInterface
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
        $this->logger->info('ViewAdminUserAction: handler dispatched');
        $data = $request->getParsedBody();
        
        try {
            $id = $commonHelper->resolveArg($request,'id');
            $comp_id = $request->getAttribute('compid');
            $this->logger->info('ViewAdminUserAction: brand id'.$id);
            $db =  $this->connection;
            $sql = $db->prepare('SELECT id,first_name,last_name,email,role,is_active from company_admin where id=:id and comp_id=:comp_id');
            $sql->execute(array(':id' => $id,':comp_id' => $comp_id));
            $data = $sql->fetchAll(PDO::FETCH_OBJ);
            $response = new Response();
            if(count($data)>0){
                $this->logger->info('ViewAdminUserAction: Admin User found"'.$id);   
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 1,
                        "message" => "Admin User found",
                        "result" => $data
                    ))
                );
            } else {
                $this->logger->info('ViewAdminUserAction: Admin User not found"'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "message" => "Admin User not found"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('ViewAdminUserAction: Error in getting admin user detail---'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in getting admin user detail'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('ViewAdminUserAction: Error in getting admin user detail---'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in getting admin user detail'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

   
}