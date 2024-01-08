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
        // Prepare request
        if ('online' == $request->get('sort') && in_array($request->get('field'), ['time','players','bots','total']))
        {
            $field = $request->get('field');
        }

        else if ('players' == $request->get('sort') && in_array($request->get('field'), ['name','frags','joined','online']))
        {
            $field = $request->get('field');
        }

        else
        {
            $field = 'time';
        }

        if (in_array($request->get('order'), ['asc','desc']))
        {
            $order = $request->get('order');
        }

        else
        {
            $order = 'desc';
        }

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
            // Init defaults
            $info    = [];
            $session = [];
            $online  = [];
            $players = [];

            // Generate CRC32 ID
            $crc32server = crc32(
                $hlserver->host . ':' . $hlserver->port
            );

            // Prepare aliases
            $aliases = [];

            foreach ($hlserver->alias as $value)
            {
                $alias = new \xPaw\SourceQuery\SourceQuery();

                $alias->Connect(
                    $value->host,
                    $value->port
                );

                $aliases[] = [
                    'host'   => $value->host,
                    'port'   => $value->port,
                    'status' => $alias->Ping()
                ];
            }

            // Request server info
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
                            'online' == $request->get('sort') && $crc32server == $request->get('crc32server') ? [$field => $order] : ['time' => 'DESC'],
                            10
                        );

                        // Get players
                        $players = $entityManagerInterface->getRepository(Player::class)->findBy(
                            [
                                'crc32server' => $crc32server
                            ],
                            'players' == $request->get('sort') && $crc32server == $request->get('crc32server') ? [$field => $order] : ['frags' => 'DESC'],
                            10
                        );
                    }

                    $status = true;
                }

                else
                {
                    $status = false;
                }
            }

            catch (Exception $error)
            {
                continue;
            }

            catch (\Throwable $error)
            {
                $status = false;
            }

            finally
            {
                $server->Disconnect();
            }

            // Add server
            $servers[] = [
                'crc32server' => $crc32server,
                'host'        => $hlserver->host,
                'port'        => $hlserver->port,
                'description' => $hlserver->description,
                'aliases'     => $aliases,
                'info'        => $info,
                'session'     => $session,
                'online'      => $online,
                'players'     => $players,
                'status'      => $status
            ];
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