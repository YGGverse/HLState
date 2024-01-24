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

class ServerController extends AbstractController
{
    #[Route(
        '/server/{crc32server}',
        name: 'server_index',
        requirements:
        [
            'crc32server' => '\d+',
        ],
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
        // Validate server
        $server = $entityManagerInterface->getRepository(Server::class)->findOneBy(
            [
                'crc32server' => $request->get('crc32server')
            ]
        );

        if (!$server)
        {
            throw $this->createNotFoundException();
        }

        // Prepare page request
        if ($request->get('page') && (int) $request->get('page') > 1)
        {
            $page = (int) $request->get('page');
        }

        else
        {
            $page = 1;
        }

        // Init defaults
        $info    = [];
        $session = [];

        // Get online
        $online = $entityManagerInterface->getRepository(Online::class)->findBy(
            [
                'crc32server' => $server->getCrc32server()
            ],
            [
                'online' == $request->get('sort') && in_array($request->get('field'), ['time','players','bots','total'])
                ? $request->get('field') : 'time' => in_array($request->get('order'), ['asc','desc']) ? $request->get('order') : 'desc',
            ],
            $this->getParameter('app.server.online.limit'),
            'online' == $request->get('sort') ? ($page - 1) * $this->getParameter('app.server.online.limit') : 0
        );

        // Get players
        $players = $entityManagerInterface->getRepository(Player::class)->findBy(
            [
                'crc32server' => $server->getCrc32server()
            ],
            [
                'players' == $request->get('sort') && in_array($request->get('field'), ['name','frags','joined','online'])
                ? $request->get('field') : 'frags' => in_array($request->get('order'), ['asc','desc']) ? $request->get('order') : 'desc',
            ],
            $this->getParameter('app.server.players.limit'),
            'players' == $request->get('sort') ? ($page - 1) * $this->getParameter('app.server.players.limit') : 0
        );

        // Format address
        if (false === filter_var($server->getHost(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))
        {
            $address = "{$server->getHost()}:{$server->getPort()}";
        }

        else
        {
            $address = "[{$server->getHost()}]:{$server->getPort()}";
        }

        // Request server info
        try
        {
            $node = new \xPaw\SourceQuery\SourceQuery();

            $node->Connect(
                false === filter_var($server->getHost(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? $server->getHost() : "[{$server->getHost()}]",
                $server->getPort(),
                1
            );

            if ($node->Ping())
            {
                if ($info = (array) $node->GetInfo())
                {
                    // Get session
                    $session = empty($info['Players']) ? [] : (array) $node->GetPlayers();

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
            throw $this->createNotFoundException();
        }

        catch (\Throwable $error)
        {
            $status = false;
        }

        finally
        {
            $node->Disconnect();
        }

        return $this->render(
            'default/server/index.html.twig',
            [
                'request' => $request,
                'server' =>
                [
                    'address'     => $address,
                    'crc32server' => $server->getCrc32server(),
                    'name'        => $server->getName(),
                    'host'        => $server->getHost(),
                    'port'        => $server->getPort(),
                    'info'        => $info,
                    'session'     => $session,
                    'online'      => $online,
                    'players'     => $players,
                    'status'      => $status,
                    'pagination'  =>
                    [
                        'players' => ceil(
                            $entityManagerInterface->getRepository(Player::class)->getTotalByCrc32server(
                                $server->getCrc32server()
                            ) / $this->getParameter('app.server.players.limit')
                        ),
                        'online'  => ceil(
                            $entityManagerInterface->getRepository(Online::class)->getTotalByCrc32server(
                                $server->getCrc32server()
                            ) / $this->getParameter('app.server.online.limit')
                        )
                    ],
                    'connections' => empty($info['Players']) || $info['Players'] < 0 || empty($info['Bots']) || $info['Bots'] < 0
                                     ? 0
                                     : (int) $info['Players'] - (int) $info['Bots']
                ]
            ]
        );
    }
}