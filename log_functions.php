<?php
$log_builder = function(\Tutelar\Tutelar $tutelar, $new, ?string $title = '', ?string $desc = '', $old = null, $color = 0xa7c5fd)
{
    $builder = new \Discord\Builders\MessageBuilder();
    $embed = new \Discord\Parts\Embed\Embed($tutelar->discord);
    
    //Get optional embed elements
    //Get optional embed elements
    
    $guild_id = null;
    $user = null;
    if (str_contains(get_class($new), 'Message')) {
        $guild_id = $new->guild_id;
        $user = $new->author;
        if ($new->getLinkAttribute()) $embed->addFieldValues('Message', "[Link]({$new->getLinkAttribute()})", true);
        if ($new->referenced_message) $embed->addFieldValues('Reply To', "[Link]({$new->referenced_message->getLinkAttribute()})", true);
        if ($new->getLinkAttribute()) $embed->addFieldValues('Channel', $new->channel, true);
        if ($new->getLinkAttribute()) $embed->addFieldValues('Message ID', $new->id, true);
        if ($title == 'Message Deleted') {
            if ($new->referenced_message) {
                if (strlen($new->referenced_message->content) <= 1024) $embed->addFieldValues('Replied to message content', $new->referenced_message->content);
                else $builder->addFileFromContent('replied_message.txt', $new->referenced_message->content);
            }
            if (strlen($new->content) <= 1024) $embed->addFieldValues('Deleted message content', $new->content);
            else $builder->addFileFromContent('deleted_message.txt', $new->content);
        }
        //if ($new->content) $builder->addFileFromContent('message_content.txt', $new->content);
    } elseif ($new instanceof \Discord\Parts\User\Member) {
        $guild_id = $new->guild_id;
        $user = $new->user;
        if (in_array($title, ['Member Left', 'Member Joined'])) {
            $embed->addFieldValues('Created', '<t:' . floor((int) $user->createdTimestamp()) . ':F>');
            $embed->addFieldValues('Member Count', sizeof($new->guild->members));
            $array = [];
            foreach ($new->roles as $role) $array []= (string) $role;
            $string = implode('', $array);
            if ($string) $embed->addFieldValues('Roles', $string);
        }
    } elseif ($new instanceof \Discord\Parts\Guild\Ban) {
        $guild_id = $new->guild_id;
        $user = $new->user;
        if ($new->reason) $embed->addFieldValues('Reason', $new->reason, true);
    } elseif ($new instanceof \Discord\Parts\User\User) {
        $user = $new;
    }
    
    if ($user) {
        $embed->setAuthor($user->displayname, $user->avatar);
        $embed->setThumbnail($user->avatar);
    }
    
    //Embed changes
    if ($old) {
        if (str_contains(get_class($old), 'Message')) {
            if ($new->content != $old->content) {
                if ($new->referenced_message) {
                    if (strlen($new->referenced_message->content) <= 1024) $embed->addFieldValues('Replied to message content', $new->referenced_message->content);
                    else $builder->addFileFromContent('replied_message.txt', $new->referenced_message->content);
                }
                if (strlen($old->content) <= 1024) $embed->addFieldValues('Old', $old->content);
                else $builder->addFileFromContent('message_content_old.txt', $old->content);
                if (strlen($new->content) <= 1024) $embed->addFieldValues('New', $new->content);
                else $builder->addFileFromContent('message_content_new.txt', $new->content);
            }
        } elseif ($old instanceof \Discord\Parts\User\Member) {
            if ($old->username != $new->username) $embed->addFieldValues('Username', "`{$old->username}`→`{$new->username}`" , true);
            if ($old->nick != $new->nick) $embed->addFieldValues('Nickname','"`' . ($old->nick ?? $old->user->global_name ?? $old->user->username)  . "`→`{$new->nick}`" , true);
            if ($old->avatar != $new->avatar) $embed->addFieldValues('Avatar', "`{$old->avatar}`→`{$new->avatar}`" , true);
            if ($new->roles != $old->roles) {
                $new_role_names = [];
                $old_role_names = [];
                foreach ($new->roles as $role) $new_role_names[] = (string) $role;
                foreach ($old->roles as $role) $old_role_names[] = (string) $role;
                $array_diff = array_merge(array_diff($old_role_names, $new_role_names), array_diff($new_role_names, $old_role_names));
                foreach ($array_diff as $diff) if (in_array($diff, $new_role_names) && !in_array($diff, $old_role_names)) $embed->addFieldValues('Added Roles', $diff);
                else $embed->addFieldValues('Removed Roles', $diff);
            }
        } elseif ($old instanceof \Discord\Parts\User\User) {
            if ($old->displayname != $new->displayname) $embed->addFieldValues('Nickname', "`{$old->displayname}`→`{$new->displayname}`" , true);
            //
        }
    }
    if ($title == 'Member Updated' && sizeof($embed->fields)<1) return; //Don't log empty changes
    if ($title == 'Message Deleted' && sizeof($embed->fields)<3) return; //Don't log uncached changes
    //Template embed elements
    if ($title) $embed->setTitle($title);
    if ($desc) $embed->setDescription($desc);
    if ($guild_id != '115233111977099271' && isset($tutelar->owner_id) && $owner = $tutelar->discord->users->get('id', $tutelar->owner_id)) $embed->setFooter(($tutelar->github ?  "{$tutelar->github}" . PHP_EOL : '') . "{$tutelar->discord->username} by {$owner->displayname}");//The DiscordPHP server doesn't need to include a footer on embeds
    $embed->setColor($color);
    $embed->setTimestamp();
    $builder->addEmbed($embed);
    return $builder;
};

