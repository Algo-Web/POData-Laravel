<?php

declare(strict_types=1);

namespace AlgoWeb\PODataLaravel\OperationContext\Web\Illuminate;

use Illuminate\Http\Request;
use POData\OperationContext\IOperationContext;
use POData\OperationContext\Web\OutgoingResponse;

/**
 * Class IlluminateOperationContext.
 * @package POData\OperationContext\Web\Illuminate
 */
class IlluminateOperationContext implements IOperationContext
{
    /**
     * Object of IncomingRequest which is needed to get all the HTTP headers info.
     *
     * @var IncomingIlluminateRequest
     */
    private $incomingRequest;

    /**
     * Object of OutgoingResponse which is needed to get all the HTTP headers info.
     *
     * @var OutgoingResponse
     */
    private $outgoingResponse;

    /**
     * Initializes a new instance of the IlluminateOperationContext class.
     * This function will perform the following tasks:
     *  (1) Retrieve the current HTTP method,headers and stream.
     *  (2) Populate $_incomingRequest using these.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->incomingRequest  = new IncomingIlluminateRequest($request);
        $this->outgoingResponse = new OutgoingResponse();
    }

    /**
     * Gets the Web request context for the request being sent.
     *
     * @return OutgoingResponse
     */
    public function outgoingResponse(): OutgoingResponse
    {
        return $this->outgoingResponse;
    }

    /**
     * Gets the Web request context for the request being received.
     *
     * @return IncomingIlluminateRequest
     */
    public function incomingRequest(): IncomingIlluminateRequest
    {
        return $this->incomingRequest;
    }
}
