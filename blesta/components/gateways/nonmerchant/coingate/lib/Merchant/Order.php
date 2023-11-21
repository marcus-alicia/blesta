<?php
namespace CoinGate\Merchant;

use CoinGate\CoinGate;
use CoinGate\Merchant;
use CoinGate\OrderIsNotValid;
use CoinGate\OrderNotFound;

class Order extends Merchant
{
    private $order;

    public function __construct($order)
    {
        $this->order = $order;
    }

    public function toHash()
    {
        return $this->order;
    }

    public function __get($name)
    {
        return $this->order[$name];
    }

    public static function find($orderId, $options = [], $authentication = [])
    {
        try {
            return self::findOrFail($orderId, $options, $authentication);
        } catch (OrderNotFound $e) {
            return false;
        }
    }

    public static function findOrFail($orderId, $options = [], $authentication = [])
    {
        $order = CoinGate::request('/orders/' . $orderId, 'GET', [], $authentication);

        return new self($order);
    }

    public static function create($params, $options = [], $authentication = [])
    {
        try {
            return self::createOrFail($params, $options, $authentication);
        } catch (OrderIsNotValid $e) {
            return false;
        }
    }

    public static function createOrFail($params, $options = [], $authentication = [])
    {
        $order = CoinGate::request('/orders', 'POST', $params, $authentication);

        return new self($order);
    }
}
