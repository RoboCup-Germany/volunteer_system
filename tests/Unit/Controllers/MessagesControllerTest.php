<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Controllers;

use Carbon\Carbon;
use Volunteersystem\Controllers\MessagesController;
use Volunteersystem\Events\EventDispatcher;
use Volunteersystem\Helpers\Authenticator;
use Volunteersystem\Http\Exceptions\HttpForbidden;
use Volunteersystem\Http\Exceptions\ValidationException;
use Volunteersystem\Http\UrlGenerator;
use Volunteersystem\Http\UrlGeneratorInterface;
use Volunteersystem\Http\Validation\Validator;
use Volunteersystem\Models\Message;
use Volunteersystem\Models\User\User;
use Volunteersystem\Test\Unit\HasDatabase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use PHPUnit\Framework\MockObject\MockObject;

class MessagesControllerTest extends ControllerTest
{
    use HasDatabase;

    protected MessagesController $controller;

    protected Authenticator|MockObject $auth;

    protected User $userA;
    protected User $userB;

    protected Carbon $now;
    protected Carbon $oneMinuteAgo;
    protected Carbon $twoMinutesAgo;

    protected EventDispatcher $events;

    /**
     * @testdox index: underNormalConditions -> returnsCorrectViewAndData
     * @covers \Volunteersystem\Controllers\MessagesController::__construct
     * @covers \Volunteersystem\Controllers\MessagesController::index
     * @covers \Volunteersystem\Controllers\MessagesController::listConversations
     * @covers \Volunteersystem\Controllers\MessagesController::latestMessagePerConversation
     * @covers \Volunteersystem\Controllers\MessagesController::numberOfUnreadMessagesPerConversation
     * @covers \Volunteersystem\Controllers\MessagesController::raw
     */
    public function testIndexUnderNormalConditionsReturnsCorrectViewAndData(): void
    {
        $this->response->expects($this->once())
            ->method('withView')
            ->willReturnCallback(function (string $view, array $data) {
                $this->assertEquals('pages/messages/index.twig', $view);
                $this->assertArrayHasKey('conversations', $data);
                $this->assertArrayHasKey('users', $data);
                $this->assertArrayOrCollection($data['conversations']);
                $this->assertArrayOrCollection($data['users']);
                return $this->response;
            });

        $this->controller->index();
    }

    /**
     * @testdox index: User is shown as first name and last name instead of nickname
     * @covers \Volunteersystem\Controllers\MessagesController::listConversations
     */
    public function testIndexUnderNormalConditionsReturnsFormattedUserName(): void
    {
        $this->config->set('display_full_name', true);

        $this->userA->personalData->first_name = 'Frank';
        $this->userA->personalData->last_name = 'Nord';
        $this->userA->personalData->save();

        $this->response->expects($this->once())
            ->method('withView')
            ->willReturnCallback(function (string $view, array $data) {
                $this->assertEquals('Frank Nord', $data['users'][1]);
                return $this->response;
            });

        $this->controller->index();
    }

    /**
     * @testdox index: usersExist -> returnsUsersWithMeAtFirstPosition
     * @covers \Volunteersystem\Controllers\MessagesController::index
     * @covers \Volunteersystem\Controllers\MessagesController::listConversations
     * @covers \Volunteersystem\Controllers\MessagesController::latestMessagePerConversation
     * @covers \Volunteersystem\Controllers\MessagesController::numberOfUnreadMessagesPerConversation
     * @covers \Volunteersystem\Controllers\MessagesController::raw
     */
    public function testIndexUsersExistReturnsUsersWithMeAtFirstPosition(): void
    {
        User::factory(['name' => '0'])->create(); // alphabetically before me ("a"), but still listed after me

        $this->response->expects($this->once())
            ->method('withView')
            ->willReturnCallback(function (string $view, array $data) {
                $users = $data['users'];

                $this->assertEquals(3, count($users));
                $this->assertEquals('a', $users->shift());
                $this->assertEquals('0', $users->shift());
                $this->assertEquals('b', $users->shift());

                return $this->response;
            });

        $this->controller->index();
    }

    /**
     * @testdox index: withNoConversation -> returnsEmptyConversationList
     * @covers \Volunteersystem\Controllers\MessagesController::index
     * @covers \Volunteersystem\Controllers\MessagesController::listConversations
     * @covers \Volunteersystem\Controllers\MessagesController::latestMessagePerConversation
     * @covers \Volunteersystem\Controllers\MessagesController::numberOfUnreadMessagesPerConversation
     * @covers \Volunteersystem\Controllers\MessagesController::raw
     */
    public function testIndexWithNoConversationReturnsEmptyConversationList(): void
    {
        $this->response->expects($this->once())
            ->method('withView')
            ->willReturnCallback(function (string $view, array $data) {
                $this->assertEquals(0, count($data['conversations']));
                return $this->response;
            });

        $this->controller->index();
    }

