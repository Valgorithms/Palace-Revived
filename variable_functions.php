<?php

/*
 * This file is a part of the Tutelar project.
 *
 * Copyright (c) 2022-present Valithor Obsidion <valithor@valzargaming.com>
 */

/*
 *
 * Ready Event
 *
*/

$on_ready = function (\Tutelar\Tutelar $tutelar)
{
    $tutelar->logger->info('logged in as ' . $tutelar->discord->user->username . '#' . $tutelar->discord->user->discriminator . ' (' . $tutelar->discord->id . ')');
    $tutelar->logger->info('------');
    if ($slash_init = $tutelar->functions['misc']['slash_init']) $slash_init($tutelar);
};

$slash_init = function (\Tutelar\Tutelar $tutelar)
{
    $discord = $tutelar->discord;
    // Creates commands if they don't already exist
    $discord->guilds['468979034571931648']->commands->freshen()->done(
        function ($commands) use ($discord) {
            if (! $commands->get('name', 'players')) {
                $command = new Discord\Parts\Interactions\Command\Command($discord, [
                    'name' => 'players',
                    'description' => 'Show Space Station 13 server information'
                ]);
                $commands->save($command);
            }
        }
    );

    $discord->application->commands->freshen()->done(
        function ($commands) use ($discord) {
            if (! $commands->get('name', 'invite')) {
                $command = new Discord\Parts\Interactions\Command\Command($discord, [
                        'name' => 'invite',
                        'description' => 'Bot invite link'
                ]);
                $commands->save($command);
            }
        }
    );
    
    $discord->listenCommand('invite', function ($interaction) use ($discord) {
        $interaction->respondWithMessage(Discord\Builders\MessageBuilder::new()->setContent($discord->application->getInviteURLAttribute('8')));
    });

    // register guild command `/players`
    $discord->listenCommand('players', function ($interaction) use ($tutelar, $discord) {
        if(! $serverinfo = file_get_contents($tutelar->files['serverinfo'])) return;        
        $data_json = json_decode($serverinfo);
        //include "../servers/serverinfo.php"; //$servers[1]["key"] = address / alias / port / servername
        
        $desc_string_array = array();
        $desc_string = "";
        $server_state = array();
        foreach ($data_json as $varname => $varvalue){ //individual servers
            $varvalue = json_encode($varvalue);
            ////if($GLOBALS['debug_echo']) echo "varname: " . $varname . PHP_EOL; //Index
            ////if($GLOBALS['debug_echo']) echo "varvalue: " . $varvalue . PHP_EOL; //Json
            $server_state["$varname"] = $varvalue;
            
            $desc_string = $desc_string . $varname . ": " . urldecode($varvalue) . "\n";
            ////if($GLOBALS['debug_echo']) echo "desc_string length: " . strlen($desc_string) . PHP_EOL;
            ////if($GLOBALS['debug_echo']) echo "desc_string: " . $desc_string . PHP_EOL;
            $desc_string_array[] = $desc_string ?? "null";
            $desc_string = "";
        }
        
        $server_index[0] = "TDM" . PHP_EOL;
        $server_url[0] = "byond://51.254.161.128:1714";
        $server_index[1] = "Nomads" . PHP_EOL;
        $server_url[1] = "byond://51.254.161.128:1715";
        $server_index[2] = "Persistence" . PHP_EOL;
        $server_url[2] = "byond://69.140.47.22:1717";
        $server_index[3] = "Blue Colony" . PHP_EOL;
        $server_url[3] = "byond://69.140.47.22:7777";
        $server_state_dump = array(); // new assoc array for use with the embed
        
        $embed = $discord->factory(\Discord\Parts\Embed\Embed::class);
        foreach ($server_index as $index => $servername){
            //if($GLOBALS['debug_echo']) echo "server_index key: $index";
            $assocArray = json_decode($server_state[$index], true);
            foreach ($assocArray as $key => $value){
                if($value) $value = urldecode($value);
                else $value = null;
                ////if($GLOBALS['debug_echo']) echo "$key:$value" . PHP_EOL;
                $playerlist = "";
                if($key/* && $value && ($value != "unknown")*/)
                    switch($key){
                        case "version": //First key if online
                            //$server_state_dump[$index]["Status"] = "Online";
                            $server_state_dump[$index]["Server"] = "<" . $server_url[$index] . "> " . PHP_EOL . $server_index[$index]/* . " **(Online)**"*/;
                            break;
                        case "ERROR": //First key if offline
                            //$server_state_dump[$index]["Status"] = "Offline";
                            $server_state_dump[$index]["Server"] = "" . $server_url[$index] . " " . PHP_EOL . $server_index[$index] . " (Offline)"; //Don't show offline
                            break;
                        case "host":
                            if( ($value == NULL) || ($value == "") ){
                                $server_state_dump[$index]["Host"] = "Taislin";
                            }elseif (strpos($value, 'Guest')!==false) { //Byond wasn't logged in at server start
                                $server_state_dump[$index]["Host"] = "ValZarGaming";
                            }else $server_state_dump[$index]["Host"] = $value;
                            break;
                        /*case "players":
                            $server_state_dump[$index]["Player Count"] = $value;
                            break;*/
                        case "age":
                            //"Epoch", urldecode($serverinfo[0]["Epoch"])
                            $server_state_dump[$index]["Epoch"] = $value;
                            break;
                        case "season":
                            //"Season", urldecode($serverinfo[0]["Season"])
                            $server_state_dump[$index]["Season"] = $value;
                            break;
                        case "map":
                            //"Map", urldecode($serverinfo[0]["Map"]);
                            $server_state_dump[$index]["Map"] = $value;
                            break;
                        case "roundduration":
                            $rd = explode (":", $value);
                            $remainder = ($rd[0] % 24);
                            $rd[0] = floor($rd[0] / 24);
                            if( ($rd[0] != 0) || ($remainder != 0) || ($rd[1] != 0) ){ //Round is starting
                                $rt = $rd[0] . "d " . $remainder . "h " . $rd[1] . "m";
                            }else{
                                $rt = null; //"STARTING";
                            }
                            $server_state_dump[$index]["Round Time"] = $rt;
                            //
                            break;
                        case "stationtime":
                            $rd = explode (":", $value);
                            $remainder = ($rd[0] % 24);
                            $rd[0] = floor($rd[0] / 24);
                            if( ($rd[0] != 0) || ($remainder != 0) || ($rd[1] != 0) ){ //Round is starting
                                $rt = $rd[0] . "d " . $remainder . "h " . $rd[1] . "m";
                            }else{
                                $rt = null; //"STARTING";
                            }
                            //$server_state_dump[$index]["Station Time"] = $rt;
                            break;
                        case "cachetime":
                            $server_state_dump[$index]["Cache Time"] = gmdate("F j, Y, g:i a", $value) . " GMT";
                        default:
                            if ((substr($key, 0, 6) == "player") && ($key != "players") ){
                                $server_state_dump[$index]["Players"][] = $value;
                                //$playerlist = $playerlist . "$varvalue, ";
                                //"Players", urldecode($serverinfo[0]["players"])
                            }
                            break;
                    }
            }
        }
        //Build the embed message
        ////if($GLOBALS['debug_echo']) echo "server_state_dump count:" . count($server_state_dump) . PHP_EOL;
        foreach ($server_index as $x => $temp){
            ////if($GLOBALS['debug_echo']) echo "x: " . $x . PHP_EOL;
            if(is_array($server_state_dump[$x]))
            foreach ($server_state_dump[$x] as $key => $value){ //Status / Byond / Host / Player Count / Epoch / Season / Map / Round Time / Station Time / Players
                if($key && $value)
                if(is_array($value)){
                    $output_string = implode(', ', $value);
                    $embed->addFieldValues($key . " (" . count($value) . ")", $output_string, true);
                }elseif($key == "Host"){
                    if(strpos($value, "(Offline") == false)
                    $embed->addFieldValues($key, $value, true);
                }elseif($key == "Cache Time"){
                    //$embed->addFieldValues($key, $value, true);
                }elseif($key == "Server"){
                    $embed->addFieldValues($key, $value, false);
                }else{
                    $embed->addFieldValues($key, $value, true);
                }
            }
        }
        //if($GLOBALS['debug_echo']) echo '[RESPONSE FOR LOOP DONE]' . PHP_EOL;
        //Finalize the embed
        $embed
            ->setColor(0xe1452d)
            ->setTimestamp()
            ->setFooter("Tutelar by Valithor#5947")
            ->setURL("");
        
        //if($GLOBALS['debug_echo']) echo '[SEND EMBED]' . PHP_EOL;
        $message = Discord\Builders\MessageBuilder::new()
            ->setContent('Players')
            ->addEmbed($embed);
        $interaction->respondWithMessage($message)->done(
        function ($success){
            //
        }, function ($error) {
            var_dump($error);
        });
    });
};

