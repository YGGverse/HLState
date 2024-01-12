<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\Online;
use App\Entity\Player;
use App\Entity\Server;

use Doctrine\ORM\EntityManagerInterface;

class RssController extends AbstractController
{
    #[Route(
        '/rss/online/{crc32server}',
        name: 'rss_online',
        requirements:
        [
            'crc32server' => '\d+',
        ],
        methods:
        [
            'GET'
        ]
    )]
    public function online(
        ?Request $request,
        EntityManagerInterface $entityManagerInterface
    ): Response
    {
        $online = [];

        foreach ($entityManagerInterface->getRepository(Online::class)->findBy(
            [
                'crc32server' => $request->get('crc32server')
            ],
            [
                'id' => 'DESC' // same as online.time but faster
            ],
            10
        ) as $value)
        {
            $online[] =
            [
                'id'      => $value->getId(),
                'bots'    => $value->getBots(),
                'players' => $value->getPlayers(),
                'total'   => $value->getTotal(),
                'time'    => $value->getTime()
            ];
        }

        // Response
        $response = new Response();

        $response->headers->set(
            'Content-Type',
            'text/xml'
        );

        return $this->render(
            'default/rss/online.xml.twig',
            [
                'server' =>
                [
                    'crc32server' => $request->get('crc32server'),
                    'online'      => $online
                ]
            ],
            $response
        );

        throw $this->createNotFoundException();
    }

    #[Route(
        '/rss/players/{crc32server}',
        name: 'rss_players',
        requirements:
        [
            'crc32server' => '\d+',
        ],
        methods:
        [
            'GET'
        ]
    )]
    public function players(
        ?Request $request,
        EntityManagerInterface $entityManagerInterface
    ): Response
    {
        $players = [];

        foreach ($entityManagerInterface->getRepository(Player::class)->findBy(
            [
                'crc32server' => $request->get('crc32server')
            ],
            [
                'id' => 'DESC'
            ],
            10
        ) as $value)
        {
            $players[] =
            [
                'id'     => $value->getId(),
                'name'   => $value->getName(),
                'joined' => $value->getJoined()
            ];
        }

        // Response
        $response = new Response();

        $response->headers->set(
            'Content-Type',
            'text/xml'
        );

        return $this->render(
            'default/rss/players.xml.twig',
            [
                'server' =>
                [
                    'crc32server' => $request->get('crc32server'),
                    'players'     => $players
                ]
            ],
            $response
        );
    }
}