<?php namespace CupOfTea\CardCast;

use Illuminate\Support\Str;
use CupOfTea\Package\Package;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client as HttpClient;

abstract class Api
{
    protected $api = [];
    
    protected $options = [
        'decode_content' => 'gzip',
    ];
    
    protected $queryTrueValue = 1;
    
    protected $querFalseValue = 0;
    
    protected $cc_api = [
        'base' => 'https://api.cardcastgame.com',
        'version' => 'v1',
        'versions' => ['v1'],
        'endpoints' => [
            'v1' => [
                'deck {index, get}' => 'decks/:playcode',
                'cards' => 'decks/:playcode/cards',
                'calls' => 'decks/:playcode/calls',
                'responses' => 'decks/:playcode/responses',
            ],
        ],
        'properties' => [
            'v1' => [
                'offset' => ['deck.index'],
                'limit' => ['deck.index'],
                'author' => ['deck.index'],
                'category' => ['deck.index'],
                'search' => ['deck.index'],
            ]
        ],
    ];
    
    private $actions = [
        'index',
        'create',
        'get',
        'edit',
        'delete',
    ];
    
    private $definition;
    
    private $client;
    
    private $endpoint;
    
    private $query;
    
    final public function __construct(ApiDefinition $definition = null)
    {
        $this->definition = $definition ?: new ApiDefinition($this->api);
        $this->definition->validate();
        
        if (method_exists($this, 'boot')) {
            $this->boot();
        }
    }
    
    public function isVersioned()
    {
        return (bool) count($this->api()->versions);
    }
    
    public function hasVersion($version)
    {
        return in_array($version, $this->api()->versions);
    }
    
    public function getVersion()
    {
        return $this->api()->version;
    }
    
    public function getVersions()
    {
        return $this->api()->versions;
    }
    
    public function setVersion($version)
    {
        if (! $this->isVersioned()) {
            return;
        }
        
        $version = strrev(Str::finish(strrev($version), 'v'));
        
        if (! $this->hasVersion($version)) {
            throw new InvaidArgumentException('There is no version ' . $version);
        }
    }
    
    final protected function api()
    {
        return $this->definition;
    }
    
    final protected function getBaseUri()
    {
        return $this->api()->base . ($this->isVersioned() ? '/' . $this->getVersion() : '');
    }
    
    final protected function getClient()
    {
        if (isset($this->client)) {
            return $this->client;
        }
        
        $options = $this->options;
        $options['headers'] = array_merge($this->getDefaultHeaders($options), array_get($options, 'headers', []));
        $options['base_uri'] = $this->getBaseUri();
        
        return $this->client = new HttpClient($options);
    }
    
    private function getDefaultHeaders($options)
    {
        $userAgent  = get_class($this);
        $userAgent .= ' Guzzle/' . ClientInterface::VERSION;
        
        if (extension_loaded('curl')) {
            $userAgent .= ' curl/' . curl_version()['version'];
        }
        
        $userAgent .= ' PHP/' . PHP_VERSION;
        
        if (array_get($options, 'decode_content') == 'gzip' || array_get($options, 'headers.Accept-Encoding' == 'gzip')) {
            $userAgent .= ' (gzip)';
        }
        
        return [
            'Accept' => 'application/json',
            'User-Agent' => $userAgent,
        ];
    }
    
    // API::endpoint([])->query1()->query2()->action()
    final public function __call($method, $arguments)
    {
        if (is_null($this->endpoint)) {
            $this->setEndpoint($method);
            
            if (isset($arguments[0])) {
                $this->setQuery($arguments[0]);
            }
            
            return $this;
        } elseif ($this->isAction($method)) {
            // Execute API Request.
            
            return;
        }
        
        $this->setQuery($method, array_get($arguments, 0, $this->queryTrueValue));
        
        return $this;
    }
    
    public function setEndpoint($endpoint)
    {
        $this->endpoint = $name;
        
        return $this;
    }
    
    public function setQuery($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $value) {
                $this->setQuery($k, $value);
            }
            
            return $this;
        }
        
        $value = value($value);
        $value = is_bool($value) ? $this->queryBoolValue($value) : $value;
        
        $this->query[$key] = $value;
        
        return $this;
    }
    
    private function queryBoolValue($value)
    {
        return $value ? $this->queryTrueValue : $this->queryFalseValue;
    }
    
    private function isAction($method)
    {
        return in_array($this->resolveAction($method), $this->actions);
    }
    
    private function resolveAction($action)
    {
        switch ($action) {
            case 'list':
                return 'index';
            case 'store':
                return 'create';
            case 'show':
                return 'get';
            case 'put':
            case 'patch':
            case 'update':
                return 'edit';
            case 'destroy':
                return 'delete';
            default:
                return $action;
    }
}
