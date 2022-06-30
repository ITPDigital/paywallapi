<?php

declare(strict_types=1);

namespace App\Application\Actions\EmailTemplates;

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

class ViewEmailAction implements RequestHandlerInterface
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
        $this->logger->info('ViewEmailAction: handler dispatched');
        $data = $request->getParsedBody();
        
        try {
            $id = $commonHelper->resolveArg($request,'id');
            $comp_id = $request->getAttribute('compid');
            $this->logger->info('ViewEmailAction: template id'.$id);
            $db =  $this->connection;
            $sql = $db->prepare('SELECT * from email_templates where id=:id and comp_id=:comp_id');
            $sql->execute(array(':id' => $id,':comp_id' => $comp_id));
            $data = $sql->fetchAll(PDO::FETCH_OBJ);
            $response = new Response();
            if(count($data)>0){
                $this->logger->info('ViewEmailAction: Template found"'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 1,
                        "message" => "Template found",
                        "result" => $data
                    ))
                );
            } else {
                $this->logger->info('ViewEmailAction: Template not found"'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "message" => "Template not found"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('ViewEmailAction: Error in getting Template detail---'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in getting Template detail'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('ViewEmailAction: Error in getting Template detail---'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in getting Template detail'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

   
}