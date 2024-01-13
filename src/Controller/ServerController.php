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

        // Init defaults
        $info    = [];
        $session = [];
        $online  = [];
        $players = [];

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
                $server->getPort()
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

                    // Get online
                    $online = $entityManagerInterface->getRepository(Online::class)->findBy(
                        [
                            'crc32server' => $server->getCrc32Server()
                        ],
                        'online' == $request->get('sort') ? [$field => $order] : ['time' => 'DESC'],
                        10
                    );

                    // Get players
                    $players = $entityManagerInterface->getRepository(Player::class)->findBy(
                        [
                            'crc32server' => $server->getCrc32Server()
                        ],
                        'players' == $request->get('sort') ? [$field => $order] : ['frags' => 'DESC'],
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
                    'crc32server' => $server->getCrc32Server(),
                    'name'        => $server->getName(),
                    'host'        => $server->getHost(),
                    'port'        => $server->getPort(),
                    'info'        => $info,
                    'session'     => $session,
                    'online'      => $online,
                    'players'     => $players,
                    'status'      => $status,
                    'connections' => is_null($info['Players']) || $info['Players'] < 0 || is_null($info['Bots']) || $info['Bots'] < 0
                                     ? 0
                                     : (int) $info['Players'] - (int) $info['Bots']
                ]
            ]
        );
    }
}