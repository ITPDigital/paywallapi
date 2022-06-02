<?php
declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

use Slim\Psr7\Factory\ResponseFactory;

class AdminRoleMiddleware implements Middleware
{
    public function process(Request $request, RequestHandler $handler): Response
    {		

	$get_header = $request->getHeaders();
	$role = 0;
	if($get_header['type'][0] ==1) {//consumer portal
		$request = $request->withAttribute('brandid', $get_header['brandid'][0]);
		$request = $request->withAttribute('userid', $get_header['userid'][0]);
		$checksum = $get_header['userid'][0] . "|" . $get_header['brandid'][0];	 
	} else {//admin portal
		$request = $request->withAttribute('compid', $get_header['compid'][0]);
		$role = $get_header['role'][0];
		$request = $request->withAttribute('role', $role);
		$request = $request->withAttribute('userid', $get_header['userid'][0]);
		$checksum = $get_header['userid'][0] . "|" . $get_header['role'][0] ."|" . $get_header['compid'][0];
	}

	  $checksum = hash('sha256', $checksum);
	  
	  //print $get_header['checksum'][0] . '****' . $checksum;exit;
	  
	  if ($get_header['checksum'][0] != $checksum || $role != 1) {		
	    $responseFactory = new ResponseFactory();
        $response = $responseFactory->createResponse();	
        $response->getBody()->write(
		  json_encode(array(
			"code" => 0,
			"status" => 0,
			"message" => 'Invalid Session'
		  ))
        );	
      return $response->withStatus(403);
	} else {//handle request only if checksum valid
		$response = $handler->handle($request);
	}
    return $response;
		
    }	
	
	

}
