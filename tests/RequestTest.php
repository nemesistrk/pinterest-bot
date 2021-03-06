<?php

namespace szhuk\tests;

use Mockery;
use Mockery\Mock;
use ReflectionClass;
use PHPUnit_Framework_TestCase;
use seregazhuk\PinterestBot\Api\Request;
use seregazhuk\PinterestBot\Api\CurlAdapter;
use seregazhuk\tests\helpers\ResponseHelper;
use seregazhuk\tests\helpers\ReflectionHelper;
use seregazhuk\PinterestBot\Helpers\CsrfHelper;
use seregazhuk\PinterestBot\Contracts\HttpInterface;

/**
 * Class RequestTest.
 */
class RequestTest extends PHPUnit_Framework_TestCase
{
    use ReflectionHelper, ResponseHelper;

    /** @test */
    public function it_should_return_logged_in_status()
    {
        $request = $this->createRequestObject();
        $this->setProperty('loggedIn', false);
        $this->assertFalse($request->isLoggedIn());

        $this->setProperty('loggedIn', true);
        $this->assertTrue($request->isLoggedIn());
    }


    /** @test */
    public function it_should_store_user_agent()
    {
        $userAgentString = 'UserAgentString';

        $request = $this->createRequestObject(new CurlAdapter());
        $request->setUserAgent($userAgentString);
        $this->assertEquals($userAgentString, $this->getProperty('userAgent'));
    }

    /** @test */
    public function it_should_set_csrf_token_to_default_value_after_clear()
    {
        $request = $this->createRequestObject();
        $this->assertEmpty($this->getProperty('csrfToken'));

        $request->clearToken();
        $this->assertEquals(CsrfHelper::DEFAULT_TOKEN, $this->getProperty('csrfToken'));
    }

    /** @test */
    public function it_should_create_simple_pinterest_request_object()
    {
        $emptyRequest = [
            'source_url' => '',
            'data'       => json_encode(
                [
                    'options' => [],
                    'context' => new \stdClass(),
                ]
            ),
        ];

        $object = $this->createRequestObject();
        $request = $object->createRequestData();
        $this->assertEquals($emptyRequest, $request);
        $this->assertEquals('', $request['source_url']);
    }

    /** @test */
    public function it_should_create_pinterest_request_object_with_data()
    {
        $data = ['key' => 'val'];

        $object = $this->createRequestObject();
        $request = $object->createRequestData($data);

        $dataFromRequest = json_decode($request['data'], true);
        $this->assertEquals($data['key'], $dataFromRequest['key']);
    }

    /** @test */
    public function it_should_create_pinterest_request_object_with_bookmarks()
    {
        $bookmarks = 'bookmarks';

        $object = $this->createRequestObject();
        $request = $object->createRequestData([], $bookmarks);
        $dataFromRequest = json_decode($request['data'], true);

        $this->assertEquals($bookmarks, $dataFromRequest['options']['bookmarks']);
    }

    /** @test */
    public function it_should_save_token_from_cookies()
    {
        $cookieFile = __DIR__.'/../'.Request::COOKIE_NAME;
        $token = 'WfdvEjNSLYiykJHDIx4sGSpCS8OhUld0';
        file_put_contents(
            $cookieFile, ".pinterest.com	TRUE	/	TRUE	1488295594	csrftoken	$token"
        );
        $request = $this->createRequestObject();
        $this->setProperty('cookieJar', $cookieFile);
        $request->login();

        unlink($cookieFile);
        $this->assertEquals($token, $this->getProperty('csrfToken'));
    }

    /** @test */
    public function it_should_clear_token_and_login_status_after_logout()
    {
        $request = $this->createRequestObject();
        $this->setProperty('loggedIn', true);

        $request->logout();
        $this->assertFalse($request->isLoggedIn());
        $this->assertEquals(CsrfHelper::DEFAULT_TOKEN, $this->getProperty('csrfToken'));
    }

    /**
     * @test
     * @expectedException seregazhuk\PinterestBot\Exceptions\AuthException
     */
    public function it_should_throw_exception_when_setting_token_from_empty_cookies()
    {
        $request = $this->createRequestObject();
        $this->setProperty('cookieJar', null);
        $request->setTokenFromCookies();
    }

    /**
     * @test
     * @expectedException seregazhuk\PinterestBot\Exceptions\InvalidRequestException
     */
    public function it_should_throw_exception_uploading_file_that_does_not_exist()
    {
        $this->createRequestObject()->upload('image.jpg', 'http://uploadurl.com');
    }

    /**
     * @test
     */
    public function it_should_create_post_data_for_upload()
    {
        $http = $this->getHttpObject();
        $image = 'image.jpg';
        file_put_contents($image, '');

        $this->http_should_execute_and_return($http, null);
        $request = $this->createRequestObject($http);

        $request->upload($image, 'http://uploadurl.com');
        $this->assertNotEmpty($this->getProperty('postFileData'));
        unlink($image);
    }

    /**
     * @param Mock $http
     * @param mixed $returnsValue
     * @param int $times
     */
    protected function http_should_execute_and_return($http, $returnsValue, $times = 1)
    {
        $http->shouldReceive('execute')
            ->times($times)
            ->andReturn($returnsValue);
    }

    protected function tearDown()
    {
        Mockery::close();
    }
    
    /**
     * @return Mock|HttpInterface
     */
    protected function getHttpObject()
    {
        $mock = Mockery::mock(HttpInterface::class);

        return $mock;
    }

    /**
     * @param HttpInterface $http
     * @param string $userAgentString
     *
     * @return Request
     */
    protected function createRequestObject(HttpInterface $http = null, $userAgentString = '')
    {
        $http = $http ? : new CurlAdapter();
        $request = new Request($http, $userAgentString);

        $this->reflection = new ReflectionClass($request);
        $this->setReflectedObject($request);

        return $request;
    }
}