$log_message = function (\Tutelar\Tutelar $tutelar, $message) use ($log_builder)
{
    return; //We have no reason to log anything for new messages right now
    if ($message->user_id == $tutelar->discord->id) return; // Ignore messages sent by this bot
    if ($message->webhook_id) return; // Ignore messages sent by webhooks
    if (isset($message->user) && $message->user->bot) return; //Don't log messages made by bots
};

$log_MESSAGE_UPDATE = function (\Tutelar\Tutelar $tutelar, $message, $message_old = null) use ($log_builder)
{
    if (is_null($message) || (! isset($message->author) || is_null($message->author) ) || (! isset($message->edited_timestamp) || is_null($message->edited_timestamp)) ) return;
    if ($message->user_id == $tutelar->discord->id) return; // Ignore messages sent by this bot
    if ($message->webhook_id) return; // Ignore messages sent by webhooks
    if (isset($message->user) && $message->user->bot) return; //Don't log messages made by bots
    if ($message->guild_id && isset($tutelar->discord_config[$message->guild_id]['channels']['log']) && $channel = $tutelar->discord->getChannel($tutelar->discord_config[$message->guild_id]['channels']['log'])) {
        if ($builder = $log_builder($tutelar, $message, 'Message Updated', null, $message_old))
            $channel->sendMessage($builder)->done(
                function ($new_message) use ($tutelar, $message) {
                    $tutelar->logger->info('Logged updated message: ' . $message->id);
                }, function ($error) use ($tutelar) {
                    $tutelar->logger->warning('Error logging updated message: ' . $error->getMessage());
                }
            );
    }
};

$log_MESSAGE_DELETE = function (\Tutelar\Tutelar $tutelar, $message) use ($log_builder)
{
    if (! $message instanceof stdClass) {
        if ($message->user_id == $tutelar->discord->id) return; // Ignore messages sent by this bot
        if ($message->webhook_id) return; // Ignore messages sent by webhooks
    }
    if ($message->channel_id == $tutelar->discord_config[$message->guild_id]['channels']['log']) return; //Don't log deleted logs
    if (isset($message->user) && $message->user->bot) return; //Don't log messages made by bots
    if ($message->guild_id && $channel = $tutelar->discord->getChannel($tutelar->discord_config[$message->guild_id]['channels']['log'])) {
        if ($builder = $log_builder($tutelar, $message, 'Message Deleted'))
            $channel->sendMessage($builder)->done(
                function ($new_message) use ($tutelar, $message) {
                    $tutelar->logger->info('Logged deleted message: ' . $message->id);
                }, function ($error) use ($tutelar) {
                    $tutelar->logger->warning('Error logging deleted message: ' . $error->getMessage());
                }
            );
    }
};

$log_MESSAGE_DELETE_BULK = function (\Tutelar\Tutelar $tutelar, $messages) use ($log_builder, $log_MESSAGE_DELETE)
{
    foreach ($messages as $message) $log_MESSAGE_DELETE($tutelar, $message);
};

