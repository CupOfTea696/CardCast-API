<?php namespace CupOfTea\CardCast;

use GuzzleHttp\Client;
use Illuminate\Support\Str;
use CupOfTea\Package\Package;

final class ApiDefinition
{
    protected $definition = [
        'base' => null,
        'version' => null,
        'versions' => [],
        'endpoints' => []
    ];
    
    public function __construct($data, $validate = false)
    {
        $this->definition = array_merge($this->definition, $data);
        
        if ($validate) {
            $this->validate();
        }
    }
    
    public function validate()
    {
        if (! $this->definition['base']) {
            throw new InvalidApiDefinitionException('required', 'base');
        }
    }
    
    /**
     * Get a property's value.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        $key = Str::snake($key);
        
        return array_get($this->definition, $key);
    }
    
    /**
     * Set a property's value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $key = Str::snake($key);
        $value = value($value);
        
        $this->definition[$key] = $value;
    }
    
    /**
     * Check if a property is set.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        $key = Str::snake($key);
        
        return isset($this->definition[$key]);
    }
    
    /**
     * Unset a property.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        $key = Str::snake($key);
        
        unset($this->definition[$key]);
    }
}
