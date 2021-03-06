<?php

namespace seregazhuk\tests\Api;

use seregazhuk\PinterestBot\Api\Providers\Conversations;

/**
 * Class ConversationsTest.
 */
class ConversationsTest extends ProviderTest
{
    /**
     * @var Conversations
     */
    protected $provider;

    /**
     * @var string
     */
    protected $providerClass = Conversations::class;

    /** @test */
    public function it_should_send_messages()
    {
        $response = $this->createMessageSendResponse();

        $userId = '1';
        $message = 'test';

        $this->setResponse($response);        
        $this->assertTrue($this->provider->sendMessage($userId, $message));

        $this->setResponse($this->createErrorApiResponse());
        $this->assertFalse($this->provider->sendMessage($userId, $message));
    }

    /** @test */
    public function it_should_send_emails()
    {
        $response = $this->createMessageSendResponse();
        $email = 'test@email.com';
        $message = 'test';

        $this->setResponse($response);        
        $this->assertTrue($this->provider->sendEmail($email, $message));

        $this->setResponse($this->createErrorApiResponse());
        $this->assertFalse($this->provider->sendEmail($email, $message));
    }

    /**
     * @test
     * @expectedException seregazhuk\PinterestBot\Exceptions\InvalidRequestException
     */
    public function it_should_throw_exception_when_sending_message_to_no_users()
    {
        $this->provider->sendMessage([], '');
    }

    /**
     * @test
     * @expectedException seregazhuk\PinterestBot\Exceptions\InvalidRequestException
     */
    public function it_should_throw_exception_when_sending_email_to_no_emails()
    {
        $this->provider->sendEmail([], '');
    }

    /** @test */
    public function it_should_return_last_conversation()
    {
        $lastConversations = [
            '1' => ['result'],
        ];

        $res = $this->createApiResponse(
            [
                'data'  => $lastConversations,
                'error' => null,
            ]
        );

        $this->setResponse($res);
        $this->assertEquals($lastConversations, $this->provider->last());
        
        $this->setResponse(null);
        $this->assertFalse($this->provider->last());
    }

    /**
     * @return array
     */
    protected function createMessageSendResponse()
    {
        $data = [
            'data'  => ['id' => '1'],
            'error' => null,
        ];

        return $this->createApiResponse($data);
    }
}
