<?php
declare(strict_types=1);

use App\Application\Middleware\SessionMiddleware;
use App\Application\Middleware\UserMiddleware;
use Slim\App;




return function (App $app) {
    //$app->add(SessionMiddleware::class);
    //$app->add(UserMiddleware::class);
    /*$app->add(new \Tuupola\Middleware\JwtAuthentication([
        "path" => ["/db-test/all","/viewUser","/deleteUser"],
        "attribute" => "decoded_token_data",
        "secret" => "TOURSECERTKEY",
        "algorithm" => ["HS256"],
        "error" => function ($response, $arguments) {
            $data["status"] = "error";
            $data["message"] = $arguments["message"];
            return $response
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        }
    ]));*/	
};
