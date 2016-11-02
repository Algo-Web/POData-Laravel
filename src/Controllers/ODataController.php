<?php

namespace AlgoWeb\PODataLaravel\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use AlgoWeb\PODataLaravel\Controllers\Controller as BaseController;
use Illuminate\Support\Facades\App;
use POData\OperationContext\ServiceHost as ServiceHost;
use POData\SimpleDataService as DataService;
use POData\OperationContext\Web\Illuminate\IlluminateOperationContext as OperationContextAdapter;

class ODataController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $op = new OperationContextAdapter($request);
        $host = new ServiceHost($op, $request);
        $host->setServiceUri("/odata.svc/");

        $query = App::make('odataquery');
        $meta = App::make('metadata');

        $service = new DataService($query, $meta);
        $service->setHost($host);
        $service->handleRequest();

        $odataResponse = $op->outgoingResponse();
        $content = $odataResponse->getStream();
        $response = new Response($content, 200);

        foreach ($odataResponse->getHeaders() as $headerName => $headerValue) {
            if (!is_null($headerValue)) {
                $response->headers->set($headerName, $headerValue);
            }
        }
        return $response;
    }
}
