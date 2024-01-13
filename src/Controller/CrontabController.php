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
        TranslatorInterface $translatorInterface,
        EntityManagerInterface $entityManagerInterface
    ): Response
    {
        // Prevent multi-thread execution
        $semaphore = sem_get(
            crc32(
                __DIR__ . '.controller.crontab.index',
            ), 1
        );

        if (false === sem_acquire($semaphore, true))
        {
            return new Response(
                $translatorInterface->trans('Process locked by another thread')
            );
        }

        // Get new servers from masters
        foreach ((array) explode(',', $this->getParameter('app.masters')) as $master)
        {
            if (!$host = parse_url($master, PHP_URL_HOST)) // @TODO IPv6 https://bugs.php.net/bug.php?id=72811
            {
                continue;
            }

            if (!$port = parse_url($master, PHP_URL_PORT))
            {
                continue;
            }

            // Connect master node
            $node = new \Yggverse\Hl\Xash3D\Master($host, $port, 1);

            foreach ((array) $node->getServersIPv6() as $key => $value)
            {
                // Generate server identity
                $crc32server = crc32(
                    $key
                );

                // Check server does not exist yet
                $server = $entityManagerInterface->getRepository(Server::class)->findOneBy(
                    [
                        'crc32server' => $crc32server
                    ]
                );

                // Server exist, just update
                if ($server)
                {
                    $server->setUpdated(
                        time()
                    );

                    $server->setOnline(
                        time()
                    );

                    $entityManagerInterface->persist(
                        $server
                    );

                    $entityManagerInterface->flush();

                    continue;
                }

                // Server does not exist, create new record
                $server = new Server();

                $server->setCrc32server(
                    $crc32server
                );

                $server->setHost(
                    $value['host']
                );

                $server->setPort(
                    $value['port']
                );

                $server->setAdded(
                    time()
                );

                $server->setUpdated(
                    time()
                );

                $server->setOnline(
                    time()
                );

                $entityManagerInterface->persist(
                    $server
                );

                $entityManagerInterface->flush();
            }
        }

        // Collect servers info
        $servers = [];

        foreach ((array) $entityManagerInterface->getRepository(Server::class)->findAll() as $server)
        {
            try
            {
                $node = new \xPaw\SourceQuery\SourceQuery();

                $node->Connect(
                    false === filter_var($server->getHost(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? $server->getHost() : "[{$server->getHost()}]",
                    $server->port
                );

                if ($node->Ping())
                {
                    if ($info = (array) $node->GetInfo())
                    {
                        // Filter response
                        $bots    = isset($info['Bots']) && $info['Bots'] > 0 ? (int) $info['Bots'] : 0;
                        $players = isset($info['Players']) && $info['Players'] > 0 ? (int) $info['Players'] - $bots : 0;
                        $total   = $players + $bots;

                        // Update server name
                        if (!empty($info['HostName']) && mb_strlen($info['HostName']) < 256)
                        {
                            $server->setName(
                                (string) $info['HostName']
                            );
                        }

                        $server->setUpdated(
                            time()
                        );

                        $server->setOnline(
                            time()
                        );

                        $entityManagerInterface->persist(
                            $server
                        );

                        $entityManagerInterface->flush();

                        // Get last online value
                        $online = $entityManagerInterface->getRepository(Online::class)->findOneBy(
                            [
                                'crc32server' => $server->getCrc32server()
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
                                $server->getCrc32server()
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
                            foreach ((array) $node->GetPlayers() as $session)
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
                                        'crc32server' => $server->getCrc32server(),
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
                                        $server->getCrc32server()
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

            catch (\Throwable $error)
            {
                continue;
            }

            finally
            {
                $node->Disconnect();
            }
        }

        // Render response
        return new Response(); // @TODO
    }
}