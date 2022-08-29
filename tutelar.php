<?php

/*
 * This file is a part of the Tutelar project.
 *
 * Copyright (c) 2022-present Valithor Obsidion <valithor@valzargaming.com>
 */

namespace Tutelar;

class Tutelar
{
    public $loop;
    public $discord;
    public $twitch;
    public $browser;
    public $logger;
    public $stats;
    
    protected $webapi;
    
    public $timers = [];
    
    public $functions = [];
    
    public $command_symbol = [];
    public $owner_id = '116927250145869826';
    public $tutelar_guild_id = '923969098185068594';
    
    public $files = [];
    public $ips = [];
    public $ports = [];
    public $channel_ids = [];
    public $role_ids = [];
    
    /**
     * Creates a Tutelar client instance.
     *
     * @param  array           $options Array of options.
     * @throws IntentException
     */
    public function __construct(array $options = [])
    {
        if (php_sapi_name() !== 'cli') {
            trigger_error('DiscordPHP will not run on a webserver. Please use PHP CLI to run a DiscordPHP bot.', E_USER_ERROR);
        }

        // x86 need gmp extension for big integer operation
        if (PHP_INT_SIZE === 4 && ! Discord\Helpers\Bitwise::init()) {
            trigger_error('ext-gmp is not loaded. Permissions will NOT work correctly!', E_USER_WARNING);
        }
        
        $options = $this->resolveOptions($options);
        
        $this->loop = $options['loop'];
        $this->browser = $options['browser'];
        $this->logger = $options['logger'];
        $this->stats = $options['stats'];
        
        
        if(isset($options['command_symbol'])) {
            if(is_array($options['command_symbol'])) {
                foreach ($options['command_symbol'] as $symbol)
                $this->command_symbol[] = $symbol;
            }
            if(is_string($options['command_symbol'])) {
                $this->command_symbol[] = $options['command_symbol'];
            }
        }
        if(isset($options['owner_id'])) {
            $this->owner_id = $options['owner_id'];
        }
        if(isset($options['tutelar_guild_id'])) {
            $this->tutelar_guild_id = $options['tutelar_guild_id'];
        }
        
        if (isset($options['discord']) || isset($options['discord_options'])) {
            if(isset($options['discord'])) $this->discord = $options['discord'];
            elseif(isset($options['discord_options'])) $this->discord = new \Discord\Discord($options['discord_options']);
        }
        
        if (isset($options['twitch']) || isset($options['twitch_options'])) {
            if(isset($options['twitch'])) $this->twitch = $options['twitch'];
            elseif(isset($options['twitch_options'])) $this->twitch = new \Twitch\Twitch($options['twitch_options']);
        }
        
        if(isset($options['functions'])) {
            if(isset($options['functions']['ready'])) {
                foreach ($options['functions']['ready'] as $key => $func)
                    $this->functions['ready'][$key] = $func;
            }
            if(isset($options['functions']['message']))
                foreach ($options['functions']['message'] as $key => $func)
                    $this->functions['message'][$key] = $func;
            if(isset($options['functions']['misc']))
                foreach ($options['functions']['misc'] as $key => $func)
                    $this->functions['misc'][$key] = $func;
        } else $this->logger->warning('No functions passed in options!');
        if(isset($options['files'])) {
            foreach ($options['files'] as $key => $path)
                $this->files[$key] = $path;
        }  else $this->logger->warning('No files passed in options!');
        if(isset($options['channel_ids'])) {
            foreach ($options['channel_ids'] as $key => $id)
                $this->channel_ids[$key] = $id;
        } else $this->logger->warning('No channel_ids passed in options!');
        if(isset($options['role_ids'])) {
            foreach ($options['role_ids'] as $key => $id)
                $this->role_ids[$key] = $id;
        } else $this->logger->warning('No role_ids passed in options!');
        $this->afterConstruct();
    }
    
    protected function afterConstruct()
    {
        if(isset($this->discord)) {
            $this->discord->once('ready', function () {
                $this->command_symbol[] = '<@'.$this->discord->id.'>';
                $this->command_symbol[] = '<@!'.$this->discord->id.'>';
                if(! empty($this->functions['ready']))
                    foreach ($this->functions['ready'] as $func)
                        $func($this);
                else $this->logger->debug('No ready functions found!');
                $this->discord->on('message', function ($message)
                {
                    if(! empty($this->functions['message']))
                        foreach ($this->functions['message'] as $func)
                            $func($this, $message);
                    else $this->logger->debug('No message functions found!');
                });
                $this->discord->on('GUILD_MEMBER_ADD', function ($guildmember) {
                    if(! empty($this->functions['GUILD_MEMBER_ADD']))
                        foreach ($this->functions['GUILD_MEMBER_ADD'] as $func)
                            $func($this, $guildmember);
                    else $this->logger->debug('No message functions found!');
                });
            });
        }
    }
    
    /*
    * Attempt to catch errors with the user-provided $options early
    */
    protected function resolveOptions(array $options = []): array
    {
        if (is_null($options['logger'])) {
            $logger = new Monolog\Logger('Tutelar');
            $logger->pushHandler(new Monolog\Handler\StreamHandler('php://stdout', Monolog\Logger::DEBUG));
            $options['logger'] = $logger;
        }
        
        $options['loop'] = $options['loop'] ?? React\EventLoop\Factory::create();
        $options['browser'] = $options['browser'] ?? new \React\Http\Browser($options['loop']);
        return $options;
    }
    
    public function run(): void
    {
        $this->logger->info('Starting Discord loop');
        if(!(isset($this->discord))) $this->logger->warning('Discord not set!');
        else $this->discord->run();
    }
    
    public function stop(): void
    {
        $this->logger->info('Shutting down');
        if((isset($this->discord))) $this->discord->stop();
    }
}