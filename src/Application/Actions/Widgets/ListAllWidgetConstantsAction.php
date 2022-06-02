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


class ListAllWidgetConstantsAction implements RequestHandlerInterface
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
        $this->logger->info('ListAllWidgetConstantsAction: handler dispatched');
        $data = $request->getParsedBody();
        try {
            $comp_id = $request->getAttribute('compid');
            $this->logger->info('ListAllWidgetConstantsAction: Comp id'.$comp_id);
            $db =  $this->connection;
            //echo $status;exit;
            $mt_action_sql = $db->prepare('SELECT * from const_metering_action_type where comp_id=:comp_id;');
            $mt_action_sql->execute(array(':comp_id' => $comp_id));
            $mt_action_data = $mt_action_sql->fetchAll(PDO::FETCH_OBJ);

            $mt_group_sql = $db->prepare('SELECT * from config_widget_group where comp_id=:comp_id and is_active=:status;');
            $mt_group_sql->execute(array(':comp_id' => $comp_id, ':status' => 1));
            $mt_group_data = $mt_group_sql->fetchAll(PDO::FETCH_OBJ);

            $mt_cttype_sql = $db->prepare('SELECT * from config_content_type where comp_id=:comp_id and is_active=:status;');
            $mt_cttype_sql->execute(array(':comp_id' => $comp_id, ':status' => 1));
            $mt_cttype_data = $mt_cttype_sql->fetchAll(PDO::FETCH_OBJ);

            $mt_ctcat_sql = $db->prepare('SELECT * from config_content_category where comp_id=:comp_id and is_active=:status;');
            $mt_ctcat_sql->execute(array(':comp_id' => $comp_id, ':status' => 1));
            $mt_ctcat_data = $mt_ctcat_sql->fetchAll(PDO::FETCH_OBJ);

            $response = new Response();
            $this->logger->info('ListAllWidgetConstantsAction: All the widget constants are fetched successfully for comp_id---------'.$comp_id);
            $data = array('mtActionObj' => $mt_action_data, 
                'mtGroupObj' => $mt_group_data,
                'mtContTypeObj' => $mt_cttype_data,
                'mtContCatObj' => $mt_ctcat_data
            );
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
            $this->logger->info('ListAllWidgetConstantsAction: Error in fetching widget constants for comp_id---'.$comp_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in fetching widget constants'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('ListAllWidgetConstantsAction: Error in fetching widget constants for comp_id---'.$comp_id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in fetching widget constants'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

   
}