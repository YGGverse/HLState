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
        TranslatorInterface $translatorInterface,
        EntityManagerInterface $entityManagerInterface
    ): Response
    {
        // Init memory
        $memory = new \Yggverse\Cache\Memory(
            $this->getParameter('app.memcached.host'),
            $this->getParameter('app.memcached.port'),
            $this->getParameter('app.memcached.namespace'),
            $this->getParameter('app.memcached.timeout') + time(),
        );

        // Collect servers info
        $servers = [];

        foreach ((array) $entityManagerInterface->getRepository(Server::class)->findAll() as $server)
        {
            // Init defaults
            $status = false;

            $info =
            [
                'Protocol'   => null,
                'HostName'   => null,
                'Map'        => null,
                'ModDir'     => null,
                'ModDesc'    => null,
                'AppID'      => null,
                'Players'    => null,
                'MaxPlayers' => null,
                'Bots'       => null,
                'Dedicated'  => null,
                'Os'         => null,
                'Password'   => null,
                'Secure'     => null,
                'Version'    => null
            ];

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
                    $status = true;

                    foreach ((array) $node->GetInfo() as $key => $value)
                    {
                        $info[$key] = $value;
                    }
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

            // Add server
            $servers[] = [
                'crc32server' => $server->getCrc32server(),
                'name'        => $server->getName(),
                'host'        => $server->getHost(),
                'port'        => $server->getPort(),
                'added'       => $server->getAdded(),
                'updated'     => $server->getUpdated(),
                'online'      => $server->getOnline(),
                'info'        => $info,
                'status'      => $status,
                'connections' => empty($info['Players']) || $info['Players'] < 0 || empty($info['Bots']) || $info['Bots'] < 0
                                 ? 0
                                 : (int) $info['Players'] - (int) $info['Bots']
            ];
        }

        // Sort by players
        array_multisort(
            array_column(
                $servers,
                'connections'
            ),
            SORT_DESC,
            $servers
        );

        // Online calendar
        $time = time();

        $month = new \Yggverse\Graph\Calendar\Month($time);

        foreach ($month->getNodes() as $day => $node)
        {
            // Skip future days processing
            if ($day > date('j'))
            {
                break;
            }

            // Add daily stats
            $total = $memory->getByMethodCallback(
                $entityManagerInterface->getRepository(Online::class),
                'getMaxPlayersByTimeInterval',
                [
                    strtotime(
                        sprintf(
                            '%s-%s-%s 00:00',
                            date('Y', $time),
                            date('n', $time),
                            $day
                        )
                    ),
                    strtotime(
                        '+1 day',
                        strtotime(
                            sprintf(
                                '%s-%s-%s 00:00',
                                date('Y', $time),
                                date('n', $time),
                                $day
                            )
                        )
                    )
                ],
                time() + ($day == date('j') ? 60 : 2592000)
            );

            $month->addNode(
                $day,
                $total,
                sprintf(
                    $translatorInterface->trans('online %d'),
                    $total
                ),
                null,
                0
            );

            // Add hourly stats
            for ($hour = 0; $hour < 24; $hour++)
            {
                $total = $memory->getByMethodCallback(
                    $entityManagerInterface->getRepository(Online::class),
                    'getMaxPlayersByTimeInterval',
                    [
                        strtotime(
                            sprintf(
                                '%s-%s-%s %s:00',
                                date('Y', $time),
                                date('n', $time),
                                $day,
                                $hour
                            )
                        ),
                        strtotime(
                            sprintf(
                                '%s-%s-%s %s:00',
                                date('Y', $time),
                                date('n', $time),
                                $day,
                                $hour + 1
                            )
                        )
                    ],
                    time() + ($day == date('j') ? 60 : 2592000)
                );

                $month->addNode(
                    $day,
                    $total,
                    sprintf(
                        $translatorInterface->trans('%s:00-%s:00 online %s'),
                        $hour,
                        $hour + 1,
                        $total
                    ),
                    'background-color-default',
                    1
                );
            }
        }

        return $this->render(
            'default/main/index.html.twig',
            [
                'request' => $request,
                'servers' => $servers,
                'month'   =>
                [
                    'online'  => (array) $month->getNodes()
                ]
            ]
        );
    }
}