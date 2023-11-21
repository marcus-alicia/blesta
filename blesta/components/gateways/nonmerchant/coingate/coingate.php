<?php
/**
 * Coingate Payment Gateway
 *
 * Allows users to pay with Bitcoins and Altcoins
 *
 * @package blesta
 * @subpackage blesta.components.gateways.nonmerchant.coingate
 * @author CoinGate
 * @copyright Copyright (c) 2018, Phillips Data, Inc. Copyright (c) 2018, CoinGate
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @license http://github.com/blesta/coingate/blob/master/LICENSE
 * @link http://www.blesta.com/ Blesta
 * @link https://coingate.com Coingate
 */
class Coingate extends NonmerchantGateway
{
    private $meta;
    public function __construct()
    {
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        Loader::loadComponents($this, ['Input']);

        Loader::loadModels($this, ['Clients']);

        Language::loadLang('coingate', null, dirname(__FILE__) . DS . 'language' . DS);
    }

    /**
     * {@inheritdoc}
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * {@inheritdoc}
     */
    public function getSettings(array $meta = null)
    {
        $this->view = $this->makeView('settings', 'default', str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS));

        Loader::loadHelpers($this, ['Form', 'Html']);

        $receiveCurrency = [
            'BTC' => Language::_('Coingate.receive_currency.btc', true),
            'EUR' => Language::_('Coingate.receive_currency.eur', true),
            'USD' => Language::_('Coingate.receive_currency.usd', true),
        ];

        $coingateEnvironment = [
            'sandbox' => Language::_('Coingate.environment.sandbox', true),
            'live' => Language::_('Coingate.environment.live', true),
        ];

        $this->view->set('meta', $meta);
        $this->view->set('receive_currency', $receiveCurrency);
        $this->view->set('coingate_environment', $coingateEnvironment);

