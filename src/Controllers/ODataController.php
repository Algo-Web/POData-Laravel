<?php

namespace AlgoWeb\PODataLaravel\Controllers;

use AlgoWeb\PODataLaravel\Controllers\Controller as BaseController;
use AlgoWeb\PODataLaravel\Serialisers\IronicSerialiser;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use POData\OperationContext\ServiceHost as ServiceHost;
use POData\OperationContext\Web\Illuminate\IlluminateOperationContext as OperationContextAdapter;
use POData\SimpleDataService as DataService;

class ODataController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Exception
     */
    public function index(Request $request)
    {
        $dryRun = $this->isDryRun();
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
            $pageSize = $this->getAppPageSize();
            if (null !== $pageSize) {
                $service->maxPageSize = intval($pageSize);
            }
            $service->handleRequest();

            $odataResponse = $context->outgoingResponse();

            $content = $odataResponse->getStream();

            $headers = $odataResponse->getHeaders();
            $responseCode = $headers[\POData\Common\ODataConstants::HTTPRESPONSE_HEADER_STATUS_CODE];
            $responseCode = isset($responseCode) ? intval($responseCode) : 200;
            $response = new Response($content, $responseCode);

            foreach ($headers as $headerName => $headerValue) {
                if (null !== $headerValue) {
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
     * Is application dry-running (ie, not committing) non-READ requests?
     *
     * @return bool
     */
    protected function isDryRun()
    {
        $configDump = env('APP_DRY_RUN', false);
        return true === $configDump;
    }

    /**
     * @return int|null
     */
    protected function getAppPageSize()
    {
        /** @var mixed|null $size */
        $size = env('APP_PAGE_SIZE', null);
        return null !== $size ? intval($size) : null;
    }
}
