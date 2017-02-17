<?php

namespace AlgoWeb\PODataLaravel\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use AlgoWeb\PODataLaravel\Controllers\Controller as BaseController;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
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
    public function index(Request $request, $dump = false)
    {
        $op = new OperationContextAdapter($request);
        $host = new ServiceHost($op, $request);
        $host->setServiceUri("/odata.svc/");

        $query = App::make('odataquery');
        $meta = App::make('metadata');

        $service = new DataService($query, $meta, $host);
        $service->handleRequest();

        $odataResponse = $op->outgoingResponse();

        if (true === $dump) {
            // iff XTest header is set, containing class and method name
            // dump outgoing odataResponse, metadata, and incoming request
            $xTest = $request->header('XTest', null);
            if (null != $xTest) {
                $cerealRequest = serialize($request);
                $cerealMeta = serialize($meta);
                $cerealResponse = serialize($odataResponse);
                Storage::put($xTest.'request', $cerealRequest);
                Storage::put($xTest.'metadata', $cerealMeta);
                Storage::put($xTest.'response', $cerealResponse);
            }
        }

        $content = $odataResponse->getStream();
        $headers = $odataResponse->getHeaders();
        $responseCode = $headers[\POData\Common\ODataConstants::HTTPRESPONSE_HEADER_STATUS_CODE];
        $responseCode = isset($responseCode) ? $responseCode : 200;
        $response = new Response($content, $responseCode);
        $response->setStatusCode($headers["Status"]);

        foreach ($headers as $headerName => $headerValue) {
            if (!is_null($headerValue)) {
                $response->headers->set($headerName, $headerValue);
            }
        }
        return $response;
    }
}
