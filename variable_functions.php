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
function dbInsert(\Tutelar\Tutelar $tutelar, string $table, array $data)
{
    $db = $tutelar->mysqli[0];
    $data_clean = [];
    foreach ($data as $d) $data_clean[] = mysqli_real_escape_string($db, $d);
    
    $sql = 'INSERT INTO `' . $table . '` (`' . implode('`, `', array_keys($data_clean)) . '`) VALUES (';
    $sql .= substr(str_repeat('?,', count($data_clean)), 0, -1) . ')';

    $stmt = $db->prepare($sql);
    $stmt->bind_param(str_repeat('s', count($data_clean)), array_values($data_clean));
    $stmt->execute();
    $stmt->store_result();

    return ['insert_id' => (isset($stmt->insert_id) ? $db->insert_id : false)];
};


$set_ips = function (\Tutelar\Tutelar $tutelar)
{
    $civ_ip = gethostbyname('www.civ13.com') ?? '51.254.161.128';
    $external_ip = file_get_contents('http://ipecho.net/plain') ?? '69.244.83.231';
    $tutelar->ips = [
        'nomads' => $civ_ip,
        'tdm' => $civ_ip,
        'vzg' => $external_ip,
    ];
    $tutelar->ports = [
        'nomads' => '1715',
        'tdm' => '1714',
        'persistence' => '7777',
        'bc' => '1717', 
        'df13' => '7778',
    ];
};

$status_changer = function (\Discord\Discord $discord, $activity, $state = 'online')
{
    $discord->updatePresence($activity, false, $state);
};
$status_changer_random = function (\Tutelar\Tutelar $tutelar) use ($status_changer)
{
    if (!$tutelar->files['statuslist']) return $tutelar->logger->warning('status is not defined');
    if ($status_array = file($tutelar->files['statuslist'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) {
        list($status, $type, $state) = explode('; ', $status_array[array_rand($status_array)]);
        $type = (int) $type;
    } else return $tutelar->logger->warning("unable to open file `{$tutelar->files['statuslist']}`");
    if (!$status) return $tutelar->logger->warning("unable to get status from `{$tutelar->files['statuslist']}`");
    
    $activity = new \Discord\Parts\User\Activity($tutelar->discord, [ //Discord status            
        'name' => $status,
        'type' => $type, //0, 1, 2, 3, 4 | Game/Playing, Streaming, Listening, Watching, Custom Status
    ]);
    $status_changer($tutelar->discord, $activity, $state);
};

$perm_check = function (array $required_perms, $member, \Discord\Parts\Channel\Channel $channel = null): bool
{
    foreach ($required_perms as $perm) if ($member->getPermissions($channel)[$perm]) return true; // @see https://github.com/discord-php/DiscordPHP/blob/master/src/Discord/Parts/Permissions/RolePermission.php
    return false;
};

$timeout = function (\Tutelar\Tutelar $tutelar, $member, ?string $duration = '6 hours', ?string $reason = null) {
    $member->timeoutMember(new \Carbon\Carbon($duration), $reason)->done(
        function () {
            // ...
        }, function ($error) use ($tutelar) {
            $tutelar->logger->warning('Error timing out member: ' . $error->getMessage());
        }
    );
};
/*
 *
 * Message Event
 *
 */

$stats = function (\Tutelar\Tutelar $tutelar, $message) {
	if ($s = $tutelar->stats) $s->handle($message);
}; 

$debug_direct_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower)
{
    //
};
$direct_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower) use ($debug_direct_message)
{
    if ($message->user_id == $tutelar->owner_id) $debug_direct_message($tutelar, $message, $message_content, $message_content_lower);
};