        return $this->view->fetch();
    }

    /**
     * {@inheritdoc}
     */
    public function editSettings(array $meta)
    {
        $rules = [
            'app_id'     => [
                'empty' => [
                    'rule'    => 'isEmpty',
                    'negate'  => true,
                    'message' => Language::_('Coingate.!error.app_id.empty', true),
                ],
            ],
            'api_key'    => [
                'empty' => [
                    'rule'    => 'isEmpty',
                    'negate'  => true,
                    'message' => Language::_('Coingate.!error.api_key.empty', true),
                ],
            ],
            'api_secret' => [
                'empty' => [
                    'rule'    => 'isEmpty',
                    'negate'  => true,
                    'message' => Language::_('Coingate.!error.api_secret.empty', true),
                ],
            ],
        ];

        $this->Input->setRules($rules);

        $this->Input->validates($meta);

        return $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function encryptableFields()
    {
        return ['app_id', 'api_key', 'api_secret'];
    }

    /**
     * {@inheritdoc}
     */
    public function setMeta(array $meta = null)
    {
        $this->meta = $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function buildProcess(array $contactInfo, $amount, array $invoiceAmounts = null, array $options = null)
    {
        Loader::load(dirname(__FILE__) . DS . 'init.php');

        $clientId = (isset($contactInfo['client_id']) ? $contactInfo['client_id'] : null);

        if (isset($invoiceAmounts) && is_array($invoiceAmounts)) {
            $invoices = $this->serializeInvoices($invoiceAmounts);
        }

        $record = new Record();
        $companyName = $record->select('name')->from('companies')->where('id', '=', 1)->fetch();

        $orderId = $clientId . '@' . (!empty($invoices) ? $invoices : time());
        $token = md5($orderId);

        $callbackURL = Configure::get('Blesta.gw_callback_url')
            . Configure::get('Blesta.company_id') . '/coingate/?client_id='
            . (isset($contactInfo['client_id']) ? $contactInfo['client_id'] : null) . '&token=' . $token;

        $testMode = $this->coingateEnvironment();

        $postParams = [
            'order_id'         => $orderId,
            'price'            => (isset($amount) ? $amount : null),
            'description'      => (isset($options['description']) ? $options['description'] : null),
            'title'            => $companyName->name . ' ' . (isset($options['description']) ? $options['description'] : null),
            'token'            => $token,
            'currency'         => (isset($this->currency) ? $this->currency : null),
            'receive_currency' => $this->meta['receive_currency'],
            'callback_url'     => $callbackURL,
            'cancel_url'       => (isset($options['return_url']) ? $options['return_url'] : null),
            'success_url'      => (isset($options['return_url']) ? $options['return_url'] : null),
        ];

        $order = \CoinGate\Merchant\Order::create(
            $postParams,
            [],
            [
                'environment' => $testMode,
                'app_id'      => $this->meta['app_id'],
                'api_key'     => $this->meta['api_key'],
                'api_secret'  => $this->meta['api_secret'],
                'user_agent'  => 'CoinGate - Blesta v' . BLESTA_VERSION . ' Extension v' . $this->getVersion(),
            ]
        );

        if ($order && $order->payment_url) {
            header('Location: ' . $order->payment_url);
        } else {
            print_r($order);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validate(array $get, array $post)
    {
        $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), serialize($post), 'output', true);

        $dataParts = explode('@', (isset($post['order_id']) ? $post['order_id'] : null), 2);

        $clientId = $dataParts[0];

        $invoices = (isset($dataParts[1]) ? $dataParts[1] : null);

        if (is_numeric($invoices)) {
            $invoices = null;
        }

        $orderId = $post['order_id'];
        $token = md5($orderId);

        if (empty($get['token']) || strcmp($get['token'], $token) !== 0) {
            $errorMessage = 'CoinGate Token: ' . $get['token'] . ' is not valid';
            $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), $errorMessage, 'output', true);
            throw new Exception($errorMessage);
        }

        $status = $this->statusChecking($post['id']);

        return [
            'client_id'      => $clientId,
            'amount'         => (isset($post['price']) ? $post['price'] : null),
            'currency'       => (isset($post['currency']) ? $post['currency'] : null),
            'status'         => $status,
            'reference_id'   => null,
            'transaction_id' => (isset($post['id']) ? $post['id'] : null),
            'invoices'       => $this->unserializeInvoices($invoices),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function success(array $get, array $post)
    {
        $dataParts = explode('@', (isset($post['order_id']) ? $post['order_id'] : null), 2);

        $clientId = $dataParts[0];

        $invoices = (isset($dataParts[1]) ? $dataParts[1] : null);

        if (is_numeric($invoices)) {
            $invoices = null;
        }

        $orderId = $post['order_id'];
        $token = md5($orderId);

        if (empty($get['token']) || strcmp($get['token'], $token) !== 0) {
            $errorMessage = 'CoinGate Token: ' . $get['token'] . ' is not valid';
            $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), $errorMessage, 'output', true);
            throw new Exception($errorMessage);
        }

        $status = $this->statusChecking($post['id']);

        return [
            'client_id'      => $clientId,
            'amount'         => (isset($post['price']) ? $post['price'] : null),
            'currency'       => (isset($post['currency']) ? $post['currency'] : null),
            'status'         => $status,
            'transaction_id' => (isset($post['id']) ? $post['id'] : null),
            'invoices'       => $this->unserializeInvoices($invoices),
        ];
    }

    /**
     * Serializes an array of invoice info into a string
     *
     * @param array A numerically indexed array invoices info including:
     *  - id The ID of the invoice
     *  - amount The amount relating to the invoice
     * @return string A serialized string of invoice info in the format of key1=value1|key2=value2
     */
    private function serializeInvoices(array $invoices)
    {
        $str = '';
        foreach ($invoices as $i => $invoice) {
            $str .= ($i > 0 ? '|' : '') . $invoice['id'] . '=' . $invoice['amount'];
        }

        return $str;
    }

    /**
     * Unserializes a string of invoice info into an array
     *
     * @param string A serialized string of invoice info in the format of key1=value1|key2=value2
     * @return array A numerically indexed array invoices info including:
     *  - id The ID of the invoice
     *  - amount The amount relating to the invoice
     */
    private function unserializeInvoices($str)
    {
        $invoices = [];
        $temp = explode('|', $str);
        foreach ($temp as $pair) {
            $pairs = explode('=', $pair, 2);
            if (count($pairs) != 2) {
                continue;
            }
            $invoices[] = ['id' => $pairs[0], 'amount' => $pairs[1]];
        }

        return $invoices;
    }

    /**
     * Determines the target CoinGate environment
     *
     * @return string The correct CoinGate environment
     */
    private function coingateEnvironment()
    {

        if ($this->meta['coingate_environment'] == 'sandbox') {
            $testMode = 'sandbox';
        } else {
            $testMode = 'live';
        }

        return $testMode;
    }

    /**
     * Retreives a CoinGate order for the given ID
     *
     * @param int $id The CoinGate order ID
     * @return mixed \CoinGate\Merchant\Order or false on failure
     */
    private function coingateCallback($id)
    {

        Loader::load(dirname(__FILE__) . DS . 'init.php');

        $testMode = $this->coingateEnvironment();

        $order = \CoinGate\Merchant\Order::find(
            $id,
            [],
            [
                'environment' => $testMode,
                'app_id'      => $this->meta['app_id'],
                'api_key'     => $this->meta['api_key'],
                'api_secret'  => $this->meta['api_secret'],
                'user_agent'  => 'CoinGate - Blesta v' . BLESTA_VERSION . ' Extension v' . $this->getVersion(),
            ]
        );

        return $order;
    }

    public function statusChecking($id)
    {
        $status = 'error';

        $cgOrder = $this->coingateCallback($id);

        if (isset($cgOrder)) {
            switch ($cgOrder->status) {
                case 'pending':
                    $status = 'pending';
                    break;
                case 'confirming':
                    $status = 'pending';
                    break;
                case 'paid':
                    $status = 'approved';
                    break;
                case 'invalid':
                    $status = 'declined';
                    $this->Input->setErrors(
                        ['transaction' => ['response' => Language::_('Coingate.!error.payment.invalid', true)]]
                    );
                    break;
                case 'canceled':
                    $status = 'declined';
                    $this->Input->setErrors(
                        ['transaction' => ['response' => Language::_('Coingate.!error.payment.canceled', true)]]
                    );
                    break;
                case 'expired':
                    $status = 'declined';
                    $this->Input->setErrors(
                        ['transaction' => ['response' => Language::_('Coingate.!error.payment.expired', true)]]
                    );
                    break;
                case 'refunded':
                    $status = 'refunded';
                    break;
                default:
                    $status = 'pending';
                    $this->Input->setErrors(
                        ['transaction' => ['response' => Language::_('Coingate.!error.failed.response', true)]]
                    );
            }
        }

        return $status;
    }
}
