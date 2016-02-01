<?php namespace CupOfTea\CardCast;

use GuzzleHttp\Psr7\Stream;
use Illuminate\Support\Str;
use CupOfTea\Package\Package;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client as HttpClient;

abstract class Api
{
    protected $api = [];
    
    protected $options = [];
    
    protected $methods = [
        'index' => 'GET',
        'create' => 'POST',
        'get' => 'GET',
        'edit' => 'PUT',
        'delete' => 'DELETE',
    ];
    
    protected $bodyAsJson = true;
    
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
    ];
    
    private $actions = [
        'index',
        'create',
        'get',
        'edit',
        'delete',
    ];
    
    private $definition;
    
    private $client = [];
    
    private $endpoint;
    
    private $query = [];
    
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
        return (bool) is_array($this->api()->versions) && count($this->api()->versions);
    }
    
    public function hasVersion($version)
    {
        return is_array($this->api()->versions) && in_array($version, $this->api()->versions);
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
        
        $this->api()->version = $version;
        $this->endpoint = null;
        $this->query = [];
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
        if ($this->isVersioned()) {
            return $this->getVersionedClient();
        } else {
            return $this->getUnversionedClient();
        }
    }
    
    private function getVersionedClient()
    {
        if (isset($this->client[$this->getVersion()])) {
            return $this->client[$this->getVersion()];
        }
        
        return $this->client[$this->getVersion()] = $this->createClient();
    }
    
    private function getUnversionedClient()
    {
        if (isset($this->client)) {
            return $this->client;
        }
        
        return $this->client = $this->createClient();
    }
    
    private function createClient()
    {
        $options = $this->options;
        $options['headers'] = $this->getHeaders();
        $options['base_uri'] = $this->getBaseUri();
        
        return new HttpClient($options);
    }
    
    private function getHeaders()
    {
        $userAgent  = get_class($this);
        $userAgent .= ' Guzzle/' . ClientInterface::VERSION;
        
        if (extension_loaded('curl')) {
            $userAgent .= ' curl/' . curl_version()['version'];
        }
        
        $userAgent .= ' PHP/' . PHP_VERSION;
        
        $default = [
            'Accept' => 'application/json',
            'User-Agent' => $userAgent,
        ];
        
        return array_merge($default, array_get($options, 'headers', []));
    }
    
    private function getMethod($action)
    {
        return array_get($this->methods, $this->resolveAction($action), 'GET');
    }
    
    // API::endpoint(q[])->query1()->query2()->action(playcode, q[])
    final public function __call($method, $arguments)
    {
        if (is_null($this->endpoint)) {
            $this->setEndpoint($method);
            
            if (isset($arguments[0])) {
                $this->setQuery($arguments[0]);
            }
            
            return $this;
        } elseif ($this->isAction($method)) {
            $uriParams = array_get($arguments, 0);
            
            $client = $this->getClient();
            $action = $this->resolveAction($method);
            $method = $this->getMethod($action);
            $endpoint = $this->getEndpoint($action, $uriParams);
            
            switch ($method) {
                case 'POST':
                case 'PUT':
                    $body = array_get($arguments, 1);
                    $query = array_get($arguments, 2);
                    break;
                default:
                    $body = null;
                    $query = array_get($arguments, 1);
                    break;
            }
            
            $options = [];
            
            if ($query) {
                $this->setQuery($query);
            }
            
            if ($this->query) {
                $options['query'] = $this->query;
            }
            
            if (is_array($body)) {
                if ($this->bodyAsJson) {
                    $options['json'] => $body;
                } else {
                    $options['form_params'] => $body;
                }
            } elseif (is_string($body) || is_resource($body) || $body instanceof Stream) {
                $options['body'] = $body;
            }
            
            $response = $client->request($method, $endpoint, $options);
            
            return;
        }
        
        $this->setQuery($method, array_get($arguments, 0, true));
        
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
        
        $this->query[$key] = $value;
        
        return $this;
    }
    
    private function isAction($method)
    {
        return in_array($this->resolveAction($method), array_unique(array_merge($this->actions, array_keys($this->methods))));
    }
    
    private function resolveAction($action)
    {
        switch ($action) {
            case 'all':
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