$owner_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower)
{
    //
};
$manager_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower)
{
    if (str_starts_with($message_content_lower, 'reset')) {
        $message_content = trim(substr($message_content, strlen('reset')));
        $message_content_lower = strtolower($message_content);
        if (str_starts_with($message_content_lower, $message->guild_id)) { // Deletes the current server configurations and recreates them using the default template
            unset($tutelar->discord_config[$message->guild_id]);
            $tutelar->SetConfigTemplate($message->guild, $tutelar->discord_config);
            return $message->react("👍");
        } else return $message->reply('If you sure you want to reset the configurations for the entire server, please confirm by using the command: <@' . $tutelar->discord->id . '> reset ' . $message->guild_id);
    }
    if (str_starts_with($message_content_lower, 'save')) {
        $tutelar->saveConfig();
        return $message->react("👍");
    }
    if ($message_content_lower == 'setup') return $message->reply(\Discord\Builders\MessageBuilder::new()->addFileFromContent('discord_config.txt', json_encode($tutelar->discord_config[$message->guild_id], JSON_PRETTY_PRINT))); //Provide current configurations
    if (str_starts_with($message_content_lower, 'setup')) {
        $message_content = trim(substr($message_content, strlen('setup')));
        $message_content_lower = strtolower($message_content);
        
        //Correct format is "@Tutelar setup command @mention/@#channel/@&role key"
        
        if (str_starts_with($message_content_lower, 'role')) {
            $message_content = trim(substr($message_content, strlen('role')));
            $message_content_lower = strtolower($message_content);
            
            if (str_starts_with($message_content_lower, 'add')) {
                $message_content = trim(substr($message_content, strlen('add')));
                $message_content_lower = strtolower($message_content);
                $array = explode (' ', $message_content);
                $emoji = $array[sizeof($array)-1];
                $name = '';
                for($x=0; $x<=sizeof($array)-2; $x++) $name .= $array[$x] . ' ';
                $name = trim($name);
                if (!$name) return $message->reply('Missing name parameter! Creating new custom roles should be done in the format of @' . $tutelar->discord->username . ' add role_name unicode_emoji');
                $keys = array_keys($tutelar->discord_config[$message->guild_id]['reaction_roles']);
                foreach ($keys as $key) if (str_starts_with($key, 'custom'))
                    foreach ($tutelar->discord_config[$message->guild_id]['reaction_roles'][$key]['roles'] as $k => $arr)
                        if ($arr['name'] == $name) return $message->reply("A custom role with the name `$name` already exists in the config");
                if (!$emoji) return $message->reply('Missing emoji parameter! Creating new custom roles should be done in the format of @' . $tutelar->discord->username . ' add role_name unicode_emoji');
                $message->react($emoji)->done(
                    function ($reaction) use ($tutelar, $message, $name, $emoji) { //Unicode should be valid, so create the role
                        $index = sizeof($tutelar->discord_config[$message->guild_id]['reaction_roles']['custom']['roles']);
                        $increment = '';
                        if ($index / 20 == 1) do {
                            $increment++;
                            $index = sizeof($tutelar->discord_config[$message->guild_id]['reaction_roles']["custom$increment"]['roles']);
                            $increment = floor($index);
                        } while ($index / 20 == 1);
                        if ($r = $message->guild->roles->get('name', $name)) {
                            foreach ($tutelar->discord_config[$message->guild_id]['reaction_roles']["custom$increment"]['roles'] as $rk => $vk) if ($vk['name'] == $name) {
                                $tutelar->discord_config[$message->guild_id]['reaction_roles']["custom$increment"]['roles'][$rk]['id'] = $r->id;
                                $tutelar->discord_config[$message->guild_id]['reaction_roles']["custom$increment"]['roles'][$rk]['name'] = $name;
                                $tutelar->discord_config[$message->guild_id]['reaction_roles']["custom$increment"]['roles'][$rk]['emoji'] = $emoji;
                                return $tutelar->saveConfig();
                            }
                            $tutelar->discord_config[$message->guild_id]['reaction_roles']["custom$increment"]['roles'][$index]['id'] = $r->id;
                            $tutelar->discord_config[$message->guild_id]['reaction_roles']["custom$increment"]['roles'][$index]['name'] = $name;
                            $tutelar->discord_config[$message->guild_id]['reaction_roles']["custom$increment"]['roles'][$index]['emoji'] = $emoji;
                            return $tutelar->saveConfig();
                        }
                        $tutelar->discord_config[$message->guild_id]['reaction_roles']["custom$increment"]['roles'][$index]['name'] = $name;
                        $tutelar->discord_config[$message->guild_id]['reaction_roles']["custom$increment"]['roles'][$index]['emoji'] = $emoji;
                        $tutelar->discord_config[$message->guild_id]['reaction_roles']["custom$increment"]['roles'][$index]['color'] = $tutelar->discord_config[$message->guild_id]['reaction_roles']['custom']['default_color'];
                        $tutelar->discord_config[$message->guild_id]['reaction_roles']["custom$increment"]['roles'][$index]['hoist'] = false;
                        $tutelar->discord_config[$message->guild_id]['reaction_roles']["custom$increment"]['roles'][$index]['mentionable'] = false;
                        $tutelar->discord_config[$message->guild_id]['reaction_roles']["custom$increment"]['roles'][$index]['permissions'] = 0;
                        $role_template = new \Discord\Parts\Guild\Role($tutelar->discord, $tutelar->discord_config[$message->guild_id]['reaction_roles']["custom$increment"]['roles'][$index]);
                        $message->guild->createRole($role_template->getUpdatableAttributes())->done(
                            function ($role) use ($tutelar, $message, $index, $increment) {
                                $tutelar->logger->info('Created new custom role id: ' . $role->id);
                                $tutelar->discord_config[$message->guild_id]['reaction_roles']["custom$increment"]['roles'][$index]['id'] = $role->id;
                                $tutelar->saveConfig();
                            },
                            function ($error) use ($tutelar) {
                                $tutelar->logger->warning('Error creating custom role: ' . $error->getMessage());
                            }
                        );
                    }, function ($error) use ($tutelar, $message) { //Unicode isn't valid
                        $tutelar->logger->warning("Error reacting to message: {$error->getMessage()}");
                        $message->reply('The command must end in a unicode emoji');
                    }
                );
            }
            if (str_starts_with($message_content_lower, 'remove')) {
                $name = trim(substr($message_content, strlen('remove')));
                if (!$name) return $message->reply('Missing name parameter! Creating new custom roles should be done in the format of @' . $tutelar->discord->username . ' add role_name unicode_emoji');
                $keys = array_keys($tutelar->discord_config[$message->guild_id]['reaction_roles']);
                foreach ($keys as $key) if (str_starts_with($key, 'custom'))
                    foreach ($tutelar->discord_config[$message->guild_id]['reaction_roles'][$key]['roles'] as $k => $arr)
                        if ($arr['name'] == $name) {
                            unset($tutelar->discord_config[$message->guild_id]['reaction_roles'][$key]['roles'][$k]);
                            $message->react("👍");
                            return $tutelar->saveConfig();
                        }
                return $message->reply("A custom role with the name of `$name` was not found in the config");
            }
        }
        
        if (str_starts_with($message_content_lower, 'toggle')) {
            $message_content = trim(substr($message_content, strlen('toggle')));
            $message_content_lower = strtolower($message_content);
            if (!$message_content_lower || is_null($tutelar->discord_config[$message->guild_id]['toggles'][$message_content_lower])) return $message->reply('Invalid toggle configuration `' . $message_content_lower . '`! Valid options are ' . implode(', ', array_keys($tutelar->discord_config[$message->guild_id]['toggles'])));
            $tutelar->discord_config[$message->guild_id]['toggles'][$message_content_lower] = !($val = $tutelar->discord_config[$message->guild_id]['toggles'][$message_content_lower]);
            $tutelar->logger->info('Toggled ' . $message_content_lower . ': ' . ($tutelar->discord_config[$message->guild_id]['toggles'][$message_content_lower] ? 'True' : 'False'));
            $message->reply('Toggled ' .  $message_content_lower . ' ' . ($tutelar->discord_config[$message->guild_id]['toggles'][$message_content_lower] ? 'on' : 'off'));
            return $tutelar->saveConfig();
        }
        
        if (str_starts_with($message_content_lower, 'channel')) {
            $message_content = trim(substr($message_content, strlen('channel')));
            $message_content_lower = strtolower($message_content);
            preg_match('/<#([0-9]*)>/', $message_content_lower, $matches);
            if (!isset($matches[1]) || !$message->guild->channels->get('id', $matches[1])) return $message->reply('Channel not found! Please mention the channel in the format of <#channel_id>');
            $message_content_lower = trim(substr($message_content_lower, strlen($matches[0])));
            if (!isset($tutelar->discord_config[$message->guild_id]['channels'][$message_content_lower])) return $message->reply('Invalid channel configuration `' . $message_content_lower . '`! Valid options are ' . implode(', ', array_keys($tutelar->discord_config[$message->guild_id]['channels'])));
            $tutelar->discord_config[$message->guild_id]['channels'][$message_content_lower] = $matches[1];
            $tutelar->logger->info('Updated ' . $message_content_lower . ' channel id ' . $matches[1]);
            $message->react("👍");
            return $tutelar->saveConfig();
        }
        
        if (str_starts_with($message_content_lower, 'message'))  {
            $message_content = trim(substr($message_content, strlen('message')));
            $target = $message_content_lower = strtolower($message_content);
            if (!isset($tutelar->discord_config[$message->guild_id]['reaction_roles'][$target])) return $message->reply('Invalid reaction role `' . $target . '`! Valid options are ' . implode(', ', array_keys($tutelar->discord_config[$message->guild_id]['reaction_roles'])));
            foreach (array_keys($tutelar->discord_config[$message->guild_id]['reaction_roles']) as $key) if ($key == $target) { //???
                $emojis = [];
                $message_content = $tutelar->discord_config[$message->guild_id]['reaction_roles'][$key]['message_content'];
                foreach ($tutelar->discord_config[$message->guild_id]['reaction_roles'][$key]['roles'] as $arr => $val) {
                    if (!isset($val['emoji'])) continue;
                    $emojis[] = $val['emoji'];
                    $message_content .= PHP_EOL . $val['emoji'] . ' : ' . $val['name'];
                    //Create roles for each emoji if they don't exist already, then add the reactions
                    if (! $role = $message->guild->roles->get('name', $val['name'])) {
                        $role_template = new \Discord\Parts\Guild\Role($tutelar->discord,
                            [
                                'name' => $val['name'],
                                'color' => $val['color'] ?? $tutelar->discord_config[$message->guild_id]['reaction_roles'][$key]['default_color'],
                                'hoist' => $val['hoist'],
                                'mentionable' => $val['mentionable'],
                                'permissions' => $val['permissions']
                            ]
                        );
                        $message->guild->createRole($role_template->getUpdatableAttributes())->done(
                            function ($role) use ($tutelar, $message, $key, $arr, $val) {
                                $tutelar->logger->info('Created new ' . $val['name'] . ' role id ' . $role->id);
                                $val['id'] = $role->id;
                                $tutelar->discord_config[$message->guild_id]['reaction_roles'][$key]['roles'][$arr] = $val;
                            },
                            function ($error) use ($tutelar) {
                                $tutelar->logger->warning('Error creating role! ' . $error->getMessage());
                            }
                        );
                    } else {
                        $tutelar->logger->info('Updated ' . $val['name'] . ' role id ' . $role->id);
                        $val['id'] = $role->id;
                        $tutelar->discord_config[$message->guild_id]['reaction_roles'][$key]['roles'][$arr] = $val;
                    }
                }
                $message->channel->sendMessage($message_content)->done( //Sending message not firing
                    function ($new_message) use ($tutelar, $message, $emojis, $key) {
                        $tutelar->reactionLoop($new_message, $emojis);
                        $tutelar->discord_config[$message->guild_id]['reaction_roles'][$key]['id'] = $new_message->id;
                        $tutelar->saveConfig();
                        $message->delete();
                    },
                    function ($error) use ($tutelar) {
                        $tutelar->logger->warning('Error sending message: ' . $error->getMessage());
                    }
                );
                return;
            }
        }
    }
};
$admin_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower)
{
    //
};
$moderator_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower) use ($timeout)
{
    if (str_starts_with($message_content_lower, 'timeout')) {
        if (empty($array = \GetMentions($message_content))) return $message->reply('You need to <@mention> at least one user to time out');
        foreach ($array as $id) $timeout($tutelar, $message->guild->members->get('id', $id)/*, $duration = '6 hours', $reason*/);
        return $message->react("🤐");
    }
    if (str_starts_with($message_content_lower, 'clear')) {
        if (! $message_content = trim(substr($message_content, strlen('clear')))) return $message->channel->limitDelete(100);
        if (is_numeric($message_content)) return $message->channel->limitDelete($message_content);
        return $message->reply ('Invalid parameter! Please include the nubmer of messages to delete');
    }
    //TwitchPHP
    if (str_starts_with($message_content_lower, 'join #')) if ($tutelar->twitch->joinChannel(trim(str_replace('join #', '', $message_content_lower)), $message->guild_id, $message->channel_id)) return $message->react("👍"); else return $message->react("👎");
	if (str_starts_with($message_content_lower, 'leave #')) if ($tutelar->twitch->leaveChannel(trim(str_replace('leave #', '', $message_content_lower)), $message->guild_id, $message->channel_id)) return $message->react("👍"); else return $message->react("👎");
};
$debug_guild_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower)
{
  if ($message_content_lower == 'save') return $tutelar->saveConfig() ? $message->react("👍") : $message->react("👎");
};

