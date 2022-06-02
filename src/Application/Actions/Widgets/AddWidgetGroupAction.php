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


class AddWidgetGroupAction implements RequestHandlerInterface
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
        $this->logger->info('AddWidgetGroupAction: handler dispatched');
        $data = $request->getParsedBody();
        $validateData = (array)$request->getParsedBody();

        try {
            $comp_id = $request->getAttribute('compid');
            $user_id = $request->getAttribute('userid');
            $group_name = isset($data['group_name']) ? $data["group_name"] : '';
            $date = date('Y-m-d h:i:s');
            $this->logger->info('AddWidgetGroupAction: group_name'.$group_name);
            $db =  $this->connection;
            $response = new Response();
            $sql = $db->prepare("INSERT INTO config_widget_group (comp_id, group_name, created_by, created_on, is_active) VALUES (:comp_id, :group_name, :created_by, :created_on, :status)");
            $sql->execute(array(':comp_id' => $comp_id, ':group_name' => $group_name, ':created_by' => $user_id, ':created_on' => $date, ':status' => 1));
            $count = $sql->rowCount();
            $lastinserid = $db->lastInsertId();
            if($count && $lastinserid) {
                $this->logger->info('AddWidgetGroupAction: New group name created successfully'.$group_name);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 1,
                        "message" => "New group name created successfully",
                        "id" => (int)$lastinserid
                    ))
                );
            } else {
                $this->logger->info('AddWidgetGroupAction: Failed to create new group name'.$group_name);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 4,
                        "message" => "Failed to create new group name"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('AddWidgetGroupAction: Error in creating new group name for comp id--'.$comp_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in creating new group name'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('AddWidgetGroupAction: Error in creating new group name for comp id---'.$comp_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in creating new group name'
                ))
            );
        }
		return $response->withHeader('Content-Type', 'application/json');
    }
}