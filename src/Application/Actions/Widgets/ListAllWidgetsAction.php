<?php

declare(strict_types=1);

namespace App\Application\Actions\Widgets;

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


class ListAllWidgetsAction implements RequestHandlerInterface
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
        $this->logger->info('ListAllWidgetsAction: handler dispatched');
        $data = $request->getParsedBody();
        try {
            $comp_id = $request->getAttribute('compid');
            $this->logger->info('ListAllWidgetsAction: Comp id'.$comp_id);
            $db =  $this->connection;
            //echo $status;exit;
            $sql = $db->prepare('SELECT mt.id, mt.brand_id, mt.type, mt.widget_group_id, mt.custom_count, mt.is_logged_in, mt.metering_action_id, mt.name, mt.is_active, mt.created_on, mt.updated_on, b.brand_name, mac.name as action_name from map_metering_type mt LEFT JOIN brands b ON mt.brand_id=b.id LEFT JOIN const_metering_action_type mac ON mac.id=mt.metering_action_id where mt.comp_id=:comp_id AND b.is_active=:status;');
            $sql->execute(array(':comp_id' => $comp_id, ':status' => 1));
            $data = $sql->fetchAll(PDO::FETCH_OBJ);
            $response = new Response();
            $this->logger->info('ListAllWidgetsAction: Number of widget groups available for company id-'.$comp_id.'-is-'.count($data));
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
            $this->logger->info('ListAllWidgetsAction: Error in fetching all widget groups---'.$comp_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in fetching all widget groups'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('ListAllWidgetsAction: Error in fetching all widget groups---'.$comp_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in fetching all widget groups'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

   
}