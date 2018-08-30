<?php
namespace Crunch\FastCGI\Client;

use Crunch\FastCGI\Protocol\Header;
use Crunch\FastCGI\Protocol\Record;
use Crunch\FastCGI\Protocol\Request;
use Crunch\FastCGI\Protocol\RequestInterface;
use Crunch\FastCGI\Protocol\RequestParametersInterface;
use Crunch\FastCGI\Protocol\Role;
use Crunch\FastCGI\ReaderWriter\ReaderInterface;
use React\Promise as promise;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use React\Stream\DuplexStreamInterface;

class Client
{
    /** @var DuplexStreamInterface */
    private $connector;
    /** @var int Next request id to use */
    private $nextRequestId = 1;
    /** @var ResponseParser[] */
    private $responseBuilders = [];

    /** @var Deferred[] */
    private $promises = [];

    /**
     * Read buffer.
     *
     * @var string
     */
    private $data = '';

    /**
     * Creates new client instance.
     *
     * @param DuplexStreamInterface $connector
     */
    public function __construct(DuplexStreamInterface $connector)
    {
        $connector->on('data', function ($data) {
            $this->read($data);
        });
        $this->connector = $connector;
    }

    /**
     * Creates a new responder request.
     *
     * Although you can create a Request instance manually it is highly
     * recommended to use this factory method, because only this one
     * ensures, that the request uses a previously unused request id.
     *
     * @param RequestParametersInterface|null $parameters
     * @param ReaderInterface|null            $stdin
     *
     * @return RequestInterface
     */
    public function newRequest(RequestParametersInterface $parameters = null, ReaderInterface $stdin = null)
    {
        return new Request(Role::responder(), $this->nextRequestId++, true, $parameters, $stdin);
    }

    /**
     * @param RequestInterface $request
     *
     * @return PromiseInterface
     */
    public function sendRequest(RequestInterface $request)
    {
        if (isset($this->promises[$request->getRequestId()])) {
            return promise\reject(new ClientException("ID {$request->getRequestId()} already in use"));
        }
        $this->responseBuilders[$request->getRequestId()] = new ResponseParser($request->getRequestId());
        $this->promises[$request->getRequestId()] = new Deferred();
        foreach ($request->toRecords() as $record) {
            $this->connector->write($record->encode());
        }

        return $this->promises[$request->getRequestId()]->promise();
    }

    private function read($data)
    {
        $this->data .= $data;

        while (strlen($this->data) >= 8) {
            $header = Header::decode(substr($this->data, 0, 8));

            if (strlen($this->data) < $header->getPayloadLength() + 8) {
                return;
            }

            $rawRecord = substr($this->data, 8, $header->getLength());
            $record = Record::decode($header, $rawRecord);
            $this->data = substr($this->data, 8 + $header->getPayloadLength());

            if ($response = $this->responseBuilders[$header->getRequestId()]->pushRecord($record)) {
                $this->promises[$header->getRequestId()]->resolve($response);

            }
        }
    }

    public function close()
    {
        $this->connector->close();
    }
}
