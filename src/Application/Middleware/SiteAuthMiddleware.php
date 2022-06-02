<?php
declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

use Slim\Psr7\Factory\ResponseFactory;

class SiteAuthMiddleware implements Middleware
{
    public function process(Request $request, RequestHandler $handler): Response
    {		

	$get_header = $request->getHeaders();
	$checksum = isset($get_header['checksum'][0]) ? $get_header['checksum'][0] : '';
	if($checksum!='') {//user loggedin
		$constChecksum = $get_header['userid'][0] . "|" . $get_header['brandid'][0];
		$constChecksum = hash('sha256', $constChecksum);
		if ($checksum == $constChecksum) {
			$request = $request->withAttribute('isLoggedIn', 'true');
			$request = $request->withAttribute('userId', $get_header['userid'][0]);
		} else {
			$request = $request->withAttribute('isLoggedIn', 'false');
			$request = $request->withAttribute('userId', 0);
		}
	} else {//user not loggedin
		$request = $request->withAttribute('isLoggedIn', 'false');
		$request = $request->withAttribute('userId', 0);
	}
	$response = $handler->handle($request);
    return $response;
		
    }	
	
	

}
