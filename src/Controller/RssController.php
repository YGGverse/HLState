<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\Online;
use App\Entity\Player;
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
        // Get HLServers config
        if ($hlservers = file_get_contents($this->getParameter('app.hlservers')))
        {
            $hlservers = json_decode($hlservers);
        }

        else
        {
            $hlservers = [];
        }

        // Find server info
        foreach ($hlservers as $hlserver)
        {
            // Generate CRC32 server ID
            $crc32server = crc32(
                $hlserver->host . ':' . $hlserver->port
            );

            // Skip servers not registered in HLServers
            if ($crc32server != $request->get('crc32server'))
            {
                continue;
            }

            // Get last online value
            $online = $entityManagerInterface->getRepository(Online::class)->findBy(
                [
                    'crc32server' => $crc32server
                ],
                [
                    'id' => 'DESC' // same as online.time but faster
                ],
                10
            );

            $result = [];

            foreach ($online as $value)
            {
                $result[] =
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
                        'crc32' => $crc32server,
                        'host'  => $hlserver->host,
                        'port'  => $hlserver->port,
                    ],
                    'online'      => $result
                ],
                $response
            );
        }

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
        // Get HLServers config
        if ($hlservers = file_get_contents($this->getParameter('app.hlservers')))
        {
            $hlservers = json_decode($hlservers);
        }

        else
        {
            $hlservers = [];
        }

        // Find server info
        foreach ($hlservers as $hlserver)
        {
            // Generate CRC32 server ID
            $crc32server = crc32(
                $hlserver->host . ':' . $hlserver->port
            );

            // Skip servers not registered in HLServers
            if ($crc32server != $request->get('crc32server'))
            {
                continue;
            }

            // Get last players
            $players = $entityManagerInterface->getRepository(Player::class)->findBy(
                [
                    'crc32server' => $crc32server
                ],
                [
                    'id' => 'DESC'
                ],
                10
            );

            $result = [];

            foreach ($players as $value)
            {
                $result[] =
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
                        'crc32' => $crc32server,
                        'host'  => $hlserver->host,
                        'port'  => $hlserver->port,
                    ],
                    'players' => $result
                ],
                $response
            );
        }

        throw $this->createNotFoundException();
    }
}