    /**
     * @testdox index: withConversation -> conversationContainsCorrectData
     * @covers \Volunteersystem\Controllers\MessagesController::index
     * @covers \Volunteersystem\Controllers\MessagesController::listConversations
     * @covers \Volunteersystem\Controllers\MessagesController::latestMessagePerConversation
     * @covers \Volunteersystem\Controllers\MessagesController::numberOfUnreadMessagesPerConversation
     * @covers \Volunteersystem\Controllers\MessagesController::raw
     */
    public function testIndexWithConversationConversationContainsCorrectData(): void
    {
        // save messages in wrong order to ensure latest message considers creation date, not id.
        $this->createMessage($this->userA, $this->userB, 'a>b', $this->now);
        $this->createMessage($this->userB, $this->userA, 'b>a', $this->twoMinutesAgo);
        $this->createMessage($this->userB, $this->userA, 'b>a', $this->oneMinuteAgo);

        $this->response->expects($this->once())
            ->method('withView')
            ->willReturnCallback(function (string $view, array $data) {
                $conversations = $data['conversations'];

                $this->assertEquals(1, count($conversations));
                $c = $conversations[0];

                $this->assertArrayHasKey('other_user', $c);
                $this->assertArrayHasKey('latest_message', $c);
                $this->assertArrayHasKey('unread_messages', $c);

                $this->assertTrue($c['other_user'] instanceof User);
                $this->assertTrue($c['latest_message'] instanceof Message);
                $this->assertIsNumeric($c['unread_messages']);

                $this->assertEquals('b', $c['other_user']->name);
                $this->assertEquals('b>a', $c['latest_message']->text);
                $this->assertEquals(2, $c['unread_messages']);

                return $this->response;
            });

        $this->controller->index();
    }

    /**
     * @testdox index: withConversations -> onlyContainsConversationsWithMe
     * @covers \Volunteersystem\Controllers\MessagesController::index
     * @covers \Volunteersystem\Controllers\MessagesController::listConversations
     * @covers \Volunteersystem\Controllers\MessagesController::latestMessagePerConversation
     * @covers \Volunteersystem\Controllers\MessagesController::numberOfUnreadMessagesPerConversation
     * @covers \Volunteersystem\Controllers\MessagesController::raw
     */
    public function testIndexWithConversationsOnlyContainsConversationsWithMe(): void
    {
        $userC = User::factory(['name' => 'c'])->create();

        // save messages in wrong order to ensure latest message considers creation date, not id.
        $this->createMessage($this->userA, $this->userB, 'a>b', $this->now);
        $this->createMessage($this->userB, $userC, 'b>c', $this->now);
        $this->createMessage($userC, $this->userA, 'c>a', $this->now);

        $this->response->expects($this->once())
            ->method('withView')
            ->willReturnCallback(function (string $view, array $data) {
                $conversations = $data['conversations'];

                $this->assertEquals(2, count($conversations));
                $msg0 = $conversations[0]['latest_message']->text;
                $msg1 = $conversations[1]['latest_message']->text;
                $this->assertTrue(($msg0 == 'a>b' && $msg1 == 'c>a') || ($msg1 == 'c>a' && $msg0 == 'a>b'));

                return $this->response;
            });

        $this->controller->index();
    }

    /**
     * @testdox index: withConversations -> conversationsOrderedByDate
     * @covers \Volunteersystem\Controllers\MessagesController::index
     * @covers \Volunteersystem\Controllers\MessagesController::listConversations
     * @covers \Volunteersystem\Controllers\MessagesController::latestMessagePerConversation
     * @covers \Volunteersystem\Controllers\MessagesController::numberOfUnreadMessagesPerConversation
     * @covers \Volunteersystem\Controllers\MessagesController::raw
     */
    public function testIndexWithConversationsConversationsOrderedByDate(): void
    {
        $userC = User::factory(['name' => 'c'])->create();
        $userD = User::factory(['name' => 'd'])->create();

        $this->createMessage($this->userA, $this->userB, 'a>b', $this->now);
        $this->createMessage($userD, $this->userA, 'd>a', $this->twoMinutesAgo);
        $this->createMessage($this->userA, $userC, 'a>c', $this->oneMinuteAgo);

        $this->response->expects($this->once())
            ->method('withView')
            ->willReturnCallback(function (string $view, array $data) {
                $conversations = $data['conversations'];

                $this->assertEquals('a>b', $conversations[0]['latest_message']->text);
                $this->assertEquals('a>c', $conversations[1]['latest_message']->text);
                $this->assertEquals('d>a', $conversations[2]['latest_message']->text);

                return $this->response;
            });

        $this->controller->index();
    }

