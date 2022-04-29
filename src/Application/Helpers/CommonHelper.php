<?php

declare(strict_types=1);

namespace App\Application\Helpers;
use Slim\Routing\RouteContext;

class CommonHelper {
    public function resolveArg($request,$name)
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $data = $route->getArgument($name);
        return $data;
    }

    public function isValidRequest($db, $request){
        $checksum = $request->getHeader('checksum');
        $user_id = $request->getHeader('userId');
        $comp_id = $request->getHeader('compId');
		$brand_id = $request->getHeader('brandId');
        $newChecksum = $user_id."|".$comp_id."|".$brand_id;
        $newChecksum = hash('sha256', $newChecksum);
        if(hash_equals($checksum,$newChecksum)) {
            return true;
        } else {
            return false;
        }
    }


}