$status_changer_random = function (\Tutelar\Tutelar $tutelar)
{
    if(! $tutelar->files['status_path']) return $tutelar->logger->warning('status_path is not defined'.PHP_EOL);
    if($status_array = file($tutelar->files['status_path'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) {
        list($status, $type, $state) = explode('; ', $status_array[array_rand($status_array)]);
        $type = (int) $type;
    } else return $tutelar->logger->warning('unable to open file ' . $tutelar->files['status_path'].PHP_EOL);
    if(! $status) return $tutelar->logger->warning('unable to get status from ' . $tutelar->files['status_path'].PHP_EOL);
    
    $activity = new \Discord\Parts\User\Activity($tutelar->discord, [ //Discord status            
        'name' => $status,
        'type' => $type, //0, 1, 2, 3, 4 | Game/Playing, Streaming, Listening, Watching, Custom Status
    ]);
    if($status_changer = $tutelar->functions['misc']['status_changer'])
        $status_changer($tutelar->discord, $activity, $state);
};

$status_changer = function (\Discord\Discord $discord, $activity, $state = 'online')
{
    $discord->updatePresence($activity, false, $state);
};

$perm_check = function (\Discord\Discord $discord, array $required_perms, $member, \Discord\Parts\Channel\Channel $channel = null): bool
{
    $perms = $member->getPermissions($channel); // @see https://github.com/discord-php/DiscordPHP/blob/master/src/Discord/Parts/Permissions/RolePermission.php
    foreach ($required_perms as $perm)
        if($perms[$perm]) return true;
    return false;
};

$timeout = function ($member, ?string $duration = '6 hours', ?string $reason = null) {
    $member->timeoutMember(new \Carbon\Carbon($duration), $reason)->done(
        function () {
            // ...
        }, function ($error) {
            echo $error . PHP_EOL;
        }
    );
};

/*
 *
 * Message Event
 *
 */

$stats = function (\Tutelar\Tutelar $tutelar, $message) {
	if($s = $tutelar->stats) $s->handle($message);
};

$on_message = function (\Tutelar\Tutelar $tutelar, $message)
{
    if(! $message->content) return; //Don't process message without text
    $message_content = '';
    $message_content_lower = '';
    $called = false;
    foreach($tutelar->command_symbol as $symbol) {
        if(str_starts_with($message->content, $symbol)) {
            $message_content = trim(substr($message->content, strlen($symbol)));
            $message_content_lower = strtolower($message_content);
            $called = true;
        }
    }
    
    if ($called) {
        echo "[CALLED] Message: `$message_content`" . PHP_EOL;
        echo '[guild_id] ' . $message->guild_id . PHP_EOL;
    }
    
    //if($message->guild->owner_id != $tutelar->owner_id) return; //Only process commands from a guild that Valithor owns
    if($any_message = $tutelar->functions['misc']['any_message'])
        $any_message($tutelar, $message, $message_content, $message_content_lower);
    if(! $message->guild_id && $direct_message = $tutelar->functions['misc']['direct_message'])
        $direct_message($tutelar, $message, $message_content, $message_content_lower);
    if(! $called) return;
    
    if($any_called_message = $tutelar->functions['misc']['any_called_message'])
        $any_called_message($tutelar, $message, $message_content, $message_content_lower);
    if($message->guild_id && $guild_message = $tutelar->functions['misc']['guild_message'])
        $guild_message($tutelar, $message, $message_content, $message_content_lower);
};

$direct_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower)
{
    if($message->user_id == $tutelar->owner_id && $debug_direct_message = $tutelar->functions['misc']['debug_direct_message'])
        $debug_direct_message($tutelar, $message, $message_content, $message_content_lower);
};

$debug_direct_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower)
{
    //
};

