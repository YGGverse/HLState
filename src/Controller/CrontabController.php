<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\Online;
use Doctrine\ORM\EntityManagerInterface;

class CrontabController extends AbstractController
{
    #[Route(
        '/crontab/online',
        name: 'crontab_online',
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
                        $crc32Server = crc32(
                            $hlserver->host . ':' . $hlserver->port
                        );

                        // Get last online value
                        $online = $entityManagerInterface->getRepository(Online::class)->findOneBy(
                            [
                                'crc32server' => $crc32Server
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
                            ||
                            $bots !== $online->getBots()
                            ||
                            $total !== $online->getTotal()
                        )
                        {
                            $online = new Online();

                            $online->setCrc32server(
                                $crc32Server
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