$whois = function (\Tutelar\Tutelar $tutelar, \Discord\Parts\User\User $user, $guild_id = null)
{
    $servers = [];
    foreach ($tutelar->discord->guilds as $guild) if ($member = $guild->members->find(fn ($m) => $m->id == $user->id)) $servers[] = $member->guild->name;
    $embed = new \Discord\Parts\Embed\Embed($tutelar->discord);
    $embed
        ->setTitle("{$user->displayname} ({$user->id})")
        ->setColor(0xe1452d)
        ->addFieldValues('Avatar', "[Link]({$user->avatar})", true)
        ->addFieldValues('Account Created', '<t:' . floor($user->createdTimestamp()) . ':R>', true)
        ->setThumbnail($user->avatar)
        ->setTimestamp()
        ->setURL('');
    if (isset($tutelar->owner_id) && $owner = $tutelar->discord->users->get('id', $tutelar->owner_id)) $embed->setFooter(($tutelar->github ?  "{$tutelar->github}" . PHP_EOL : '') . "{$tutelar->discord->username} by {$owner->displayname}");
    if ($guild_id && $member = $tutelar->discord->guilds->get('id', $guild_id)->members->get('id', $user->id)) $embed->addFieldValues('Joined', '<t:' . floor($member->joined_at->timestamp) . ':R>', true);
    if (!empty($servers)) $embed->addFieldValues('Shared Servers', implode(PHP_EOL, $servers));
    return $embed;
};

