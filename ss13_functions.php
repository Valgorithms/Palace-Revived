<?php

$ss13_debug_guild_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower)
{
    //
};
$ss13_owner_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower)
{
    //
};
$ss13_manager_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower)
{
    if(!str_starts_with($message_content_lower, 'ss13')) return;
    $message_content = trim(substr($message_content, strlen('ss13')));
    $message_content_lower = strtolower($message_content);
    
    if(str_starts_with($message_content_lower, 'setup')) {
        $message_content = trim(substr($message_content, strlen('setup')));
        $message_content_lower = strtolower($message_content);
        
        if(str_starts_with($message_content_lower, 'channel')) {
            $message_content = trim(substr($message_content, strlen('channel')));
            $message_content_lower = strtolower($message_content);
            preg_match('/<#([0-9]*)>/', $message_content_lower, $matches);
            if (!$matches[1] || !$message->guild->channels->get('id', $matches[1])) return $message->reply('Channel not found! Please mention the channel in the format of <#channel_id>');
            $message_content_lower = trim(substr($message_content_lower, strlen($matches[0])));
            if (!in_array($message_content_lower, ['suggestion_pending', 'suggestion_approved', 'tip_pending', 'tip_approved'])) return $message->reply('Invalid channel configuration `' . $message_content_lower . '`! Valid options are ' . implode(', ', ['suggestion_pending', 'suggestion_approved', 'tip_pending', 'tip_approved']));
            $tutelar->discord_config[$message->guild_id]['channels'][$message_content_lower] = $matches[1];
            
            if(!isset($tutelar->suggestions[$message->guild_id]['pending'])) $tutelar->suggestions[$message->guild_id]['pending'] = [];
            if(!isset($tutelar->suggestions[$message->guild_id]['approved'])) $tutelar->suggestions[$message->guild_id]['approved'] = [];
            if(!isset($tutelar->suggestions[$message->guild_id]['denied'])) $tutelar->suggestions[$message->guild_id]['denied'] = [];
        
        
            if(!isset($tutelar->tips[$message->guild_id]['pending'])) $tutelar->tips[$message->guild_id]['pending'] = [];
            if(!isset($tutelar->tips[$message->guild_id]['approveed'])) $tutelar->tips[$message->guild_id]['approved'] = [];
            if(!isset($tutelar->tips[$message->guild_id]['denied'])) $tutelar->tips[$message->guild_id]['denied'] = [];
            
            $tutelar->logger->info('Updated ' . $message_content_lower . ' channel id ' . $matches[1]);
            $tutelar->saveConfig();
            $tutelar->VarSave('suggestions.json', $tutelar->suggestions);
            $tutelar->VarSave('tips.json', $tutelar->tips);
            return $message->react("👍");;
        }
    }
};
$ss13_admin_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower)
{
    //
};
$ss13_moderator_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower)
{
    //
};

