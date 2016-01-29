<?php namespace CupOfTea\CardCast;

use GuzzleHttp\Client;
use Illuminate\Support\Str;
use CupOfTea\Package\Package;

class CardCast extends Api
{
    use Package;
    
    /**
     * Package Name.
     *
     * @const string
     */
    const PACKAGE = 'CupOfTea/CardCast';
    
    /**
     * Package Version.
     *
     * @const string
     */
    const VERSION = '0.0.0';
    
    protected $api = [
        'base' => 'https://api.cardcastgame.com',
        'version' => 'v1',
        'versions' => ['v1'],
        'endpoints' => [
            'v1' => [
                'deck.{index, show}' => 'decks/:playcode',
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
    
    public function deck($playcode)
    {
        
    }
    
    public function cards($playcode)
    {
        
    }
}