$guild_called_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower) use ($whois, $perm_check, $debug_guild_message, $owner_message, $manager_message, $admin_message, $moderator_message)
{
    if (str_starts_with($message_content_lower, 'whois')) {
        if (is_numeric($message_content = trim(substr($message_content, strlen('whois')))) && $member = $message->guild->members->get('id', $message_content)) return $message->channel->sendEmbed($whois($tutelar, $member->user, $message->guild_id));
        if (empty($arr = \GetMentions($message_content))) return $message->react("👎");
        if (!is_numeric($arr[0])) return $message->react("👎");
        if ($member = $message->guild->members->get('id', $arr[0])) return $message->channel->sendEmbed($whois($tutelar, $member->user, $message->guild_id));
        $tutelar->discord->users->fetch($arr[0])->done(
            function ($user) use ($tutelar, $message, $whois) { $message->channel->sendEmbed($whois($tutelar, $user, $message->guild_id)); },
            function ($error) use ($message) { $message->react("👎"); }
        );
    }
    
    if ($message->user_id == $tutelar->owner_id) {
        $debug_guild_message($tutelar, $message, $message_content, $message_content_lower);
        $owner_message($tutelar, $message, $message_content, $message_content_lower);
        $manager_message($tutelar, $message, $message_content, $message_content_lower);
        $admin_message($tutelar, $message, $message_content, $message_content_lower);
        $moderator_message($tutelar, $message, $message_content, $message_content_lower);
        return;
    }
    if ($message->guild->owner_id == $message->user_id) $owner_message($tutelar, $message, $message_content, $message_content_lower);    
    if ($perm_check(['administrator', 'manage_guild'], $message->member)) $manager_message($tutelar, $message, $message_content, $message_content_lower);
    if ($perm_check(['administrator', 'ban_members'], $message->member)) $admin_message($tutelar, $message, $message_content, $message_content_lower);
    if ($perm_check(['administrator', 'moderate_members'], $message->member)) $moderator_message($tutelar, $message, $message_content, $message_content_lower);
};
$twitch_relay = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower): void
{
    if ($channels = $tutelar->twitch->getChannels()) foreach ($channels as $twitch_channel => $arr) foreach ($arr as $guild_id => $channel_id) {
        if (!($message->guild_id == $guild_id && $message->channel_id == $channel_id)) continue;
        $channel = '';
        if (str_starts_with($message_content_lower, "#$twitch_channel")) {
            $message_content = trim(substr($message_content, strlen("#$twitch_channel")));
            $channel = $twitch_channel;
        }
        //else $channel = $tutelar->twitch->getLastChannel(); //Only works reliably if only relaying chat for a single Twitch chat
        if (! $channel) continue;
        if (! $tutelar->twitch->sendMessage("{$message->author->displayname} => $message_content", $channel)) $tutelar->logger->warning('[FAILED TO SEND MESSAGE TO TWITCH]');
    }
};
$guild_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower) use ($twitch_relay)
{
    echo '[GUILD_MESSAGE]' . PHP_EOL;
    if ($message->guild_id == $tutelar->owner_guild_id && $message->channel->type == 5) $message->crosspost();
    
    if ($message->user_id != $tutelar->discord->id) $twitch_relay($tutelar, $message, $message_content, $message_content_lower);
};

