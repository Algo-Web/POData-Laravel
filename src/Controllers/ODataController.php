<?php

namespace AlgoWeb\PODataLaravel\Controllers;

use AlgoWeb\PODataLaravel\Serialisers\IronicSerialiser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use AlgoWeb\PODataLaravel\Controllers\Controller as BaseController;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use POData\OperationContext\ServiceHost as ServiceHost;
use POData\SimpleDataService as DataService;
use POData\OperationContext\Web\Illuminate\IlluminateOperationContext as OperationContextAdapter;
use voku\helper\AntiXSS;

class ODataController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $dump = $this->getIsDumping();
        $dryRun = $this->getIsDryRun();
        $commitCall = $dryRun ? 'rollBack' : 'commit';

        try {
            DB::beginTransaction();
            $context = new OperationContextAdapter($request);
            $host = new ServiceHost($context, $request);
            $host->setServiceUri('/odata.svc/');

            $query = App::make('odataquery');
            $meta = App::make('metadata');

            $service = new DataService($query, $meta, $host);
            $cereal = new IronicSerialiser($service, null);
            $service = new DataService($query, $meta, $host, $cereal);
            $service->handleRequest();

            $odataResponse = $context->outgoingResponse();

            if (true === $dump) {
                // iff XTest header is set, containing class and method name
                // dump outgoing odataResponse, metadata, and incoming request
                $xTest = $request->header('XTest');
                $date = Carbon::now(0);
                $timeString = $date->toTimeString();
                $xTest = (null !== $xTest) ? $xTest
                    : $request->method() . ';' . str_replace('/', '-', $request->path()) . ';' . $timeString . ';';
                if (null != $xTest) {
                    $reflectionClass = new \ReflectionClass('Illuminate\Http\Request');
                    $reflectionProperty = $reflectionClass->getProperty('userResolver');
                    $reflectionProperty->setAccessible(true);
                    $reflectionProperty->setValue($request, null);
                    $reflectionProperty = $reflectionClass->getProperty('routeResolver');
                    $reflectionProperty->setAccessible(true);
                    $reflectionProperty->setValue($request, null);
                    $cerealRequest = serialize($request);
                    $cerealMeta = serialize($meta);
                    $cerealResponse = serialize($odataResponse);
                    Storage::put($xTest . 'request', $cerealRequest);
                    Storage::put($xTest . 'metadata', $cerealMeta);
                    Storage::put($xTest . 'response', $cerealResponse);
                }
            }

            $content = $odataResponse->getStream();

            $headers = $odataResponse->getHeaders();
            $responseCode = $headers[\POData\Common\ODataConstants::HTTPRESPONSE_HEADER_STATUS_CODE];
            $responseCode = isset($responseCode) ? intval($responseCode) : 200;
            $response = new Response($content, $responseCode);
            $response->setStatusCode($headers['Status']);

            foreach ($headers as $headerName => $headerValue) {
                if (!is_null($headerValue)) {
                    $response->headers->set($headerName, $headerValue);
                }
            }
            DB::$commitCall();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
        return $response;
    }

    /**
     * @return bool
     */
    protected function getIsDumping()
    {
        $configDump = env('APP_DUMP_REQUESTS', false);
        return true === $configDump;
    }

    /**
     * Is application dry-running (ie, not committing) non-READ requests?
     *
     * @return bool
     */
    protected function getIsDryRun()
    {
        $configDump = env('APP_DRY_RUN', false);
        return true === $configDump;
    }
}
