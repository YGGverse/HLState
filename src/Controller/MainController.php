<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

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
        ?Request $request
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
                        $servers[] = [
                            'host'   => $hlserver->host,
                            'port'   => $hlserver->port,
                            'alias'  => $hlserver->alias,
                            'info'   => $info,
                            'online' => empty($info['Players']) ? [] : (array) $server->GetPlayers()
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