$any_debug_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower) use ($perm_check)
{
    if ($message_content_lower == 'guilds') {
        $string = '';
        foreach ($tutelar->discord->guilds as $guild) $string .= "{$guild->name} ({$guild->id}) [{$guild->member_count}]" . PHP_EOL;
        return $message->reply($string);
    }
    if (str_starts_with($message_content_lower, 'guild leave ')) {
        $tutelar->discord->guilds->get('id', explode(' ', str_replace('guild leave ', '', $message_content_lower))[0])->leave()->done(
            function () use ($message) { $message->react("👍"); },
            function () use ($message) { $message->react("👎"); }
        );
    }
    if (str_starts_with($message_content_lower, 'guild invite ')) {
        if (!is_numeric($id = explode(' ', str_replace('guild invite ', '', $message_content_lower))[0])) return $message->react("👎");
        if (!$guild = $tutelar->discord->guilds->get('id', $id)) return $message->react("👎");
        if ($guild->vanity_url_code) return $message->channel->sendMessage("{$guild->name} ({$guild->id}) https://discord.gg/{$guild->vanity_url_code}");
        if (!$guild->members->get('id', $tutelar->discord->id) || ! $perm_check(['administrator', 'manage_guild'], $guild->members->get('id', $tutelar->discord->id))) return;
        foreach ($guild->invites as $invite) if ($invite->code) return $message->channel->sendMessage("{$guild->name} ({$guild->id}) https://discord.gg/{$invite->code}");
        
        foreach ($guild->channels as $channel) if ($channel->type != 4) return $channel->createInvite([
            'max_age' => 60, // 1 minute
            'max_uses' => 1, // 1 use
        ])->done(
            function ($invite) use ($message, $guild) { $message->reply("{$guild->name} ({$guild->id}) https://discord.gg/{$invite->code}"); },
            function () use ($message) { $message->react("❌"); }
        );
    }
};
$any_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower) use ($any_debug_message)
{
    if ($message->user_id == $tutelar->owner_id) $any_debug_message($tutelar, $message, $message_content, $message_content_lower);
};

