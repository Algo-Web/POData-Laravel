<?php

declare(strict_types=1);

namespace AlgoWeb\PODataLaravel\OperationContext\Web\Illuminate;

use Illuminate\Http\Request;
use POData\OperationContext\HTTPRequestMethod;
use POData\OperationContext\IHTTPRequest;

/**
 * Class IncomingIlluminateRequest.
 * @package POData\OperationContext\Web\Illuminate
 */
class IncomingIlluminateRequest implements IHTTPRequest
{
    /**
     * The Illuminate request.
     *
     * @var Request
     */
    private $request;

    /**
     * The request headers.
     *
     * @var array
     */
    private $headers = [];

    /**
     * The incoming url in raw format.
     *
     * @var string
     */
    private $rawUrl = null;

    /**
     * The request method (GET, POST, PUT, DELETE or MERGE).
     *
     * @var HTTPRequestMethod HttpVerb
     */
    private $method;

    /**
     * The query options as key value.
     *
     * @var array(string, string);
     */
    private $queryOptions = [];

    /**
     * A collection that represents mapping between query
     * option and its count.
     *
     * @var array(string, int)
     */
    private $queryOptionsCount = [];

    /**
     * IncomingIlluminateRequest constructor.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request           = $request;
        $this->headers           = [];
        $this->queryOptions      = [];
        $this->queryOptionsCount = [];
        $this->method            = new HTTPRequestMethod($this->request->getMethod());
    }

    /**
     * @return string RequestURI called by User with the value of QueryString
     */
    public function getRawUrl(): string
    {
        $this->rawUrl = $this->request->fullUrl();

        return $this->rawUrl;
    }

    /**
     * @param string $key The header name
     *
     * @return array|null|string
     */
    public function getRequestHeader(string $key)
    {
        $result = $this->request->header($key);
        //Zend returns false for a missing header...POData needs a null
        if (false === $result || '' === $result) {
            return null;
        }

        return $result;
    }

    /**
     * Returns the Query String Parameters (QSPs) as an array of KEY-VALUE pairs.  If a QSP appears twice
     * it will have two entries in this array.
     *
     * @return array
     */
    public function getQueryParameters(): array
    {
        //TODO: the contract is more specific than this, it requires the name and values to be decoded
        //not sure how to test that...
        //Have to convert to the stranger format known to POData that deals with multiple query strings.
        //this makes this request a bit non compliant as it doesn't expose duplicate keys, something POData will
        //check for instead whatever parameter was last in the query string is set.  IE
        //odata.svc/?$format=xml&$format=json the format will be json
        $this->queryOptions      = [];
        $this->queryOptionsCount = [];

        foreach ($this->request->all() as $key => $value) {
            $keyBitz              = explode(';', strval($key));
            $newKey               = strtolower($keyBitz[count($keyBitz) - 1]);
            $this->queryOptions[] = [$newKey => $value];
            if (!array_key_exists($key, $this->queryOptionsCount)) {
                $this->queryOptionsCount[$newKey] = 0;
            }
            $this->queryOptionsCount[$newKey]++;
        }

        return $this->queryOptions;
    }

    /**
     * @return HTTPRequestMethod
     */
    public function getMethod(): HTTPRequestMethod
    {
        return $this->method;
    }

    /**
     * @return resource|string|array
     */
    public function getAllInput()
    {
        $content = $this->request->all();
        return !empty($content) ? $content : $this->request->getContent();
    }
}
