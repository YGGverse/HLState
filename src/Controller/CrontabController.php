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

class CrontabController extends AbstractController
{
    #[Route(
        '/crontab/index',
        name: 'crontab_index',
        methods:
        [
            'GET'
        ]
    )]
    public function index(
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

        // Collect servers info
        $servers = [];

        foreach ($hlservers as $hlserver)
        {
            try
            {
                $server = new \xPaw\SourceQuery\SourceQuery();

                $server->Connect(
                    $hlserver->host,
                    $hlserver->port
                );

                if ($server->Ping())
                {
                    if ($info = (array) $server->GetInfo())
                    {
                        // Filter response
                        $bots    = isset($info['Bots']) && $info['Bots'] > 0 ? (int) $info['Bots'] : 0;
                        $players = isset($info['Players']) && $info['Players'] > 0 ? (int) $info['Players'] - $bots : 0;
                        $total   = $players + $bots;

                        // Generate CRC32 server ID
                        $crc32server = crc32(
                            $hlserver->host . ':' . $hlserver->port
                        );

                        // Get last online value
                        $online = $entityManagerInterface->getRepository(Online::class)->findOneBy(
                            [
                                'crc32server' => $crc32server
                            ],
                            [
                                'id' => 'DESC' // same as online.time but faster
                            ]
                        );

                        // Add new record if online changed
                        if
                        (
                            is_null($online)
                            ||
                            $players !== $online->getPlayers()
                            // ||
                            // $bots !== $online->getBots()
                            // ||
                            // $total !== $online->getTotal()
                        )
                        {
                            $online = new Online();

                            $online->setCrc32server(
                                $crc32server
                            );

                            $online->setTime(
                                time()
                            );

                            $online->setPlayers(
                                $players
                            );

                            $online->setBots(
                                $bots
                            );

                            $online->setTotal(
                                $total
                            );

                            $entityManagerInterface->persist(
                                $online
                            );

                            $entityManagerInterface->flush();
                        }

                        // Update player stats
                        if ($players)
                        {
                            foreach ((array) $server->GetPlayers() as $session)
                            {
                                // Validate fields
                                if
                                (
                                    !isset($session['Name']) || mb_strlen($session['Name']) > 255
                                    ||
                                    !isset($session['TimeF']) || (int) $session['TimeF'] < 0
                                    ||
                                    !isset($session['Frags']) || (int) $session['Frags'] < 0
                                )
                                {
                                    continue;
                                }

                                // Skip bots
                                if ($session['TimeF'] == '59:59')
                                {
                                    continue;
                                }

                                // Generate CRC32 server ID
                                $crc32name = crc32(
                                    $session['Name']
                                );

                                $player = $entityManagerInterface->getRepository(Player::class)->findOneBy(
                                    [
                                        'crc32server' => $crc32server,
                                        'crc32name'   => $crc32name,
                                    ]
                                );

                                // Player exists
                                if ($player)
                                {
                                    $player->setUpdated(
                                        time()
                                    );

                                    $player->setOnline(
                                        time()
                                    );

                                    if ((int) $session['Frags'] > $player->getFrags())
                                    {
                                        $player->setFrags(
                                            (int) $session['Frags']
                                        );
                                    }
                                }

                                // Create new player
                                else
                                {
                                    $player = new Player();

                                    $player->setCrc32server(
                                        $crc32server
                                    );

                                    $player->setCrc32name(
                                        $crc32name
                                    );

                                    $player->setJoined(
                                        time()
                                    );

                                    $player->setUpdated(
                                        time()
                                    );

                                    $player->setOnline(
                                        time()
                                    );

                                    $player->setName(
                                        (string) $session['Name']
                                    );

                                    $player->setFrags(
                                        (int) $session['Frags']
                                    );
                                }

                                // Update DB
                                $entityManagerInterface->persist(
                                    $player
                                );

                                $entityManagerInterface->flush();
                            }
                        }
                    }
                }
            }

            catch (Exception $error)
            {
                continue;
            }

            finally
            {
                $server->Disconnect();
            }
        }

        // Render response
        return new Response(); // @TODO
    }
}