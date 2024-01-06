<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\Online;
use Doctrine\ORM\EntityManagerInterface;

class MainController extends AbstractController
{
    #[Route(
        '/',
        name: 'main_index',
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
                        // Generate CRC32 ID
                        $crc32server = crc32(
                            $hlserver->host . ':' . $hlserver->port
                        );

                        // Get session
                        $session = empty($info['Players']) ? [] : (array) $server->GetPlayers();

                        // Sort by players by frags
                        if ($session)
                        {
                            array_multisort(
                                array_column(
                                    $session,
                                    'Frags'
                                ),
                                SORT_DESC,
                                $session
                            );
                        }

                        // Get online
                        $online = $entityManagerInterface->getRepository(Online::class)->findBy(
                            [
                                'crc32server' => $crc32server
                            ],
                            [
                                'id' => 'DESC' // same as online.time but faster
                            ],
                            10
                        );

                        // Add server
                        $servers[] = [
                            'crc32server' => $crc32server,
                            'host'        => $hlserver->host,
                            'port'        => $hlserver->port,
                            'alias'       => $hlserver->alias,
                            'info'        => $info,
                            'session'     => $session,
                            'online'      => $online
                        ];
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

        return $this->render(
            'default/main/index.html.twig',
            [
                'request' => $request,
                'servers' => $servers
            ]
        );
    }
}