$any_called_debug_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower)
{
    //Tutelar
     if ($message_content_lower == 'restart') {
        \restart();
        $tutelar->discord->close();
        return;
    }
    
    //DiscordPHP
    if ($message_content_lower == 'debug channel invite') {
        $message->channel->createInvite([
            'max_age' => 60, // 1 minute
            'max_uses' => 5, // 5 uses
        ])->done(function ($invite) use ($message) {
            $url = 'https://discord.gg/' . $invite->code;
            $message->reply("Invite URL: $url");
        });
        return;
    }
    if ($message_content_lower == 'debug guild names') { //Maybe upload as a file instead?
        $guildstring = '';
        foreach ($tutelar->discord->guilds as $guild) $guildstring .= "[{$guild->name} ({$guild->id}) :{count($guild->members)} <@{$guild->owner_id}>]" . PHP_EOL;
        foreach (str_split($guildstring, 2000) as $piece) $message->reply($piece);
        return;
    }
    
    //TwitchPHP
    //if (str_starts_with($message_content_lower, 'join #')) if ($tutelar->twitch->joinChannel(trim(str_replace('join #', '', $message_content_lower)), $message->guild_id, $message->channel_id)) return $message->react("👍"); else return $message->react("👎");
	//if (str_starts_with($message_content_lower, 'leave #')) if ($tutelar->twitch->leaveChannel(trim(str_replace('leave #', '', $message_content_lower)), $message->guild_id, $message->channel_id)) return $message->react("👍"); else return $message->react("👎");
};
$any_called_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower) use ($any_called_debug_message)
{
    if ($message->user_id == $tutelar->owner_id) $any_called_debug_message($tutelar, $message, $message_content, $message_content_lower);
    if ($message_content_lower == 'ping') return $message->reply('Pong!');
    if ($message_content_lower == 'invite') return $message->reply($tutelar->discord->application->getInviteURLAttribute('8'));
    if ($message_content_lower == 'help') return $message->reply('Not yet implemented');
    
    //Miscellaneous Discord stuff
    if (str_starts_with($message_content_lower, 'avatar')) {
        $message_content_lower = trim(str_replace(['<@!', '<@', '>'], '', substr($message_content_lower, strlen('avatar'))));
        if (! $message_content_lower) return $message->reply($message->user->avatar);
        if (is_numeric($message_content_lower)) {
            return $tutelar->discord->users->fetch($message_content_lower)->done(
                function ($user) use ($message) {
                    return $message->reply($user->avatar);
                },
                function ($error) use ($message) {
                    return $message->reply('Unable to locate user!');
                }
            );
        }
        return $message->reply('Invalid parameter! Please include the ID of the user you want to see the avatar of.');
    }
};


$on_message = function (\Tutelar\Tutelar $tutelar, $message) use ($any_message, $direct_message, $guild_message, $any_called_message, $guild_called_message)
{
    if (!$message->content) return; //Don't process message without text
    $message_content = $message->content;
    $message_content_lower = strtolower($message->content);
    $called = false;
    //$tutelar->logger->debug('[MESSAGE] {' . $message->guild_id . '/' . $message->channel_id . '} ' . $message->author->displayname . ': ' . $message->content);
    foreach ($tutelar->command_symbol as $symbol) if (str_starts_with($message->content, $symbol)) {
        $message_content = trim(substr($message_content, strlen($symbol)));
        $message_content_lower = strtolower($message_content);
        $called = true;
        $tutelar->logger->debug("{$message->guild_id} - `$message_content`");
        break;
    }
    
    //if ($message->guild->owner_id != $tutelar->owner_id) return; //Only process commands from a guild that Valithor owns
    
    $any_message($tutelar, $message, $message_content, $message_content_lower);
    $direct_message($tutelar, $message, $message_content, $message_content_lower);
    $guild_message($tutelar, $message, $message_content, $message_content_lower);
    if (!$called) return;
    
    $any_called_message($tutelar, $message, $message_content, $message_content_lower);
    $guild_called_message($tutelar, $message, $message_content, $message_content_lower);
};

