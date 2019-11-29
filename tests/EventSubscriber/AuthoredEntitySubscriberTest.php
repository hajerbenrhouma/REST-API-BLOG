<?php

namespace App\Tests\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\BlogPost;
use App\Entity\User;
use App\EventSubscriber\AuthoredEntitySubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AuthoredEntitySubscriberTest extends TestCase
{
    public function testConfiguration()
    {
        $result = AuthoredEntitySubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(KernelEvents::VIEW, $result);

        $this->assertEquals(
            ['getAuthenticatedUser', EventPriorities::PRE_WRITE],
            $result[KernelEvents::VIEW]
        );
    }

    public function providerSetAuthorCall()
    {
        return [
            [BlogPost::class, true, 'POST'],
            ['NonExisting', false, 'POST'],
            [BlogPost::class, false, 'GET'],
        ];
    }

    /**
     * @dataProvider providerSetAuthorCall
     */
    public function testSetAuthorCall(string $className, bool $shouldCallSetAuthor, string $method)
    {
        $entityMock = $this->getEntityMock($className, $shouldCallSetAuthor);

        $tokenStorageMock = $this->getTokenStorageMock();
        $eventMock = $this->getEventMock($method, $entityMock);

        (new AuthoredEntitySubscriber($tokenStorageMock))->getAuthenticatedUser(
            $eventMock
        );
    }

    /**
     * @return MockObject | TokenStorageInterface
     */
    private
    function getTokenStorageMock()
    {
        $tokenMock = $this->getMockBuilder(TokenInterface::class)
            ->getMockForAbstractClass();
        $tokenMock->expects($this->once())//how much instance number | once | never | count(number)
        ->method('getUser')
            ->willReturn(new User());

        $tokenStorageMock = $this->getMockBuilder(TokenStorageInterface::class)
            ->getMockForAbstractClass();
        $tokenStorageMock->expects($this->once())
            ->method('getToken')
            ->willReturn($tokenMock);
        return $tokenStorageMock;
    }

    /**
     * @return MockObject | ViewEvent
     */
    private
    function getEventMock(string $method, $controllerResult)
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->getMock();
        $requestMock->expects($this->once())
            ->method('getMethod')
            ->willReturn($method);

        $eventMock = $this->getMockBuilder(ViewEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        /*
         * because
         *  $entity = $event->getControllerResult();  NULL
         *  $method = $event->getRequest()->getMethod(); NULL
         * we should do:
         */
        $eventMock->expects($this->once())
            ->method('getControllerResult')
            ->willReturn($controllerResult);

        $eventMock->expects($this->once())
            ->method('getRequest')
            ->willReturn($requestMock);

        return $eventMock;
    }

    /**
     * @return MockObject
     */
    private function getEntityMock(string $className, bool $shouldCallSetAuthor)
    {
        $entityMock = $this->getMockBuilder($className)
            ->setMethods(['setAuthor'])// if is not exist we set this method
            ->getMock();
        $entityMock->expects($shouldCallSetAuthor ? $this->once() : $this->never())
            ->method('setAuthor');
        return $entityMock;
    }
}