$guild_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower)
{
    echo '[GUILD MESSAGE]' . PHP_EOL;
    if($message->user_id == $tutelar->owner_id && $debug_guild_message = $tutelar->functions['misc']['debug_guild_message'])
        $debug_guild_message($tutelar, $message, $message_content, $message_content_lower);
    if(($message->guild->owner_id == $message->user_id) && $owner_message = $tutelar->functions['misc']['owner_message'])
        $owner_message($tutelar, $message, $message_content, $message_content_lower);
    if($perm_check = $tutelar->functions['misc']['perm_check']) {
        if($perm_check($tutelar->discord, ['administrator', 'ban_members'], $message->member) && $admin_message = $tutelar->functions['misc']['admin_message'])
            $admin_message($tutelar, $message, $message_content, $message_content_lower);
        if($perm_check($tutelar->discord, ['moderate_members'], $message->member) && $moderator_message = $tutelar->functions['misc']['moderator_message'])
            $moderator_message($tutelar, $message, $message_content, $message_content_lower);
    }
};

$debug_guild_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower)
{
    //
};

$owner_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower)
{
    //
};

$admin_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower)
{
    //
};

$moderator_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower)
{
    echo '[MODERATOR]' . PHP_EOL;
    if(str_starts_with($message_content_lower, 'timeout')) {
        if ($timeout = $tutelar->functions['misc']['timeout']) {
            if(! empty($array = GetMentions($message_content))) {
                foreach ($array as $id) {
                    echo "[TIMEOUT] $id" . PHP_EOL;
                    $member = $message->guild->members->get('id', $id);
                    $timeout($member/*, $duration = '6 hours', $reason*/);
                }
                $message->react("👍");
            }
        }
    }
};

