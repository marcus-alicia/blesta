<?php
namespace CoinGate;

class OrderTest extends TestCase
{
    public function testFindOrderNotFound()
    {
        $this->assertFalse(Merchant\Order::find(0, [], self::getGoodAuthentication()));

        try {
            $this->assertFalse(Merchant\Order::findOrFail(0, [], self::getGoodAuthentication()));
        } catch (\Exception $e) {
            $this->assertRegExp('/OrderNotFound/', $e->getMessage());
        }
    }

    public function testFindOrderFound()
    {
        $order = Merchant\Order::create(self::getGoodPostParams(), [], self::getGoodAuthentication());
        $this->assertNotFalse(Merchant\Order::find($order->id, [], self::getGoodAuthentication()));
    }

    public function testCreateOrderIsNotValid()
    {
        $this->assertFalse(Merchant\Order::create([], [], self::getGoodAuthentication()));
        try {
            $this->assertFalse(Merchant\Order::createOrFail([], [], self::getGoodAuthentication()));
        } catch (\Exception $e) {
            $this->assertRegExp('/OrderIsNotValid/', $e->getMessage());
        }
    }

    public function testCreateOrderValid()
    {
        $this->assertNotFalse(Merchant\Order::create(self::getGoodPostParams(), [], self::getGoodAuthentication()));
    }

    public static function getGoodPostParams()
    {
        return [
            'order_id'          => 'ORDER-1412759368',
            'price'             => 1050.99,
            'currency'          => 'USD',
            'receive_currency'  => 'EUR',
            'callback_url'      => 'https://example.com/payments/callback?token=6tCENGUYI62ojkuzDPX7Jg',
            'cancel_url'        => 'https://example.com/cart',
            'success_url'       => 'https://example.com/account/orders',
            'description'       => 'Apple Iphone 6'
        ];
    }
}
