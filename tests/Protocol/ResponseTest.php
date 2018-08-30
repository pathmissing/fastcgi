<?php
namespace Crunch\FastCGI\Protocol;

use Crunch\FastCGI\ReaderWriter\StringReader;
use PHPUnit\Framework\TestCase as TestCase;

/**
 * @coversDefaultClass \Crunch\FastCGI\Protocol\Response
 * @covers \Crunch\FastCGI\Protocol\Response
 */
class ResponseTest extends TestCase
{
    /**
     * @covers ::getRequestId
     * @uses \Crunch\FastCGI\ReaderWriter\StringReader
     */
    public function testInstanceKeepsRequestId()
    {
        $requestId = 42;
        $content = new StringReader('foo');
        $error = new StringReader('bar');
        $response = new Response($requestId, $content, $error);

        self::assertEquals(42, $response->getRequestId());
    }

    /**
     * @covers ::getError
     * @uses \Crunch\FastCGI\ReaderWriter\StringReader
     */
    public function testInstanceKeepsErrorReader()
    {
        $requestId = 42;
        $content = new StringReader('foo');
        $error = new StringReader('bar');
        $response = new Response($requestId, $content, $error);

        self::assertSame($error, $response->getError());
    }

    /**
     * @covers ::getContent
     * @uses \Crunch\FastCGI\ReaderWriter\StringReader
     */
    public function testInstanceKeepsContentReader()
    {
        $requestId = 42;
        $content = new StringReader('foo');
        $error = new StringReader('bar');
        $response = new Response($requestId, $content, $error);

        self::assertSame($content, $response->getContent());
    }
}