$any_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower)
{
    if($message->user_id == $tutelar->owner_id && $debug_any_message = $tutelar->functions['misc']['debug_any_message'])
        $debug_any_message($tutelar, $message, $message_content, $message_content_lower);
};

$debug_any_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower)
{
    //
};

$any_called_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower)
{
    if($message->user_id == $tutelar->owner_id && $debug_any_called_message = $tutelar->functions['misc']['debug_any_called_message'])
        $debug_any_called_message($tutelar, $message, $message_content, $message_content_lower);
    if($message_content_lower == 'ping') return $message->reply('Pong!');
    if($message_content_lower == 'invite') return $message->reply($discord->application->getInviteURLAttribute('8'));
    if($message_content_lower == 'help') return $message->reply('Not yet implemented');
};

$debug_any_called_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower)
{
    //Tutelar
     if($message_content_lower == 'restart') {
        \restart();
        $tutelar->discord->close();
        return;
    }
    
    //DiscordPHP
    if($message_content_lower == 'debug channel invite') {
        $message->channel->createInvite([
            'max_age' => 60, // 1 minute
            'max_uses' => 5, // 5 uses
        ])->done(function ($invite) use ($message) {
            $url = 'https://discord.gg/' . $invite->code;
            $message->reply("Invite URL: $url");
        });
        return;
    }
    if($message_content_lower == 'debug guild names') {
        $guildstring = "";
        foreach($discord->guilds as $guild) $guildstring .= "[{$guild->name} ({$guild->id}) :".count($guild->members)." <@{$guild->owner_id}>] \n";
        foreach (str_split($guildstring, 2000) as $piece) $message->reply($piece);
        return;
    }
    
    //TwitchPHP
    if(str_starts_with($message_content_lower, 'join #')) return $tutelar->twitch->joinChannel(explode(' ', str_replace('join #', "", $message_content_lower))[0]);
	if(str_starts_with($message_content_lower, 'leave #')) return $tutelar->twitch->leaveChannel(explode(' ', str_replace('leave #', "", $message_content_lower))[0]);
};

/*
 *
 * ReactPHP/http
 *
 */

$browser_get = function (\Tutelar\Tutelar $tutelar, string $url, array $headers = [], bool $curl = true)
{
    if( ! $curl && $browser = $tutelar->browser) return $browser->get($url, $headers);
    
    $ch = curl_init(); //create curl resource
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //return the transfer as a string
    $result = curl_exec($ch);
    return $data; //string
};

$browser_post = function (\Tutelar\Tutelar $tutelar, string $url, array $headers = ['Content-Type' => 'application/x-www-form-urlencoded'], array $data = [], bool $curl = true)
{
    //Send a POST request to 69.140.47.22:8081/discord2ckey/ with POST['id'] = $id
    if( ! $curl && $browser = $tutelar->browser) return $browser->post($url, $headers, http_build_query($data));

    $ch = curl_init(); //create curl resource
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //return the transfer as a string
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $result = curl_exec($ch);
    return json_decode($result, true); //Array
};