<?php

/*
 * This file is a part of the Tutelar project.
 *
 * Copyright (c) 2022-present Valithor Obsidion <valithor@valzargaming.com>
 */

function webapiFail($part, $id) {
    //logInfo('[webapi] Failed', ['part' => $part, 'id' => $id]);
    return new \React\Http\Message\Response(($id ? 404 : 400), ['Content-Type' => 'text/plain'], ($id ? 'Invalid' : 'Missing').' '.$part);
}

function webapiSnow($string) {
    return preg_match('/^[0-9]{16,20}$/', $string);
}

$external_ip = file_get_contents("http://ipecho.net/plain");
$vzg_ip = gethostbyname('www.valzargaming.com');
$civ13_ip = gethostbyname('www.civ13.com');

$socket = new \React\Socket\Server(sprintf('%s:%s', '0.0.0.0', '55557'), $tutelar->loop);
$webapi = new \React\Http\Server($loop, function (\Psr\Http\Message\ServerRequestInterface $request) use ($tutelar, $socket, $external_ip, $vzg_ip, $civ13_ip)
{
    /*
    $path = explode('/', $request->getUri()->getPath());
    $sub = (isset($path[1]) ? (string) $path[1] : false);
    $id = (isset($path[2]) ? (string) $path[2] : false);
    $id2 = (isset($path[3]) ? (string) $path[3] : false);
    $ip = (isset($path[4]) ? (string) $path[4] : false);
    $idarray = array(); //get from post data (NYI)
    */
    
    $echo = 'API ';
    $sub = 'index.';
    $path = explode('/', $request->getUri()->getPath());
    $repository = $sub = (isset($path[1]) ? (string) strtolower($path[1]) : false); if ($repository) $echo .= "$repository";
    $method = $id = (isset($path[2]) ? (string) strtolower($path[2]) : false); if ($method) $echo .= "/$method";
    $id2 = $repository2 = (isset($path[3]) ? (string) strtolower($path[3]) : false); if ($id2) $echo .= "/$id2";
    $ip = $partial = $method2 = (isset($path[4]) ? (string) strtolower($path[4]) : false); if ($partial) $echo .= "/$partial";
    $id3 = (isset($path[5]) ? (string) strtolower($path[5]) : false); if ($id3) $echo .= "/$id3";
    $id4 = (isset($path[6]) ? (string) strtolower($path[6]) : false); if ($id4) $echo .= "/$id4";
    $idarray = array(); //get from post data (NYI)
    //$tutelar->logger->info($echo);
    
    //if ($ip) $tutelar->logger->info('API IP ' . $ip);
    $whitelist = ['127.0.0.1', $external_ip, $vzg_ip, $civ13_ip];
    $substr_whitelist = ['10.0.0.', '192.168.'];
    $whitelisted = false;
    foreach ($substr_whitelist as $substr) if (str_starts_with($request->getServerParams()['REMOTE_ADDR'], $substr)) $whitelisted = true;
    if (in_array($request->getServerParams()['REMOTE_ADDR'], $whitelist)) $whitelisted = true;
    
    if (!$whitelisted) return $tutelar->logger->info('API REMOTE_ADDR ' . $request->getServerParams()['REMOTE_ADDR']);

    switch ($sub) {
        case (str_starts_with($sub, 'index.')):
            return new \React\Http\Message\Response(301, ['Location' => "https://$vzg_ip:8443/?login", 'Connection' => 'Close'], ''); //Redirect to the website to log in

        case 'github':
            return new \React\Http\Message\Response(301, ['Location' => 'https://github.com/VZGCoders/Palace-Revived', 'Connection' => 'Close'], ''); //Redirect to the github

        case 'favicon.ico':
            if (!$whitelisted) {
                $tutelar->logger->info('API REJECT ' . $request->getServerParams()['REMOTE_ADDR']);
                return new \React\Http\Message\Response(501, ['Content-Type' => 'text/plain'], 'Reject');
            }
            $favicon = file_get_contents('favicon.ico');
            return new \React\Http\Message\Response(200, ['Content-Type' => 'image/x-icon'], $favicon);

        case 'nohup.out':
            if (!$whitelisted) {
                $tutelar->logger->alert('API REJECT ' . $request->getServerParams()['REMOTE_ADDR']);
                return new \React\Http\Message\Response(501, ['Content-Type' => 'text/plain'], 'Reject');
            }
            if ($return = file_get_contents('nohup.out')) return new \React\Http\Message\Response(200, ['Content-Type' => 'text/plain'], $return);
            else return new \React\Http\Message\Response(501, ['Content-Type' => 'text/plain'], "Unable to access `nohup.out`");

        case 'botlog':
            if (!$whitelisted) {
                $tutelar->logger->alert('API REJECT ' . $request->getServerParams()['REMOTE_ADDR']);
                return new \React\Http\Message\Response(501, ['Content-Type' => 'text/plain'], 'Reject');
            }
            if ($return = file_get_contents('botlog.txt')) return new \React\Http\Message\Response(200, ['Content-Type' => 'text/html'], '<meta name="color-scheme" content="light dark"> <div class="checkpoint">' . str_replace('[' . date("Y"), '</div><div> [' . date("Y"), str_replace([PHP_EOL, '[] []', ' [] '], '</div><div>', $return)) . "</div><script>var mainScrollArea=document.getElementsByClassName('checkpoint')[0];var scrollTimeout;window.onload=function(){if (window.location.href==localStorage.getItem('lastUrl')){mainScrollArea.scrollTop=localStorage.getItem('scrollTop');}else{localStorage.setItem('lastUrl',window.location.href);localStorage.setItem('scrollTop',0);}};mainScrollArea.addEventListener('scroll',function(){clearTimeout(scrollTimeout);scrollTimeout=setTimeout(function(){localStorage.setItem('scrollTop',mainScrollArea.scrollTop);},100);});setTimeout(locationreload,10000);function locationreload(){location.reload();}</script>");
            else return new \React\Http\Message\Response(501, ['Content-Type' => 'text/plain'], "Unable to access `botlog.txt`");

        case 'botlog2':
            if (!$whitelisted) {
                $tutelar->logger->alert('API REJECT ' . $request->getServerParams()['REMOTE_ADDR']);
                return new \React\Http\Message\Response(501, ['Content-Type' => 'text/plain'], 'Reject');
            }
            if ($return = file_get_contents('botlog2.txt')) return new \React\Http\Message\Response(200, ['Content-Type' => 'text/html'], '<meta name="color-scheme" content="light dark"> <div class="checkpoint">' . str_replace('[' . date("Y"), '</div><div> [' . date("Y"), str_replace([PHP_EOL, '[] []', ' [] '], '</div><div>', $return)) . "</div><script>var mainScrollArea=document.getElementsByClassName('checkpoint')[0];var scrollTimeout;window.onload=function(){if (window.location.href==localStorage.getItem('lastUrl')){mainScrollArea.scrollTop=localStorage.getItem('scrollTop');}else{localStorage.setItem('lastUrl',window.location.href);localStorage.setItem('scrollTop',0);}};mainScrollArea.addEventListener('scroll',function(){clearTimeout(scrollTimeout);scrollTimeout=setTimeout(function(){localStorage.setItem('scrollTop',mainScrollArea.scrollTop);},100);});setTimeout(locationreload,10000);function locationreload(){location.reload();}</script>");
            else return new \React\Http\Message\Response(501, ['Content-Type' => 'text/plain'], "Unable to access `botlog2.txt`");

        case 'save':
            if (!$whitelisted) {
                $tutelar->saveConfig();
            }

        case 'channel':
            if (!$id || !webapiSnow($id) || !$return = $tutelar->discord->getChannel($id)) return webapiFail('channel_id', $id);
            break;

        case 'guild':
            if (!$id || !webapiSnow($id) || !$return = $tutelar->discord->guilds->get('id', $id)) return webapiFail('guild_id', $id);
            break;

        case 'bans':
            if (!$id || !webapiSnow($id) || !$return = $tutelar->discord->guilds->get('id', $id)->bans) return webapiFail('guild_id', $id);
            break;

        case 'channels':
            if (!$id || !webapiSnow($id) || !$return = $tutelar->discord->guilds->get('id', $id)->channels) return webapiFail('guild_id', $id);
            break;

        case 'members':
            if (!$id || !webapiSnow($id) || !$return = $tutelar->discord->guilds->get('id', $id)->members) return webapiFail('guild_id', $id);
            break;

        case 'emojis':
            if (!$id || !webapiSnow($id) || !$return = $tutelar->discord->guilds->get('id', $id)->emojis) return webapiFail('guild_id', $id);
            break;

        case 'invites':
            if (!$id || !webapiSnow($id) || !$return = $tutelar->discord->guilds->get('id', $id)->invites) return webapiFail('guild_id', $id);
            break;

        case 'roles':
            if (!$id || !webapiSnow($id) || !$return = $tutelar->discord->guilds->get('id', $id)->roles) return webapiFail('guild_id', $id);
            break;

        case 'guildMember':
            if (!$id || !webapiSnow($id) || !$guild = $tutelar->discord->guilds->get('id', $id)) return webapiFail('guild_id', $id);
            if (!$id2 || !webapiSnow($id2) || !$return = $guild->members->get('id', $id2)) return webapiFail('user_id', $id2);
            break;

        case 'user':
            if (!$id || !webapiSnow($id) || !$return = $tutelar->discord->users->get('id', $id)) return webapiFail('user_id', $id);
            break;

        case 'userName':
            if (!$id || !$return = $tutelar->discord->users->get('name', $id))
                return webapiFail('user_name', $id);
            break;

        case 'reset':
            if (!$whitelisted) {
                $tutelar->logger->alert('API REJECT ' . $request->getServerParams()['REMOTE_ADDR']);
                return new \React\Http\Message\Response(501, ['Content-Type' => 'text/plain'], 'Reject');
            }
            execInBackground('git reset --hard origin/main');
            $return = 'fixing git';
            break;

        case 'pull':
            if (!$whitelisted) {
                $tutelar->logger->alert('API REJECT ' . $request->getServerParams()['REMOTE_ADDR']);
                return new \React\Http\Message\Response(501, ['Content-Type' => 'text/plain'], 'Reject');
            }
            execInBackground('git pull');
            $tutelar->logger->info('[GIT PULL]');
            $return = 'updating code';
            break;

        case 'update':
            if (!$whitelisted) {
                $tutelar->logger->alert('API REJECT ' . $request->getServerParams()['REMOTE_ADDR']);
                return new \React\Http\Message\Response(501, ['Content-Type' => 'text/plain'], 'Reject');
            }
            execInBackground('composer update');
            $tutelar->logger->info('[COMPOSER UPDATE]');
            $return = 'updating dependencies';
            break;

        case 'restart':
            if (!$whitelisted) {
                $tutelar->logger->alert('API REJECT ' . $request->getServerParams()['REMOTE_ADDR']);
                return new \React\Http\Message\Response(501, ['Content-Type' => 'text/plain'], 'Reject');
            }
            $tutelar->logger->info('[RESTART]');
            $return = 'restarting';
            $socket->close();
            $tutelar->discord->getLoop()->addTimer(5, function () use ($tutelar, $socket) {
                \restart();
                $tutelar->discord->close();
                die();
            });
            break;

        case 'lookup':
            if (!$whitelisted) {
                $tutelar->logger->alert('API REJECT ' . $request->getServerParams()['REMOTE_ADDR']);
                return new \React\Http\Message\Response(501, ['Content-Type' => 'text/plain'], 'Reject');
            }
            if (!$id || !webapiSnow($id) || !$return = $tutelar->discord->users->get('id', $id))
                return webapiFail('user_id', $id);
            break;

        case 'owner':
            if (!$whitelisted) {
                $tutelar->logger->alert('API REJECT ' . $request->getServerParams()['REMOTE_ADDR']);
                return new \React\Http\Message\Response(501, ['Content-Type' => 'text/plain'], 'Reject');
            }
            if (!$id || !webapiSnow($id))
                return webapiFail('user_id', $id);
            $return = false;
            if ($user = $tutelar->discord->users->get('id', $id)) { //Search all guilds the bot is in and check if the user id exists as a guild owner
                foreach ($tutelar->discord->guilds as $guild) {
                    if ($id == $guild->owner_id) {
                        $return = true;
                        break 1;
                    }
                }
            }
            break;

        case 'avatar':
            if (!$id || !webapiSnow($id)) {
                return webapiFail('user_id', $id);
            }
            if (!$user = $tutelar->discord->users->get('id', $id)) {
                $tutelar->discord->users->fetch($id)->done(
                    function ($user) {
                        $return = $user->avatar;
                        return new \React\Http\Message\Response(200, ['Content-Type' => 'text/plain'], $return);
                    }, function ($error) use ($id) {
                        return webapiFail('user_id', $id);
                    }
                );
                $return = 'https://cdn.discordapp.com/embed/avatars/'.rand(0,4).'.png';
            }else{
                $return = $user->avatar;
            }
            //if (!$return) return new \React\Http\Message\Response(($id ? 404 : 400), ['Content-Type' => 'text/plain'], (''));
            break;

        case 'avatars':
            $idarray = $data ?? array(); // $data contains POST data
            $results = [];
            $promise = $tutelar->discord->users->fetch($idarray[0])->then(function ($user) use (&$results) {
              $results[$user->id] = $user->avatar;
            });
            for ($i = 1; $i < count($idarray); $i++) {
                $promise->then(function () use (&$results, $idarray, $i, $tutelar) {
                return $tutelar->discord->users->fetch($idarray[$i])->then(function ($user) use (&$results) {
                    $results[$user->id] = $user->avatar;
                });
              });
            }
            $promise->done(function () use ($results) {
              return new \React\Http\Message\Response (200, ['Content-Type' => 'application/json'], json_encode($results));
            }, function () use ($results) {
              // return with error ?
              return new \React\Http\Message\Response(200, ['Content-Type' => 'application/json'], json_encode($results));
            });
            break;
        default:
            return new \React\Http\Message\Response(501, ['Content-Type' => 'text/plain'], 'Not implemented');
    }
    return new \React\Http\Message\Response(200, ['Content-Type' => 'text/json'], json_encode($return));
});
$webapi->listen($socket);
$webapi->on('error', function ($error) use ($tutelar) {
    $tutelar->logger->error('API error: ' . $error->getMessage());
});