$ss13_any_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower)
{
    //
};
$ss13_direct_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower)
{
    //
};
$ss13_guild_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower)
{
    //
};
$ss13_any_called_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower)
{
    //
};
$ss13_guild_called_message = function (\Tutelar\Tutelar $tutelar, $message, string $message_content, string $message_content_lower) use ($perm_check, $ss13_debug_guild_message, $ss13_owner_message, $ss13_manager_message, $ss13_admin_message, $ss13_moderator_message)
{
    if (str_starts_with($message_content_lower, 'tip')) {
        $message_content = trim(substr($message_content, strlen('tip')));
        $message_content_lower = strtolower($message_content);
        if(!$message_content_lower) return;
        if($tutelar->discord_config[$message->guild_id]['channels']['tip_approved']) {
            if (str_starts_with($message_content_lower, 'approve')) {
                if (is_int($id = trim(substr($message_content, strlen('approve'))))) {
                    if(! $perm_check($tutelar->discord, ['administrator', 'manage_server', 'ban_members', 'moderate_members'], $message->member)) return $message->reply('You do not have permission to use this command');
                    if(! $tip = $tutelar->tips[$id]) return $message->reply('Tip does not exist');
                    if(! $channel = $tutelar->discord->getChannel($tutelar->discord_config[$message->guild_id]['channels']['tip_approved'])) return $message->reply('Unable to locate tip approved channel');
                    //return $this->VarSave('tips.json', $this->tips);
                }
            }
            if (str_starts_with($message_content_lower, 'deny')) {
                if (is_int($id = trim(substr($message_content, strlen('deny'))))) {
                    if(! $perm_check($tutelar->discord, ['administrator', 'manage_server', 'ban_members', 'moderate_members'], $message->member)) return $message->reply('You do not have permission to use this command');
                    if(! $tip = $tutelar->tips[$id]) return $message->reply('Tip does not exist');
                    //
                }
            }
        }
        if($tutelar->discord_config[$message->guild_id]['channels']['tip_pending']) {
            if(! $channel = $tutelar->discord->getChannel($tutelar->discord_config[$message->guild_id]['channels']['tip_pending'])) return $message->reply('Unable to locate tip pending channel!');
            //
        }
    }
    if (str_starts_with($message_content_lower, 'suggest')) {
        $message_content = trim(substr($message_content, strlen('suggest')));
        $message_content_lower = strtolower($message_content);
        if(!$message_content_lower) return;
        
        if ($message_content_lower == 'pending') {
            if(! $perm_check($tutelar->discord, ['administrator', 'manage_server', 'ban_members', 'moderate_members'], $message->member)) return $message->reply('You do not have permission to use this command');
            $builder = new \Discord\Builders\MessageBuilder();
            $builder->addFileFromContent('pending.txt', var_export($tutelar->suggestions[$message->guild_id]['pending'], true));
            return $message->reply($builder);
        }
        if ($message_content_lower == 'approved') {
            if(! $perm_check($tutelar->discord, ['administrator', 'manage_server', 'ban_members', 'moderate_members'], $message->member)) return $message->reply('You do not have permission to use this command');
            $builder = new \Discord\Builders\MessageBuilder();
            $builder->addFileFromContent('approved.txt', var_export($tutelar->suggestions[$message->guild_id]['approved'], true));
            return $message->reply($builder);
        }
        if ($message_content_lower == 'denied') {
            if(! $perm_check($tutelar->discord, ['administrator', 'manage_server', 'ban_members', 'moderate_members'], $message->member)) return $message->reply('You do not have permission to use this command');
            $builder = new \Discord\Builders\MessageBuilder();
            $builder->addFileFromContent('denied.txt', var_export($tutelar->suggestions[$message->guild_id]['denied'], true));
            return $message->reply($builder);
        }
        
        if (str_starts_with($message_content_lower, 'approve')) {
            if (! $suggestion = $tutelar->suggestions[$message->guild_id]['pending'][$id = trim(substr($message_content, strlen('approve')))]) return $message->reply('Suggestion does not exist');
            if(! $perm_check($tutelar->discord, ['administrator', 'manage_server', 'ban_members', 'moderate_members'], $message->member)) return $message->reply('You do not have permission to use this command');
            if(! $channel = $tutelar->discord->getChannel($tutelar->discord_config[$message->guild_id]['channels']['suggestion_approved'])) return $message->reply('Unable to locate suggestion approved channel');
            $tutelar->suggestions[$message->guild_id]['approved'][$id] = $suggestion;
            unset($tutelar->suggestions[$message->guild_id]['pending'][$id]);
            $builder = new \Discord\Builders\MessageBuilder();
            $builder->setAllowedMentions(['parse'=>[]]);
            $embed = new \Discord\Parts\Embed\Embed($tutelar->discord);
            $embed->setTitle('Suggestion ID: ' . $id);
            $embed->setDescription('Suggestion submitted by: <@' . $suggestion['user_id'] . '>');
            if ($user = $tutelar->discord->users->get('id', $suggestion['user_id'])) {
                $embed->setAuthor($user->displayname, $user->avatar);
                $embed->setThumbnail($user->avatar);
            }
            if (strlen($suggestion['content']) <= 1024) $embed->addFieldValues('Suggestion', $suggestion['content']);
            else $builder->setContent($suggestion['content']);
            $embed->setTimestamp();
            $builder->addEmbed($embed);
            $channel->sendMessage($builder)->done(function($message){$message->react("👍")->done(function($result)use($message){$message->react("👎");});});
            if ($m = $message->guild->channels->get('id' , $tutelar->discord_config[$message->guild_id]['channels']['suggestion_pending'])->messages->get('id', $suggestion['message_id'])) $m->delete(); 
            else ($m = $message->guild->channels->get($tutelar->discord_config[$message->guild_id]['channels']['suggestion_pending'])->messages->fetch('id', $suggestion['message_id'])->done(function ($message) {$message->delete();}));
            $message->react("👍");
            return $tutelar->VarSave('suggestions.json', $tutelar->suggestions);
        }
        if (str_starts_with($message_content_lower, 'deny')) {
            if(! $suggestion = $tutelar->suggestions[$message->guild_id]['pending'][$id = trim(substr($message_content, strlen('deny')))]) return $message->reply('Suggestion does not exist');
            if(! $perm_check($tutelar->discord, ['administrator', 'manage_server', 'ban_members', 'moderate_members'], $message->member)) return $message->reply('You do not have permission to use this command');
            $tutelar->suggestions[$message->guild_id]['denied'][$id] = $suggestion;
            unset($tutelar->suggestions[$message->guild_id]['pending'][$id]);
            if ($m = $message->guild->channels->get('id' , $tutelar->discord_config[$message->guild_id]['channels']['suggestion_pending'])->messages->get('id', $suggestion['message_id'])) $m->delete(); 
            $message->react("👍");
            return $tutelar->VarSave('suggestions.json', $tutelar->suggestions);
        }
        if($tutelar->discord_config[$message->guild_id]['channels']['suggestion_pending']) {
            if(! $channel = $tutelar->discord->getChannel($tutelar->discord_config[$message->guild_id]['channels']['suggestion_pending'])) return $message->reply('Unable to locate suggestion pending channel!');
            $tutelar->suggestions[$message->guild_id]['pending'][$message->id] = [
                'user_id' => $message->author->id,
                'content' => $message_content,
                'message_id' => ''
            ];
            $builder = new \Discord\Builders\MessageBuilder();
            $embed = new \Discord\Parts\Embed\Embed($tutelar->discord);
            $embed->setTitle('Suggestion ID: ' . $message->id);
            $embed->setDescription('Suggestion submitted by: ' . $message->author);
            $embed->setTimestamp();
            $builder->addFileFromContent('suggestion.txt', $message_content);
            $builder->addEmbed($embed);
            $channel->sendMessage($builder)->done(function($new_message) use ($tutelar, $message) {
                $tutelar->suggestions[$message->guild_id]['pending'][$message->id]['message_id'] = $new_message->id;
                $tutelar->VarSave('suggestions.json', $tutelar->suggestions);
                $new_message->react("👍")->done(function($result)use($new_message){$new_message->react("👎");});
            });
            return $message->reply("Your suggestion has been submitted for review with ID {$message->id}");
        }
    }
    
    if($message->user_id == $tutelar->owner_id) {
        $ss13_debug_guild_message($tutelar, $message, $message_content, $message_content_lower);
        $ss13_owner_message($tutelar, $message, $message_content, $message_content_lower);
        $ss13_manager_message($tutelar, $message, $message_content, $message_content_lower);
        $ss13_admin_message($tutelar, $message, $message_content, $message_content_lower);
        $ss13_moderator_message($tutelar, $message, $message_content, $message_content_lower);
        return;
    }
    if($message->guild->owner_id == $message->user_id) $owner_message($tutelar, $message, $message_content, $message_content_lower);    
    if($perm_check($tutelar->discord, ['administrator', 'manage_server'], $message->member)) $ss13_manager_message($tutelar, $message, $message_content, $message_content_lower);
    if($perm_check($tutelar->discord, ['administrator', 'ban_members'], $message->member)) $ss13_admin_message($tutelar, $message, $message_content, $message_content_lower);
    if($perm_check($tutelar->discord, ['administrator', 'moderate_members'], $message->member)) $ss13_moderator_message($tutelar, $message, $message_content, $message_content_lower);
    //if (str_starts_with($message_content_lower, 'tip '))
};
$ss13_on_message = function (\Tutelar\Tutelar $tutelar, $message) use ($ss13_any_message, $ss13_direct_message, $ss13_guild_message, $ss13_any_called_message, $ss13_guild_called_message)
{
    if(!$message->content) return; //Don't process message without text
    $message_content = '';
    $message_content_lower = '';
    $called = false;
    //$tutelar->logger->debug('[MESSAGE] {' . $message->guild_id . '/' . $message->channel_id . '} ' . $message->author->displayname . ': ' . $message->content);
    foreach($tutelar->command_symbol as $symbol) {
        if(str_starts_with($message->content, $symbol)) {
            $message_content = trim(substr($message->content, strlen($symbol)));
            $message_content_lower = strtolower($message_content);
            $called = true;
            $tutelar->logger->debug($message->guild_id . ' - `' . $message_content . '`');
            break;
        }
    }
    
    //if($message->guild->owner_id != $tutelar->owner_id) return; //Only process commands from a guild that Valithor owns
    $ss13_any_message($tutelar, $message, $message_content, $message_content_lower);
    $ss13_direct_message($tutelar, $message, $message_content, $message_content_lower);
    $ss13_guild_message($tutelar, $message, $message_content, $message_content_lower);
    if(!$called) return;
    $ss13_any_called_message($tutelar, $message, $message_content, $message_content_lower);
    $ss13_guild_called_message($tutelar, $message, $message_content, $message_content_lower);
};