    /**
     * @testdox redirectToConversation: withNoUserIdGiven -> throwsException
     * @covers \Volunteersystem\Controllers\MessagesController::redirectToConversation
     */
    public function testRedirectToConversationWithNoUserIdGivenThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        $this->controller->redirectToConversation($this->request);
    }

    /**
     * @testdox redirectToConversation: withUserIdGiven -> redirect
     * @covers \Volunteersystem\Controllers\MessagesController::redirectToConversation
     */
    public function testRedirectToConversationWithUserIdGivenRedirect(): void
    {
        $this->request = $this->request->withParsedBody(['user_id' => '1']);
        $this->response->expects($this->once())
            ->method('redirectTo')
            ->with('http://localhost/messages/1#newest')
            ->willReturn($this->response);

        $this->controller->redirectToConversation($this->request);
    }

    /**
     * @testdox messagesOfConversation: withNoUserIdGiven -> throwsException
     * @covers \Volunteersystem\Controllers\MessagesController::messagesOfConversation
     */
    public function testMessagesOfConversationWithNoUserIdGivenThrowsException(): void
    {
        $this->expectException(ModelNotFoundException::class);
        $this->controller->messagesOfConversation($this->request);
    }

    /**
     * @testdox messagesOfConversation: withUnknownUserIdGiven -> throwsException
     * @covers \Volunteersystem\Controllers\MessagesController::messagesOfConversation
     */
    public function testMessagesOfConversationWithUnknownUserIdGivenThrowsException(): void
    {
        $this->request->attributes->set('user_id', '1234');
        $this->expectException(ModelNotFoundException::class);
        $this->controller->messagesOfConversation($this->request);
    }

    /**
     * @testdox messagesOfConversation: underNormalConditions -> returnsCorrectViewAndData
     * @covers \Volunteersystem\Controllers\MessagesController::messagesOfConversation
     */
    public function testMessagesOfConversationUnderNormalConditionsReturnsCorrectViewAndData(): void
    {
        $this->request->attributes->set('user_id', $this->userB->id);

        $this->response->expects($this->once())
            ->method('withView')
            ->willReturnCallback(function (string $view, array $data) {
                $this->assertEquals('pages/messages/conversation.twig', $view);
                $this->assertArrayHasKey('messages', $data);
                $this->assertArrayHasKey('other_user', $data);
                $this->assertArrayOrCollection($data['messages']);
                $this->assertTrue($data['other_user'] instanceof User);
                return $this->response;
            });

        $this->controller->messagesOfConversation($this->request);
    }

    /**
     * @testdox messagesOfConversation: withNoMessages -> returnsEmptyMessageList
     * @covers \Volunteersystem\Controllers\MessagesController::messagesOfConversation
     */
    public function testMessagesOfConversationWithNoMessagesReturnsEmptyMessageList(): void
    {
        $this->request->attributes->set('user_id', $this->userB->id);

        $this->response->expects($this->once())
            ->method('withView')
            ->willReturnCallback(function (string $view, array $data) {
                $this->assertEquals(0, count($data['messages']));
                return $this->response;
            });

        $this->controller->messagesOfConversation($this->request);
    }

    /**
     * @testdox messagesOfConversation: withMessages -> messagesOnlyWithThatUserOrderedByDate
     * @covers \Volunteersystem\Controllers\MessagesController::messagesOfConversation
     */
    public function testMessagesOfConversationWithMessagesMessagesOnlyWithThatUserOrderedByDate(): void
    {
        $this->request->attributes->set('user_id', $this->userB->id);

        $userC = User::factory(['name' => 'c'])->create();

        // to be listed
        $this->createMessage($this->userA, $this->userB, 'a>b', $this->now);
        $this->createMessage($this->userB, $this->userA, 'b>a', $this->twoMinutesAgo);
        $this->createMessage($this->userB, $this->userA, 'b>a2', $this->oneMinuteAgo);

        // not to be listed
        $this->createMessage($this->userA, $userC, 'a>c', $this->now);
        $this->createMessage($userC, $this->userB, 'b>c', $this->now);

        $this->response->expects($this->once())
            ->method('withView')
            ->willReturnCallback(function (string $view, array $data) {
                $messages = $data['messages'];
                $this->assertEquals(3, count($messages));
                $this->assertEquals('b>a', $messages[0]->text);
                $this->assertEquals('b>a2', $messages[1]->text);
                $this->assertEquals('a>b', $messages[2]->text);

                return $this->response;
            });

        $this->controller->messagesOfConversation($this->request);
    }

    /**
     * @testdox messagesOfConversation: withUnreadMessages -> messagesToMeWillStillBeReturnedAsUnread
     * @covers \Volunteersystem\Controllers\MessagesController::messagesOfConversation
     */
    public function testMessagesOfConversationWithUnreadMessagesMessagesToMeWillStillBeReturnedAsUnread(): void
    {
        $this->request->attributes->set('user_id', $this->userB->id);
        $this->createMessage($this->userB, $this->userA, 'b>a', $this->now);

        $this->response->expects($this->once())
            ->method('withView')
            ->willReturnCallback(function (string $view, array $data) {
                $this->assertFalse($data['messages'][0]->read);
                return $this->response;
            });
        $this->controller->messagesOfConversation($this->request);
    }

    /**
     * @testdox messagesOfConversation: withUnreadMessages -> messagesToMeWillBeMarkedAsRead
     * @covers \Volunteersystem\Controllers\MessagesController::messagesOfConversation
     */
    public function testMessagesOfConversationWithUnreadMessagesMessagesToMeWillBeMarkedAsRead(): void
    {
        $this->request->attributes->set('user_id', $this->userB->id);
        $this->response->expects($this->once())
            ->method('withView')
            ->willReturnCallback(function (string $view, array $data) {
                return $this->response;
            });

        $msg = $this->createMessage($this->userB, $this->userA, 'b>a', $this->now);
        $this->controller->messagesOfConversation($this->request);
        $this->assertTrue(Message::whereId($msg->id)->first()->read);
    }

    /**
     * @testdox messagesOfConversation: withMyUserIdGiven -> returnsMessagesFromMeToMe
     * @covers \Volunteersystem\Controllers\MessagesController::messagesOfConversation
     */
    public function testMessagesOfConversationWithMyUserIdGivenReturnsMessagesFromMeToMe(): void
    {
        $this->request->attributes->set('user_id', $this->userA->id); // myself

        $this->createMessage($this->userA, $this->userA, 'a>a1', $this->now);
        $this->createMessage($this->userA, $this->userA, 'a>a2', $this->twoMinutesAgo);

        $this->response->expects($this->once())
            ->method('withView')
            ->willReturnCallback(function (string $view, array $data) {
                $messages = $data['messages'];
                $this->assertEquals(2, count($messages));
                $this->assertEquals('a>a2', $messages[0]->text);
                $this->assertEquals('a>a1', $messages[1]->text);

                return $this->response;
            });

        $this->controller->messagesOfConversation($this->request);
    }

    /**
     * @testdox send: withNoTextGiven -> throwsException
     * @covers \Volunteersystem\Controllers\MessagesController::send
     */
    public function testSendWithNoTextGivenThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        $this->controller->send($this->request);
    }

    /**
     * @testdox send: withNoUserIdGiven -> throwsException
     * @covers \Volunteersystem\Controllers\MessagesController::send
     */
    public function testSendWithNoUserIdGivenThrowsException(): void
    {
        $this->request = $this->request->withParsedBody(['text' => 'a']);
        $this->expectException(ModelNotFoundException::class);
        $this->controller->send($this->request);
    }

    /**
     * @testdox send: withUnknownUserIdGiven -> throwsException
     * @covers \Volunteersystem\Controllers\MessagesController::send
     */
    public function testSendWithUnknownUserIdGivenThrowsException(): void
    {
        $this->request = $this->request->withParsedBody(['text' => 'a']);
        $this->request->attributes->set('user_id', '1234');
        $this->expectException(ModelNotFoundException::class);
        $this->controller->send($this->request);
    }

    /**
     * @testdox send: withUserAndTextGiven -> savesMessage
     * @covers \Volunteersystem\Controllers\MessagesController::send
     */
    public function testSendWithUserAndTextGivenSavesMessage(): void
    {
        $this->request = $this->request->withParsedBody(['text' => 'a']);
        $this->request->attributes->set('user_id', $this->userB->id);

        $this->response->expects($this->once())
            ->method('redirectTo')
            ->with('http://localhost/messages/' . $this->userB->id . '#newest')
            ->willReturn($this->response);

        $this->setExpects($this->events, 'dispatch', ['message.created'], []);

        $this->controller->send($this->request);

        $msg = Message::whereText('a')->first();
        $this->assertEquals($this->userA->id, $msg->user_id);
        $this->assertEquals($this->userB->id, $msg->receiver_id);
        $this->assertFalse($msg->read);
    }

    /**
     * @testdox send: withMyUserIdGiven -> savesMessageAlreadyMarkedAsRead
     * @covers \Volunteersystem\Controllers\MessagesController::send
     */
    public function testSendWithMyUserIdGivenSavesMessageAlreadyMarkedAsRead(): void
    {
        $this->request = $this->request->withParsedBody(['text' => 'a']);
        $this->request->attributes->set('user_id', $this->userA->id);

        $this->response->expects($this->once())
            ->method('redirectTo')
            ->with('http://localhost/messages/' . $this->userA->id . '#newest')
            ->willReturn($this->response);

        $this->setExpects($this->events, 'dispatch', ['message.created'], []);

        $this->controller->send($this->request);

        $msg = Message::whereText('a')->first();
        $this->assertEquals($this->userA->id, $msg->user_id);
        $this->assertEquals($this->userA->id, $msg->receiver_id);
        $this->assertTrue($msg->read);
    }

    /**
     * @testdox delete: withNoMsgIdGiven -> throwsException
     * @covers \Volunteersystem\Controllers\MessagesController::delete
     */
    public function testDeleteWithNoMsgIdGivenThrowsException(): void
    {
        $this->expectException(ModelNotFoundException::class);
        $this->controller->delete($this->request);
    }

    /**
     * @testdox delete: tryingToDeleteSomeonesMessage -> throwsException
     * @covers \Volunteersystem\Controllers\MessagesController::delete
     */
    public function testDeleteTryingToDeleteSomeonesMessageThrowsException(): void
    {
        $msg = $this->createMessage($this->userB, $this->userA, 'a>b', $this->now);
        $this->request->attributes->set('msg_id', $msg->id);
        $this->expectException(HttpForbidden::class);

        $this->controller->delete($this->request);
    }

    /**
     * @testdox delete: tryingToDeleteMyMessage -> deletesItAndRedirect
     * @covers \Volunteersystem\Controllers\MessagesController::delete
     */
    public function testDeleteTryingToDeleteMyMessageDeletesItAndRedirect(): void
    {
        $msg = $this->createMessage($this->userA, $this->userB, 'a>b', $this->now);
        $this->request->attributes->set('msg_id', $msg->id);
        $this->request->attributes->set('user_id', '1');

        $this->response->expects($this->once())
            ->method('redirectTo')
            ->with('http://localhost/messages/1#newest')
            ->willReturn($this->response);

        $this->controller->delete($this->request);

        $this->assertEquals(0, count(Message::whereId($msg->id)->get()));
    }

    /**
     * Setup environment
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->auth = $this->createMock(Authenticator::class);
        $this->app->instance(Authenticator::class, $this->auth);

        $this->app->bind(UrlGeneratorInterface::class, UrlGenerator::class);

        $this->userA = User::factory(['name' => 'a'])->create();
        $this->userB = User::factory(['name' => 'b'])->create();
        $this->setExpects($this->auth, 'user', null, $this->userA, $this->any());

        $this->now = Carbon::now();
        $this->oneMinuteAgo = Carbon::now()->subMinute();
        $this->twoMinutesAgo = Carbon::now()->subMinutes(2);

        $this->controller = $this->app->get(MessagesController::class);
        $this->controller->setValidator(new Validator());

        $this->events = $this->createMock(EventDispatcher::class);
        $this->app->instance('events.dispatcher', $this->events);
    }

    protected function assertArrayOrCollection(mixed $obj): void
    {
        $this->assertTrue(gettype($obj) == 'array' || $obj instanceof Collection);
    }

    protected function createMessage(User $from, User $to, string $text, Carbon $at): Message
    {
        Message::unguard(); // unguard temporarily to save custom creation dates.
        $msg = new Message([
            'user_id'     => $from->id,
            'receiver_id' => $to->id,
            'text'        => $text,
            'created_at'  => $at,
            'updated_at'  => $at,
        ]);
        $msg->save();
        Message::reguard();

        return $msg;
    }
}