$log_GUILD_MEMBER_ADD = function (\Tutelar\Tutelar $tutelar, \Discord\Parts\User\Member $member) use ($log_builder)
{
    if ($channel = $tutelar->discord->getChannel($tutelar->discord_config[$member->guild_id]['channels']['welcomelog'] ?? $tutelar->discord_config[$member->guild_id]['channels']['log'])) {
        if ($builder = $log_builder($tutelar, $member, 'Member Joined', $member))
            $channel->sendMessage($builder)->done(
                function ($message) use ($tutelar, $member) {
                    $tutelar->logger->info('Logged added member: ' . $member->id);
                }, function ($error) use ($tutelar) {
                    $tutelar->logger->warning('Error logging added member: ' . $error->getMessage());
                }
            );
    }
};

$log_GUILD_MEMBER_REMOVE = function (\Tutelar\Tutelar $tutelar, \Discord\Parts\User\Member $member) use ($log_builder)
{
    if ($channel = $tutelar->discord->getChannel($tutelar->discord_config[$member->guild_id]['channels']['welcomelog'] ?? $tutelar->discord_config[$member->guild_id]['channels']['log'])) {
        if ($builder = $log_builder($tutelar, $member, 'Member Left', $member))
            $channel->sendMessage($builder)->done(
                function ($message) use ($tutelar, $member) {
                    $tutelar->logger->info('Logged removed member: ' . $member->id);
                }, function ($error) use ($tutelar) {
                    $tutelar->logger->warning('Error logging removed member: ' . $error->getMessage());
                }
            );
    }
};

$log_GUILD_MEMBER_UPDATE = function (\Tutelar\Tutelar $tutelar, \Discord\Parts\User\Member $member, ?\Discord\Parts\User\Member $member_old = null) use ($log_builder)
{
    if ($channel = $tutelar->discord->getChannel($tutelar->discord_config[$member->guild_id]['channels']['log'])) {
        if ($builder = $log_builder($tutelar, $member, 'Member Updated', $member, $member_old))
            $channel->sendMessage($builder)->done(
                function ($message) use ($tutelar, $member) {
                    $tutelar->logger->info('Logged updated member: ' . $member->id);
                }, function ($error) use ($tutelar) {
                    $tutelar->logger->warning('Error logging updated member: ' . $error->getMessage());
                }
            );
    }
};

$log_GUILD_BAN_ADD = function (\Tutelar\Tutelar $tutelar, \Discord\Parts\Guild\Ban $ban) use ($log_builder)
{
    if ($channel = $tutelar->discord->getChannel($tutelar->discord_config[$ban->guild_id]['channels']['log'])) {
        if ($builder = $log_builder($tutelar, $ban, 'Member Banned', $ban->user))
            $channel->sendMessage($builder)->done(
                function ($message) use ($tutelar, $ban) {
                    $tutelar->logger->info('Logged banned member: ' . $ban->user);
                }, function ($error) use ($tutelar) {
                    $tutelar->logger->warning('Error logging banned member: ' . $error->getMessage());
                }
            );
    }
};

$log_GUILD_BAN_REMOVE = function (\Tutelar\Tutelar $tutelar, \Discord\Parts\Guild\Ban $ban) use ($log_builder)
{
    if ($channel = $tutelar->discord->getChannel($tutelar->discord_config[$ban->guild_id]['channels']['log'])) {
        if ($builder = $log_builder($tutelar, $ban, 'Member Unbanned', $ban->user))
            $channel->sendMessage($builder)->done(
                function ($message) use ($tutelar, $ban) {
                    $tutelar->logger->info('Logged unbanned member: ' . $ban->user);
                }, function ($error) use ($tutelar) {
                    $tutelar->logger->warning('Error logging unbanned member: ' . $error->getMessage());
                }
            );
    }
};

$log_userUpdate = function (\Tutelar\Tutelar $tutelar, \Discord\Parts\User\User $user, ?\Discord\Parts\User\User $user_old = null) use ($log_builder)
{
    if ($user->id == $tutelar->discord->id) return; // Ignore user updates by this bot
    if ($builder = $log_builder($tutelar, $user, 'User Updated', $user, $user_old))
        foreach ($tutelar->discord->guilds as $guild) if ($member = $guild->members->get('id', $user->id) && $channel = $tutelar->discord->getChannel($tutelar->discord_config[$guild->id]['channels']['log'])) {
            $channel->sendMessage($builder)->done(
                function ($message) use ($tutelar, $user) {
                    $tutelar->logger->info('Logged updated user: ' . $user->id);
                }, function ($error) use ($tutelar) {
                    $tutelar->logger->warning('Error logging updated user: ' . $error->getMessage());
                }
            );
        }
};