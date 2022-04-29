<?php

declare(strict_types=1);

namespace App\Application\Actions\Constants;

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

class ListAllCurrenciesByStatusAction implements RequestHandlerInterface
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
        $this->logger->info('ListAllCurrenciesByStatusAction: handler dispatched');
        $data = $request->getParsedBody();
        $commonHelper = new CommonHelper();
        try {
            $comp_id = $request->getAttribute('compid');
            $status = $commonHelper->resolveArg($request,'status');
            $this->logger->info('ListAllCurrenciesByStatusAction: Comp id'.$comp_id);
            $db =  $this->connection;
            $sql = $db->prepare('SELECT id,disp_id,description from const_currencies where comp_id=:comp_id and is_active=:status');
            $sql->execute(array(':comp_id' => $comp_id, ':status' => $status));
            $data = $sql->fetchAll(PDO::FETCH_OBJ);
            $response = new Response();
            $this->logger->info('ListAllCurrenciesByStatusAction: Number of currencies available for company id-'.$comp_id.'-is-'.count($data));
            $response->getBody()->write(
                json_encode(array(
                    "code" => 1,
                    "status" => 1,
                    "message" => "Data found",
                    "result" => $data
                ))
            );
        }
        catch(MySQLException $e) {
            $this->logger->info('ListAllCurrenciesByStatusAction: Error in fetching all brands---'.$comp_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in fetching all brands'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('ListAllCurrenciesByStatusAction: Error in fetching all brands---'.$comp_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in fetching all brands'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

   
}