/*
 *
 * ReactPHP/http
 *
 */

$browser_get = function (\Tutelar\Tutelar $tutelar, string $url, array $headers = [], bool $curl = true)
{
    if ( ! $curl && $browser = $tutelar->browser) return $browser->get($url, $headers);
    
    $ch = curl_init(); //create curl resource
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //return the transfer as a string
    $result = curl_exec($ch);
    return $result; //string
};
$browser_post = function (\Tutelar\Tutelar $tutelar, string $url, array $headers = ['Content-Type' => 'application/x-www-form-urlencoded'], array $data = [], bool $curl = true)
{
    //Send a POST request to civ13.valzargaming.com/discord2ckey/ with POST['id'] = $id
    if ( ! $curl && $browser = $tutelar->browser) return $browser->post($url, $headers, http_build_query($data));

    $ch = curl_init(); //create curl resource
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //return the transfer as a string
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $result = curl_exec($ch);
    return json_decode($result, true); //Array
};

$slash_init = function (\Tutelar\Tutelar $tutelar, $commands) use ($whois)
{   
    //if ($command = $commands->get('name', 'invite')) $commands->delete($command->id);
    if (!$commands->get('name', 'invite')) {
        $command = new \Discord\Parts\Interactions\Command\Command($tutelar->discord, [
            'name' => 'invite',
            'description' => 'Bot invite link'
        ]);
        $commands->save($command);
    }
    //listen for global commands
    $tutelar->discord->listenCommand('invite', function ($interaction) use ($tutelar) {
        $interaction->respondWithMessage(\Discord\Builders\MessageBuilder::new()->setContent($tutelar->discord->application->getInviteURLAttribute('8')));
    });

    // Creates commands if they don't already exist
    $tutelar->discord->guilds->get('id', '468979034571931648')->commands->freshen()->done(
        function ($commands) use ($tutelar) {
            //if ($command = $commands->get('name', 'players')) $commands->delete($command->id);
            if (! $commands->get('name', 'players')) {
                $command = new \Discord\Parts\Interactions\Command\Command($tutelar->discord, [
                    'name' => 'players',
                    'description' => 'Show Space Station 13 server information'
                ]);
                $commands->save($command);
            }
        }
    );
    $tutelar->discord->guilds->get('id', '807759102624792576')->commands->freshen()->done(
        function ($commands) use ($tutelar) {
            //if ($command = $commands->get('name', 'players')) $commands->delete($command->id);
            if (! $commands->get('name', 'players')) {
                $command = new \Discord\Parts\Interactions\Command\Command($tutelar->discord, [
                    'name' => 'players',
                    'description' => 'Show Space Station 13 server information'
                ]);
                $commands->save($command);
            }
        }
    );
    $tutelar->discord->listenCommand('players', function ($interaction) use ($tutelar) {
        if (!$data_json = json_decode(file_get_contents($tutelar->files['serverinfo']), true)) return $interaction->respondWithMessage('Unable to fetch serverinfo.json, webserver might be down');
        $server_info[0] = ['name' => 'TDM', 'host' => 'Taislin', 'link' => "<byond://{$tutelar->ips['tdm']}:{$tutelar->ports['tdm']}>"];
        $server_info[1] = ['name' => 'Nomads', 'host' => 'Taislin', 'link' => "<byond://{$tutelar->ips['nomads']}:{$tutelar->ports['nomads']}>"];
        $server_info[2] = ['name' => 'Persistence', 'host' => 'ValZarGaming', 'link' => "<byond://{$tutelar->ips['vzg']}:{$tutelar->ports['persistence']}>"];
        $server_info[3] = ['name' => 'Blue Colony', 'host' => 'ValZarGaming', 'link' => "<byond://{$tutelar->ips['vzg']}:{$tutelar->ports['bc']}>"];
        $server_info[4] = ['name' => 'Pocket Stronghold 13', 'host' => 'ValZarGaming', 'link' => "<byond://{$tutelar->ips['vzg']}:{$tutelar->ports['df13']}>"];
        
        $embed = new \Discord\Parts\Embed\Embed($tutelar->discord);
        foreach ($data_json as $server) {
            $server_info_hard = array_shift($server_info);
            if (array_key_exists('ERROR', $server)) continue;
            if (isset($server_info_hard['name'])) $embed->addFieldValues('Server', $server_info_hard['name'] . PHP_EOL . $server_info_hard['link'], false);
            if (isset($server_info_hard['host'])) $embed->addFieldValues('Host', $server_info_hard['host'], true);
            //Round time
            if (isset($server['roundduration'])) {
                $rd = explode(":", urldecode($server['roundduration']));
                $remainder = ($rd[0] % 24);
                $rd[0] = floor($rd[0] / 24);
                if ($rd[0] != 0 || $remainder != 0 || $rd[1] != 0) $rt = "{$rd[0]}d {$remainder}h {$rd[1]}m";
                else $rt = "STARTING";
                $embed->addFieldValues('Round Timer', $rt, true);
            }
            if (isset($server['map'])) $embed->addFieldValues('Map', urldecode($server['map']), true);
            if (isset($server['age'])) $embed->addFieldValues('Epoch', urldecode($server['age']), true);
            //Players
            $players = [];
            foreach (array_keys($server) as $key) {
                $p = explode('player', $key); 
                if (isset($p[1]) && is_numeric($p[1])) $players[] = str_replace(['.', '_', ' '], '', strtolower(urldecode($server[$key])));
            }
            if (! empty($players)) $embed->addFieldValues("Players ({{count($players)})", implode(', ', $players), true);
            if (isset($server['season'])) $embed->addFieldValues('Season', urldecode($server['season']), true);
        }
        if (isset($tutelar->owner_id) && $owner = $tutelar->discord->users->get('id', $tutelar->owner_id)) $embed->setFooter(($tutelar->github ?  "{$tutelar->github}" . PHP_EOL : '') . "{$tutelar->discord->username} by {$owner->displayname}");
        $embed->setColor(0xe1452d);
        $embed->setTimestamp();
        $embed->setURL('');
        return $interaction->respondWithMessage(\Discord\Builders\MessageBuilder::new()->addEmbed($embed));
    });

    //if ($command = $commands->get('name', 'whois')) $commands->delete($command->id);
    if (! $commands->get('name', 'whois')) {
        $command = new \Discord\Parts\Interactions\Command\Command($tutelar->discord, [
            'type' => \Discord\Parts\Interactions\Command\Command::USER,
            'name' => 'whois',
        ]);
        $commands->save($command);
    }
    // listen for user commands
    $tutelar->discord->listenCommand('whois', function ($interaction) use ($tutelar, $whois) {
        $builder = new \Discord\Builders\MessageBuilder();
        $builder->addEmbed($whois($tutelar, $interaction->data->resolved->users->get('id', $interaction->data->target_id), $interaction->guild_id));
        $interaction->respondWithMessage($builder);
    });

    //if ($command = $commands->get('name', 'reminder')) $commands->delete($command->id);
    if (! $commands->get('name', 'reminder')) {
        $command = new \Discord\Parts\Interactions\Command\Command($tutelar->discord, [
            'name'			=> 'reminder',
            'description'	=> 'Add a reminder in the channel',
            'dm_permission' => false,
            'options'		=> [
                [
                    'name'			=> 'time',
                    'description'	=> 'PHP strtotime() compatible text',
                    'type'			=>  3,
                    'required'		=> true,
                ],
                [
                    'name'			=> 'message',
                    'description'	=> 'Message associated with your reminder',
                    'type'			=>  3,
                    'required'		=> true,
                ],
            ]
        ]);
        $commands->save($command);
    }
    $tutelar->discord->listenCommand('reminder', function ($interaction) use ($tutelar) {
        if (! $when = strtotime($interaction->data->options['time']->value)) return $interaction->respondWithMessage(\Discord\Builders\MessageBuilder::new()->setContent('Invalid time specified'), true);
        if (time()-$when>0) $when = $when+86400+(86400*(floor((time()-$when)/86400))); //Set time to tomorrow
        $interaction->respondWithMessage(\Discord\Builders\MessageBuilder::new()->setContent("Reminder added. {$interaction->data->options['message']->value}<t:" . $when-4 . ':R>')->setAllowedMentions(['users' => [$interaction->user->id]]));
        $tutelar->discord->getLoop()->addTimer($when-time(), function () use ($tutelar, $interaction) {
            if ($channel = $tutelar->discord->getChannel($interaction->channel_id)) $channel->sendMessage(\Discord\Builders\MessageBuilder::new()->setContent("{$interaction->user}, " . $interaction->data->options['message']->value)->setAllowedMentions(['users' => [$interaction->user->id]]));
        });
    });
};

$on_ready = function (\Tutelar\Tutelar $tutelar)
{
    $tutelar->logger->info("Logged in as {$tutelar->discord->user->displayname} ({$tutelar->discord->id})" . PHP_EOL . '------');
};